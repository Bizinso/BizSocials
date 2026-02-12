<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAutomationAction;
use App\Enums\WhatsApp\WhatsAppAutomationTrigger;
use App\Models\WhatsApp\WhatsAppAutomationRule;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final class WhatsAppAutomationService extends BaseService
{
    public function __construct(
        private readonly WhatsAppMessagingService $messagingService,
        private readonly WhatsAppConversationService $conversationService,
    ) {}

    /**
     * Evaluate all active rules for a conversation when a message arrives.
     */
    public function evaluateRules(WhatsAppConversation $conversation, WhatsAppMessage $message): void
    {
        $rules = WhatsAppAutomationRule::forWorkspace($conversation->workspace_id)
            ->active()
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesTrigger($rule, $conversation, $message)) {
                $this->executeRule($rule, $conversation, $message);
            }
        }
    }

    public function executeRule(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation, ?WhatsAppMessage $message = null): void
    {
        try {
            match ($rule->action_type) {
                WhatsAppAutomationAction::AUTO_REPLY => $this->executeAutoReply($rule, $conversation),
                WhatsAppAutomationAction::ASSIGN_USER => $this->executeAssignUser($rule, $conversation),
                WhatsAppAutomationAction::ASSIGN_TEAM => $this->executeAssignTeam($rule, $conversation),
                WhatsAppAutomationAction::ADD_TAG => $this->executeAddTag($rule, $conversation),
                WhatsAppAutomationAction::SEND_TEMPLATE => $this->executeSendTemplate($rule, $conversation),
            };

            $rule->incrementExecution();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp automation rule execution failed', [
                'rule_id' => $rule->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return LengthAwarePaginator<WhatsAppAutomationRule>
     */
    public function listForWorkspace(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppAutomationRule::forWorkspace($workspaceId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderByDesc('priority')->paginate($filters['per_page'] ?? 15);
    }

    private function matchesTrigger(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation, WhatsAppMessage $message): bool
    {
        return match ($rule->trigger_type) {
            WhatsAppAutomationTrigger::NEW_CONVERSATION => $conversation->message_count <= 1,
            WhatsAppAutomationTrigger::KEYWORD_MATCH => $this->matchesKeywords($rule, $message),
            WhatsAppAutomationTrigger::OUTSIDE_BUSINESS_HOURS => $this->isOutsideBusinessHours($rule),
            WhatsAppAutomationTrigger::NO_RESPONSE_TIMEOUT => false, // Handled by scheduled job
        };
    }

    private function matchesKeywords(WhatsAppAutomationRule $rule, WhatsAppMessage $message): bool
    {
        $keywords = $rule->trigger_conditions['keywords'] ?? [];
        $text = strtolower($message->content_text ?? '');

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function isOutsideBusinessHours(WhatsAppAutomationRule $rule): bool
    {
        $conditions = $rule->trigger_conditions;
        $timezone = $conditions['timezone'] ?? 'UTC';
        $now = now($timezone);
        $start = $conditions['business_hours']['start'] ?? '09:00';
        $end = $conditions['business_hours']['end'] ?? '18:00';

        $currentTime = $now->format('H:i');

        return $currentTime < $start || $currentTime >= $end;
    }

    private function executeAutoReply(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation): void
    {
        $replyText = $rule->action_params['reply_text'] ?? '';
        if (empty($replyText)) {
            return;
        }

        $phone = $conversation->phoneNumber;
        $this->messagingService->sendTextMessage($phone, $conversation, $replyText);
    }

    private function executeAssignUser(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation): void
    {
        $userId = $rule->action_params['user_id'] ?? null;
        if ($userId) {
            $conversation->update(['assigned_to_user_id' => $userId]);
        }
    }

    private function executeAssignTeam(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation): void
    {
        $teamName = $rule->action_params['team_name'] ?? null;
        if ($teamName) {
            $conversation->update(['assigned_to_team' => $teamName]);
        }
    }

    private function executeAddTag(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation): void
    {
        $tag = $rule->action_params['tag'] ?? null;
        if ($tag) {
            $tags = $conversation->tags ?? [];
            if (! in_array($tag, $tags, true)) {
                $tags[] = $tag;
                $conversation->update(['tags' => $tags]);
            }
        }
    }

    private function executeSendTemplate(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation): void
    {
        // Template sending requires the template model — delegates to messaging service
        $templateId = $rule->action_params['template_id'] ?? null;
        if (! $templateId) {
            return;
        }

        $template = \App\Models\WhatsApp\WhatsAppTemplate::find($templateId);
        if (! $template || ! $template->isApproved()) {
            return;
        }

        $phone = $conversation->phoneNumber;
        $params = $rule->action_params['template_params'] ?? [];
        $this->messagingService->sendTemplateMessage($phone, $conversation->customer_phone, $template, $params);
    }
}
