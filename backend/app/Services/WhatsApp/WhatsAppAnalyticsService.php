<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppConversationStatus;
use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppDailyMetric;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class WhatsAppAnalyticsService extends BaseService
{
    /**
     * Aggregate daily metrics for a specific date and phone number.
     */
    public function aggregateDailyMetrics(WhatsAppPhoneNumber $phone, Carbon $date): WhatsAppDailyMetric
    {
        $waba = $phone->businessAccount;
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get workspace IDs from conversations for this phone
        $workspaceId = WhatsAppConversation::where('whatsapp_phone_number_id', $phone->id)
            ->value('workspace_id');

        if (! $workspaceId) {
            return WhatsAppDailyMetric::firstOrCreate(
                ['whatsapp_phone_number_id' => $phone->id, 'date' => $date->toDateString()],
                ['workspace_id' => $waba->tenant_id],
            );
        }

        $conversationIds = WhatsAppConversation::where('whatsapp_phone_number_id', $phone->id)->pluck('id');

        $messagesQuery = WhatsAppMessage::whereIn('conversation_id', $conversationIds)
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        $outboundMessages = (clone $messagesQuery)->where('direction', WhatsAppMessageDirection::OUTBOUND);

        $metrics = [
            'workspace_id' => $workspaceId,
            'conversations_started' => WhatsAppConversation::where('whatsapp_phone_number_id', $phone->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            'conversations_resolved' => WhatsAppConversation::where('whatsapp_phone_number_id', $phone->id)
                ->where('status', WhatsAppConversationStatus::RESOLVED)
                ->whereBetween('updated_at', [$startOfDay, $endOfDay])->count(),
            'messages_sent' => (clone $outboundMessages)->count(),
            'messages_delivered' => (clone $outboundMessages)->where('status', WhatsAppMessageStatus::DELIVERED)->count(),
            'messages_read' => (clone $outboundMessages)->where('status', WhatsAppMessageStatus::READ)->count(),
            'messages_failed' => (clone $outboundMessages)->where('status', WhatsAppMessageStatus::FAILED)->count(),
            'templates_sent' => (clone $outboundMessages)->whereNotNull('template_id')->count(),
            'avg_first_response_seconds' => $this->calculateAvgFirstResponse($phone, $startOfDay, $endOfDay),
        ];

        return WhatsAppDailyMetric::updateOrCreate(
            ['whatsapp_phone_number_id' => $phone->id, 'date' => $date->toDateString()],
            $metrics,
        );
    }

    /**
     * @return array{conversations_open: int, conversations_pending: int, avg_response_time: int|null, unassigned: int}
     */
    public function getInboxHealth(string $workspaceId): array
    {
        return [
            'conversations_open' => WhatsAppConversation::where('workspace_id', $workspaceId)
                ->where('status', WhatsAppConversationStatus::ACTIVE)->count(),
            'conversations_pending' => WhatsAppConversation::where('workspace_id', $workspaceId)
                ->where('status', WhatsAppConversationStatus::PENDING)->count(),
            'avg_response_time' => $this->getAvgResponseTimeToday($workspaceId),
            'unassigned' => WhatsAppConversation::where('workspace_id', $workspaceId)
                ->whereIn('status', [WhatsAppConversationStatus::ACTIVE, WhatsAppConversationStatus::PENDING])
                ->whereNull('assigned_to_user_id')->count(),
        ];
    }

    /**
     * @return array{total_sent: int, total_delivered: int, total_read: int, total_failed: int, delivery_rate: float, read_rate: float}
     */
    public function getMarketingPerformance(string $workspaceId, string $from, string $to): array
    {
        $metrics = WhatsAppDailyMetric::forWorkspace($workspaceId)
            ->forDateRange($from, $to)
            ->selectRaw('SUM(messages_sent) as total_sent, SUM(messages_delivered) as total_delivered, SUM(messages_read) as total_read, SUM(messages_failed) as total_failed, SUM(templates_sent) as total_templates')
            ->first();

        $totalSent = (int) ($metrics->total_sent ?? 0);
        $totalDelivered = (int) ($metrics->total_delivered ?? 0);

        return [
            'total_sent' => $totalSent,
            'total_delivered' => $totalDelivered,
            'total_read' => (int) ($metrics->total_read ?? 0),
            'total_failed' => (int) ($metrics->total_failed ?? 0),
            'delivery_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0.0,
            'read_rate' => $totalDelivered > 0 ? round(((int) ($metrics->total_read ?? 0) / $totalDelivered) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{quality_ratings: array, block_count: int, template_rejection_rate: float}
     */
    public function getComplianceHealth(string $workspaceId): array
    {
        $blockCount = WhatsAppDailyMetric::forWorkspace($workspaceId)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->sum('block_count');

        return [
            'block_count' => (int) $blockCount,
        ];
    }

    /**
     * @return array<array{user_id: string, name: string, conversations_handled: int, messages_sent: int, avg_response_time: int|null}>
     */
    public function getAgentProductivity(string $workspaceId): array
    {
        return WhatsAppConversation::where('workspace_id', $workspaceId)
            ->whereNotNull('assigned_to_user_id')
            ->select('assigned_to_user_id')
            ->selectRaw('COUNT(*) as conversations_handled')
            ->groupBy('assigned_to_user_id')
            ->orderByDesc('conversations_handled')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->assigned_to_user_id,
                'conversations_handled' => $row->conversations_handled,
            ])
            ->toArray();
    }

    private function calculateAvgFirstResponse(WhatsAppPhoneNumber $phone, Carbon $start, Carbon $end): ?int
    {
        $conversations = WhatsAppConversation::where('whatsapp_phone_number_id', $phone->id)
            ->whereNotNull('first_response_at')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        if ($conversations->isEmpty()) {
            return null;
        }

        $totalSeconds = $conversations->sum(fn ($c) => $c->first_response_at->diffInSeconds($c->created_at));

        return (int) round($totalSeconds / $conversations->count());
    }

    private function getAvgResponseTimeToday(string $workspaceId): ?int
    {
        $result = WhatsAppConversation::where('workspace_id', $workspaceId)
            ->whereNotNull('first_response_at')
            ->whereDate('created_at', today())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_seconds')
            ->value('avg_seconds');

        return $result ? (int) round((float) $result) : null;
    }
}
