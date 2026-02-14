<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class WhatsAppTemplateService extends BaseService
{
    private const API_BASE = 'https://graph.facebook.com/v19.0/';

    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['base_uri' => self::API_BASE, 'timeout' => 30]);
    }

    /**
     * @param array{
     *   whatsapp_phone_number_id: string,
     *   name: string,
     *   language?: string,
     *   category: string,
     *   header_type?: string,
     *   header_content?: string,
     *   body_text: string,
     *   footer_text?: string,
     *   buttons?: array,
     *   sample_values?: array,
     * } $data
     */
    public function create(Workspace $workspace, array $data): WhatsAppTemplate
    {
        // Template names must be lowercase, underscores only, max 512 chars
        $data['name'] = strtolower(preg_replace('/[^a-z0-9_]/', '_', strtolower($data['name'])));

        return WhatsAppTemplate::create([
            'workspace_id' => $workspace->id,
            'whatsapp_phone_number_id' => $data['whatsapp_phone_number_id'],
            'name' => $data['name'],
            'language' => $data['language'] ?? 'en',
            'category' => $data['category'],
            'status' => WhatsAppTemplateStatus::DRAFT,
            'header_type' => $data['header_type'] ?? 'none',
            'header_content' => $data['header_content'] ?? null,
            'body_text' => $data['body_text'],
            'footer_text' => $data['footer_text'] ?? null,
            'buttons' => $data['buttons'] ?? null,
            'sample_values' => $data['sample_values'] ?? null,
        ]);
    }

    public function update(WhatsAppTemplate $template, array $data): WhatsAppTemplate
    {
        if ($data['name'] ?? null) {
            $data['name'] = strtolower(preg_replace('/[^a-z0-9_]/', '_', strtolower($data['name'])));
        }

        $template->update($data);

        return $template->refresh();
    }

    public function submitForApproval(WhatsAppTemplate $template): void
    {
        if (! $template->canSubmit()) {
            throw new \RuntimeException('Template cannot be submitted in current status: ' . $template->status->value);
        }

        $phone = $template->phoneNumber;
        $waba = $phone->businessAccount;

        $components = $this->buildMetaComponents($template);

        $response = $this->client->post($waba->waba_id . '/message_templates', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => [
                'name' => $template->name,
                'language' => $template->language,
                'category' => strtoupper($template->category->value),
                'components' => $components,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        $template->update([
            'meta_template_id' => $result['id'] ?? null,
            'status' => WhatsAppTemplateStatus::PENDING_APPROVAL,
            'submitted_at' => now(),
        ]);
    }

    public function syncTemplateStatus(WhatsAppTemplate $template): void
    {
        if (! $template->meta_template_id) {
            return;
        }

        $waba = $template->phoneNumber->businessAccount;

        $response = $this->client->get($template->meta_template_id, [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'query' => ['fields' => 'status,rejected_reason'],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $metaStatus = strtolower($result['status'] ?? '');

        $statusMap = [
            'approved' => WhatsAppTemplateStatus::APPROVED,
            'rejected' => WhatsAppTemplateStatus::REJECTED,
            'pending' => WhatsAppTemplateStatus::PENDING_APPROVAL,
            'disabled' => WhatsAppTemplateStatus::DISABLED,
            'paused' => WhatsAppTemplateStatus::PAUSED,
        ];

        $newStatus = $statusMap[$metaStatus] ?? $template->status;
        $updates = ['status' => $newStatus];

        if ($newStatus === WhatsAppTemplateStatus::APPROVED && ! $template->approved_at) {
            $updates['approved_at'] = now();
        }

        if ($newStatus === WhatsAppTemplateStatus::REJECTED) {
            $updates['rejection_reason'] = $result['rejected_reason'] ?? null;
        }

        $template->update($updates);
    }

    public function disable(WhatsAppTemplate $template): void
    {
        $template->update(['status' => WhatsAppTemplateStatus::DISABLED]);
    }

    /**
     * Sync all templates from Meta for a WABA.
     */
    public function listFromMeta(WhatsAppBusinessAccount $waba): Collection
    {
        $response = $this->client->get($waba->waba_id . '/message_templates', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'query' => ['fields' => 'name,language,category,status,components', 'limit' => 250],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return collect($result['data'] ?? []);
    }

    /**
     * Sync templates from WhatsApp Business API to database.
     * Fetches all templates from Meta and creates/updates them in the database.
     */
    public function syncTemplatesFromApi(WhatsAppBusinessAccount $waba): array
    {
        $metaTemplates = $this->listFromMeta($waba);
        
        $stats = [
            'fetched' => $metaTemplates->count(),
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        foreach ($metaTemplates as $metaTemplate) {
            $this->syncSingleTemplate($waba, $metaTemplate, $stats);
        }

        return $stats;
    }

    /**
     * Sync a single template from Meta API data.
     */
    private function syncSingleTemplate(WhatsAppBusinessAccount $waba, array $metaTemplate, array &$stats): void
    {
        $templateId = $metaTemplate['id'] ?? null;
        $templateName = $metaTemplate['name'] ?? null;
        $language = $metaTemplate['language'] ?? 'en';
        $category = $this->mapMetaCategoryToLocal($metaTemplate['category'] ?? 'MARKETING');
        $status = $this->mapMetaStatusToLocal($metaTemplate['status'] ?? 'PENDING');
        $components = $metaTemplate['components'] ?? [];

        if (!$templateId || !$templateName) {
            return;
        }

        // Parse components to extract template structure
        $parsed = $this->parseMetaComponents($components);

        // Get the first phone number for this WABA (templates are WABA-level but we store per phone)
        $phoneNumber = $waba->phoneNumbers()->first();
        if (!$phoneNumber) {
            return;
        }

        // Get workspace from tenant
        $workspace = $waba->tenant->workspaces()->first();
        if (!$workspace) {
            return;
        }

        // Find existing template by meta_template_id or name+language combination
        $existing = WhatsAppTemplate::where('meta_template_id', $templateId)
            ->orWhere(function ($query) use ($phoneNumber, $templateName, $language) {
                $query->where('whatsapp_phone_number_id', $phoneNumber->id)
                    ->where('name', $templateName)
                    ->where('language', $language);
            })
            ->first();

        $data = [
            'workspace_id' => $workspace->id,
            'whatsapp_phone_number_id' => $phoneNumber->id,
            'meta_template_id' => $templateId,
            'name' => $templateName,
            'language' => $language,
            'category' => $category,
            'status' => $status,
            'header_type' => $parsed['header_type'],
            'header_content' => $parsed['header_content'],
            'body_text' => $parsed['body_text'],
            'footer_text' => $parsed['footer_text'],
            'buttons' => $parsed['buttons'],
        ];

        if ($status === WhatsAppTemplateStatus::APPROVED && !$existing?->approved_at) {
            $data['approved_at'] = now();
        }

        if ($existing) {
            // Check if anything changed
            $changed = false;
            foreach ($data as $key => $value) {
                if ($key === 'approved_at' && $existing->approved_at) {
                    continue; // Don't overwrite existing approval date
                }
                
                $existingValue = $existing->$key;
                
                // Handle enum comparison
                if ($existingValue instanceof \BackedEnum) {
                    $existingValue = $existingValue->value;
                }
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }
                
                // Handle array comparison
                if (is_array($existingValue) && is_array($value)) {
                    if (json_encode($existingValue) !== json_encode($value)) {
                        $changed = true;
                        break;
                    }
                } elseif ($existingValue != $value) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $existing->update($data);
                $stats['updated']++;
            } else {
                $stats['unchanged']++;
            }
        } else {
            WhatsAppTemplate::create($data);
            $stats['created']++;
        }
    }

    /**
     * Map Meta API category to local enum.
     */
    private function mapMetaCategoryToLocal(string $metaCategory): \App\Enums\WhatsApp\WhatsAppTemplateCategory
    {
        $categoryMap = [
            'MARKETING' => \App\Enums\WhatsApp\WhatsAppTemplateCategory::MARKETING,
            'UTILITY' => \App\Enums\WhatsApp\WhatsAppTemplateCategory::UTILITY,
            'AUTHENTICATION' => \App\Enums\WhatsApp\WhatsAppTemplateCategory::AUTHENTICATION,
        ];

        return $categoryMap[strtoupper($metaCategory)] ?? \App\Enums\WhatsApp\WhatsAppTemplateCategory::MARKETING;
    }

    /**
     * Map Meta API status to local enum.
     */
    private function mapMetaStatusToLocal(string $metaStatus): WhatsAppTemplateStatus
    {
        $statusMap = [
            'APPROVED' => WhatsAppTemplateStatus::APPROVED,
            'REJECTED' => WhatsAppTemplateStatus::REJECTED,
            'PENDING' => WhatsAppTemplateStatus::PENDING_APPROVAL,
            'DISABLED' => WhatsAppTemplateStatus::DISABLED,
            'PAUSED' => WhatsAppTemplateStatus::PAUSED,
        ];

        return $statusMap[strtoupper($metaStatus)] ?? WhatsAppTemplateStatus::PENDING_APPROVAL;
    }

    /**
     * Parse Meta API components into our template structure.
     */
    private function parseMetaComponents(array $components): array
    {
        $parsed = [
            'header_type' => 'none',
            'header_content' => null,
            'body_text' => '',
            'footer_text' => null,
            'buttons' => null,
        ];

        foreach ($components as $component) {
            $type = strtoupper($component['type'] ?? '');

            switch ($type) {
                case 'HEADER':
                    $format = strtolower($component['format'] ?? 'text');
                    $parsed['header_type'] = $format;
                    if ($format === 'text') {
                        $parsed['header_content'] = $component['text'] ?? null;
                    }
                    break;

                case 'BODY':
                    $parsed['body_text'] = $component['text'] ?? '';
                    break;

                case 'FOOTER':
                    $parsed['footer_text'] = $component['text'] ?? null;
                    break;

                case 'BUTTONS':
                    $parsed['buttons'] = $component['buttons'] ?? null;
                    break;
            }
        }

        return $parsed;
    }

    /**
     * @return LengthAwarePaginator<WhatsAppTemplate>
     */
    public function listForWorkspace(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppTemplate::forWorkspace($workspaceId)
            ->with('phoneNumber');

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['category'] ?? null) {
            $query->where('category', $filters['category']);
        }

        if ($filters['search'] ?? null) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Build Meta API components array from template.
     */
    private function buildMetaComponents(WhatsAppTemplate $template): array
    {
        $components = [];

        // Header
        if ($template->header_type !== 'none') {
            $header = ['type' => 'HEADER'];
            if ($template->header_type === 'text') {
                $header['format'] = 'TEXT';
                $header['text'] = $template->header_content;
            } else {
                $header['format'] = strtoupper($template->header_type);
            }
            $components[] = $header;
        }

        // Body
        $body = ['type' => 'BODY', 'text' => $template->body_text];
        if ($template->sample_values) {
            $body['example'] = ['body_text' => [$template->sample_values]];
        }
        $components[] = $body;

        // Footer
        if ($template->footer_text) {
            $components[] = ['type' => 'FOOTER', 'text' => $template->footer_text];
        }

        // Buttons
        if ($template->buttons) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $template->buttons];
        }

        return $components;
    }

    /**
     * Build components array for sending a template message with parameter substitution.
     * 
     * @param WhatsAppTemplate $template The template to send
     * @param array $parameters Associative array of parameter values ['1' => 'value1', '2' => 'value2']
     * @return array Components array ready for WhatsApp API
     */
    public function buildSendComponents(WhatsAppTemplate $template, array $parameters = []): array
    {
        $components = [];

        // Header with parameters
        if ($template->header_type === 'text' && $template->header_content) {
            $headerParams = $this->extractParametersForText($template->header_content, $parameters);
            if (!empty($headerParams)) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => $headerParams,
                ];
            }
        } elseif (in_array($template->header_type, ['image', 'video', 'document'])) {
            // Media headers need media parameter
            if (isset($parameters['header_media'])) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => $template->header_type,
                            $template->header_type => [
                                'link' => $parameters['header_media'],
                            ],
                        ],
                    ],
                ];
            }
        }

        // Body with parameters
        $bodyParams = $this->extractParametersForText($template->body_text, $parameters);
        if (!empty($bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }

        // Buttons with dynamic URLs or phone numbers
        if ($template->buttons && isset($parameters['button_payload'])) {
            $buttonComponents = [];
            foreach ($template->buttons as $index => $button) {
                $buttonType = $button['type'] ?? null;
                
                if ($buttonType === 'URL' && isset($parameters['button_payload'][$index])) {
                    $buttonComponents[] = [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => (string) $index,
                        'parameters' => [
                            ['type' => 'text', 'text' => $parameters['button_payload'][$index]],
                        ],
                    ];
                }
            }
            
            if (!empty($buttonComponents)) {
                $components = array_merge($components, $buttonComponents);
            }
        }

        return $components;
    }

    /**
     * Extract parameters from template text and match with provided values.
     * Template variables are in format {{1}}, {{2}}, etc.
     */
    private function extractParametersForText(string $text, array $parameters): array
    {
        $params = [];
        
        // Find all {{n}} placeholders
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $paramNumber) {
                $value = $parameters[$paramNumber] ?? '';
                $params[] = [
                    'type' => 'text',
                    'text' => (string) $value,
                ];
            }
        }
        
        return $params;
    }

    /**
     * Send a template message using the template.
     * 
     * @param WhatsAppTemplate $template The template to send
     * @param string $recipientPhone Recipient phone number in international format
     * @param array $parameters Parameter values for template variables
     * @param string|null $sentByUserId User ID who sent the message
     * @return WhatsAppMessage The created message record
     */
    public function sendTemplate(
        WhatsAppTemplate $template,
        string $recipientPhone,
        array $parameters = [],
        ?string $sentByUserId = null
    ): \App\Models\WhatsApp\WhatsAppMessage {
        if (!$template->canSend()) {
            throw new \RuntimeException(
                "Template cannot be sent. Current status: {$template->status->value}"
            );
        }

        $phone = $template->phoneNumber;
        $components = $this->buildSendComponents($template, $parameters);

        $messagingService = app(WhatsAppMessagingService::class);
        $message = $messagingService->sendTemplateMessage(
            $phone,
            $recipientPhone,
            $template->name,
            $template->language,
            $components,
            $sentByUserId
        );

        // Increment template usage
        $template->incrementUsage();

        return $message;
    }
}
