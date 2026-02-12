<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Models\Social\SocialAccount;
use Illuminate\Support\Collection;

/**
 * Fake InboxService for testing.
 *
 * This stub allows testing jobs without mocking final classes.
 */
class FakeInboxService
{
    public bool $shouldFail = false;

    public string $failureMessage = 'Sync failed';

    /** @var array<string, bool> */
    public array $syncedAccounts = [];

    /** @var array<string, int> */
    public array $itemsCreated = [];

    public int $archivedCount = 0;

    public function syncForAccount(SocialAccount $account): int
    {
        if ($this->shouldFail) {
            throw new \RuntimeException($this->failureMessage);
        }

        $this->syncedAccounts[$account->id] = true;
        $count = $this->itemsCreated[$account->id] ?? 0;

        return $count;
    }

    public function archiveOldItems(int $days = 90): int
    {
        return $this->archivedCount;
    }

    /**
     * Configure fake to return specific item count for an account.
     */
    public function willCreateItems(string $accountId, int $count): self
    {
        $this->itemsCreated[$accountId] = $count;

        return $this;
    }

    /**
     * Configure the fake to simulate failure.
     */
    public function shouldFailWith(string $message = 'Sync failed'): self
    {
        $this->shouldFail = true;
        $this->failureMessage = $message;

        return $this;
    }

    /**
     * Configure the number of items to archive.
     */
    public function willArchive(int $count): self
    {
        $this->archivedCount = $count;

        return $this;
    }

    /**
     * Assert sync was called for an account.
     */
    public function assertSyncedAccount(SocialAccount $account): self
    {
        expect($this->syncedAccounts[$account->id] ?? false)
            ->toBeTrue("Expected sync to be called for account {$account->id}");

        return $this;
    }

    /**
     * Assert sync was not called for an account.
     */
    public function assertNotSyncedAccount(SocialAccount $account): self
    {
        expect($this->syncedAccounts[$account->id] ?? false)
            ->toBeFalse("Expected sync not to be called for account {$account->id}");

        return $this;
    }
}
