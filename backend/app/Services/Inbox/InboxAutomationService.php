<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Inbox\InboxAutomationAction;
use App\Enums\Inbox\InboxAutomationTrigger;
use App\Models\Inbox\InboxAutomationRule;
use App\Models\Inbox\InboxItem;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class InboxAutomationService extends BaseService
{
    /**
     * List automation rules for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = InboxAutomationRule::forWorkspace($workspace->id);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query->orderByDesc('priority')->orderBy('name')->paginate($perPage);
    }

    /**
     * Create a new automation rule.
     *
     * @param array<string, mixed> $data
     */
    public function create(Workspace $workspace, array $data): InboxAutomationRule
    {
        return $this->transaction(function () use ($workspace, $data): InboxAutomationRule {
            $rule = InboxAutomationRule::create([
                'workspace_id' => $workspace->id,
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? true,
                'trigger_type' => $data['trigger_type'],
                'trigger_conditions' => $data['trigger_conditions'] ?? null,
                'action_type' => $data['action_type'],
                'action_params' => $data['action_params'] ?? null,
                'priority' => $data['priority'] ?? 0,
            ]);

            $this->log('Inbox automation rule created', [
                'rule_id' => $rule->id,
                'workspace_id' => $workspace->id,
            ]);

            return $rule;
        });
    }

    /**
     * Update an existing automation rule.
     *
     * @param array<string, mixed> $data
     */
    public function update(InboxAutomationRule $rule, array $data): InboxAutomationRule
    {
        return $this->transaction(function () use ($rule, $data): InboxAutomationRule {
            $rule->update($data);

            $this->log('Inbox automation rule updated', [
                'rule_id' => $rule->id,
            ]);

            return $rule->fresh() ?? $rule;
        });
    }

    /**
     * Delete an automation rule.
     */
    public function delete(InboxAutomationRule $rule): void
    {
        $this->transaction(function () use ($rule): void {
            $rule->delete();

            $this->log('Inbox automation rule deleted', [
                'rule_id' => $rule->id,
            ]);
        });
    }

    /**
     * Evaluate all active rules for a workspace against an inbox item.
     */
    public function evaluateRules(InboxItem $item): void
    {
        $rules = InboxAutomationRule::forWorkspace($item->workspace_id)
            ->active()
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesTrigger($rule, $item)) {
                $this->executeRule($rule, $item);
            }
        }
    }

    /**
     * Execute a single automation rule against an inbox item.
     */
    public function executeRule(InboxAutomationRule $rule, InboxItem $item): void
    {
        $this->transaction(function () use ($rule, $item): void {
            match ($rule->action_type) {
                InboxAutomationAction::ASSIGN => $this->executeAssign($rule, $item),
                InboxAutomationAction::TAG => $this->executeTag($rule, $item),
                InboxAutomationAction::AUTO_REPLY => $this->executeAutoReply($rule, $item),
                InboxAutomationAction::ARCHIVE => $this->executeArchive($item),
            };

            $rule->increment('execution_count');

            $this->log('Automation rule executed', [
                'rule_id' => $rule->id,
                'inbox_item_id' => $item->id,
                'action_type' => $rule->action_type->value,
            ]);
        });
    }

    /**
     * Check if the rule's trigger matches the inbox item.
     */
    private function matchesTrigger(InboxAutomationRule $rule, InboxItem $item): bool
    {
        $conditions = $rule->trigger_conditions ?? [];

        return match ($rule->trigger_type) {
            InboxAutomationTrigger::NEW_ITEM => true,
            InboxAutomationTrigger::KEYWORD_MATCH => $this->matchesKeywords($item, $conditions),
            InboxAutomationTrigger::PLATFORM_MATCH => $this->matchesPlatform($item, $conditions),
        };
    }

    /**
     * Check if the inbox item content matches any of the specified keywords.
     *
     * @param array<string, mixed> $conditions
     */
    private function matchesKeywords(InboxItem $item, array $conditions): bool
    {
        $keywords = $conditions['keywords'] ?? [];

        if (empty($keywords)) {
            return false;
        }

        $content = strtolower($item->content_text);

        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower((string) $keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the inbox item is from a matching platform.
     *
     * @param array<string, mixed> $conditions
     */
    private function matchesPlatform(InboxItem $item, array $conditions): bool
    {
        $platforms = $conditions['platforms'] ?? [];

        if (empty($platforms)) {
            return false;
        }

        $item->loadMissing('socialAccount');

        if ($item->socialAccount === null) {
            return false;
        }

        return in_array($item->socialAccount->platform->value, $platforms, true);
    }

    /**
     * Execute the assign action.
     */
    private function executeAssign(InboxAutomationRule $rule, InboxItem $item): void
    {
        $params = $rule->action_params ?? [];
        $userId = $params['user_id'] ?? null;

        if ($userId !== null) {
            $item->update([
                'assigned_to_user_id' => $userId,
                'assigned_at' => now(),
            ]);
        }
    }

    /**
     * Execute the tag action.
     */
    private function executeTag(InboxAutomationRule $rule, InboxItem $item): void
    {
        $params = $rule->action_params ?? [];
        $tagId = $params['tag_id'] ?? null;

        if ($tagId !== null && !$item->tags()->where('tag_id', $tagId)->exists()) {
            $item->tags()->attach($tagId);
        }
    }

    /**
     * Execute the auto-reply action.
     */
    private function executeAutoReply(InboxAutomationRule $rule, InboxItem $item): void
    {
        // Auto-reply is a placeholder for future implementation.
        // Actual sending would require integration with the social platform API.
        $this->log('Auto-reply action triggered (not yet implemented)', [
            'rule_id' => $rule->id,
            'inbox_item_id' => $item->id,
        ]);
    }

    /**
     * Execute the archive action.
     */
    private function executeArchive(InboxItem $item): void
    {
        $item->archive();
    }
}
