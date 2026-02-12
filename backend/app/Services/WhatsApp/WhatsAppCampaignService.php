<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppCampaignStatus;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Jobs\WhatsApp\SendWhatsAppCampaignJob;
use App\Models\WhatsApp\WhatsAppCampaign;
use App\Models\WhatsApp\WhatsAppCampaignRecipient;
use App\Models\WhatsApp\WhatsAppOptIn;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class WhatsAppCampaignService extends BaseService
{
    /**
     * @param array{
     *   whatsapp_phone_number_id: string,
     *   template_id: string,
     *   name: string,
     *   template_params_mapping?: array,
     *   audience_filter?: array,
     * } $data
     */
    public function create(Workspace $workspace, string $userId, array $data): WhatsAppCampaign
    {
        return WhatsAppCampaign::create([
            'workspace_id' => $workspace->id,
            'whatsapp_phone_number_id' => $data['whatsapp_phone_number_id'],
            'template_id' => $data['template_id'],
            'name' => $data['name'],
            'status' => WhatsAppCampaignStatus::DRAFT,
            'template_params_mapping' => $data['template_params_mapping'] ?? null,
            'audience_filter' => $data['audience_filter'] ?? null,
            'created_by_user_id' => $userId,
        ]);
    }

    public function update(WhatsAppCampaign $campaign, array $data): WhatsAppCampaign
    {
        if (! $campaign->canEdit()) {
            throw new \RuntimeException('Campaign cannot be edited in current status: ' . $campaign->status->value);
        }

        $campaign->update($data);

        return $campaign->refresh();
    }

    /**
     * Resolve audience filter → create recipient records.
     */
    public function buildAudience(WhatsAppCampaign $campaign): int
    {
        // Delete existing recipients for rebuild
        $campaign->recipients()->delete();

        $query = WhatsAppOptIn::where('workspace_id', $campaign->workspace_id)
            ->where('is_active', true);

        $filter = $campaign->audience_filter;

        if ($filter) {
            if (! empty($filter['tags'])) {
                $query->where(function ($q) use ($filter) {
                    foreach ($filter['tags'] as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            if (! empty($filter['opt_in_after'])) {
                $query->where('opted_in_at', '>=', $filter['opt_in_after']);
            }

            if (! empty($filter['exclude_tags'])) {
                foreach ($filter['exclude_tags'] as $tag) {
                    $query->whereJsonDoesntContain('tags', $tag);
                }
            }
        }

        $count = 0;
        $mapping = $campaign->template_params_mapping ?? [];

        $query->chunk(500, function ($contacts) use ($campaign, $mapping, &$count) {
            $recipients = [];
            foreach ($contacts as $contact) {
                $params = $this->resolveTemplateParams($contact, $mapping);
                $recipients[] = [
                    'campaign_id' => $campaign->id,
                    'opt_in_id' => $contact->id,
                    'phone_number' => $contact->phone_number,
                    'customer_name' => $contact->customer_name,
                    'template_params' => json_encode($params),
                    'status' => WhatsAppMessageStatus::PENDING->value,
                ];
                $count++;
            }
            WhatsAppCampaignRecipient::insert(array_map(function ($r) {
                $r['id'] = (string) \Illuminate\Support\Str::uuid();

                return $r;
            }, $recipients));
        });

        $campaign->update(['total_recipients' => $count]);

        return $count;
    }

    public function schedule(WhatsAppCampaign $campaign, Carbon $scheduledAt): void
    {
        if (! $campaign->canEdit() && $campaign->status !== WhatsAppCampaignStatus::DRAFT) {
            throw new \RuntimeException('Campaign cannot be scheduled in current status.');
        }

        if ($campaign->total_recipients === 0) {
            throw new \RuntimeException('Campaign has no recipients. Build audience first.');
        }

        $campaign->update([
            'status' => WhatsAppCampaignStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        SendWhatsAppCampaignJob::dispatch($campaign)->delay($scheduledAt);
    }

    public function send(WhatsAppCampaign $campaign): void
    {
        if ($campaign->total_recipients === 0) {
            throw new \RuntimeException('Campaign has no recipients.');
        }

        $campaign->update([
            'status' => WhatsAppCampaignStatus::SENDING,
            'started_at' => now(),
        ]);

        SendWhatsAppCampaignJob::dispatch($campaign);
    }

    public function cancel(WhatsAppCampaign $campaign): void
    {
        if (! $campaign->canCancel()) {
            throw new \RuntimeException('Campaign cannot be cancelled in current status.');
        }

        $campaign->update(['status' => WhatsAppCampaignStatus::CANCELLED]);
    }

    /**
     * @return array{
     *   total_recipients: int,
     *   sent: int,
     *   delivered: int,
     *   read: int,
     *   failed: int,
     *   delivery_rate: float,
     *   read_rate: float,
     * }
     */
    public function getStats(WhatsAppCampaign $campaign): array
    {
        return [
            'total_recipients' => $campaign->total_recipients,
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->delivered_count,
            'read' => $campaign->read_count,
            'failed' => $campaign->failed_count,
            'delivery_rate' => $campaign->getDeliveryRate(),
            'read_rate' => $campaign->getReadRate(),
        ];
    }

    /**
     * Validate audience before sending.
     *
     * @return array{valid_count: int, invalid_count: int, reasons: string[]}
     */
    public function validateAudience(WhatsAppCampaign $campaign): array
    {
        $template = $campaign->template;
        $phone = $campaign->phoneNumber;
        $reasons = [];

        if (! $template->isApproved()) {
            $reasons[] = 'Template is not approved (status: ' . $template->status->value . ')';
        }

        $waba = $phone->businessAccount;
        if (! $waba->isVerified()) {
            $reasons[] = 'WhatsApp Business Account is not verified';
        }

        if (! $waba->canSendMarketing()) {
            $reasons[] = 'Marketing is disabled for this account';
        }

        // Count active opt-ins
        $validCount = $campaign->recipients()
            ->whereHas('optIn', fn ($q) => $q->where('is_active', true))
            ->count();

        $invalidCount = $campaign->total_recipients - $validCount;

        if ($invalidCount > 0) {
            $reasons[] = "{$invalidCount} recipients have inactive opt-ins";
        }

        return [
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'reasons' => $reasons,
        ];
    }

    /**
     * @return LengthAwarePaginator<WhatsAppCampaign>
     */
    public function listForWorkspace(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppCampaign::forWorkspace($workspaceId)
            ->with(['template', 'createdBy']);

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search'] ?? null) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Resolve template params from contact data based on mapping.
     */
    private function resolveTemplateParams(WhatsAppOptIn $contact, array $mapping): array
    {
        $params = [];

        foreach ($mapping as $placeholder => $field) {
            $params[$placeholder] = match ($field) {
                'customer_name' => $contact->customer_name ?? '',
                'phone_number' => $contact->phone_number,
                default => $field, // static value
            };
        }

        return $params;
    }
}
