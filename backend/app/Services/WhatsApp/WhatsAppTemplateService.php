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

    public function __construct()
    {
        $this->client = new Client(['base_uri' => self::API_BASE, 'timeout' => 30]);
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
}
