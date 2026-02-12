<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Log service activity.
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('service')->{$level}(
            sprintf('[%s] %s', static::class, $message),
            $context
        );
    }

    /**
     * Handle and log service exceptions.
     */
    protected function handleException(Throwable $e, string $context = ''): never
    {
        $this->log($context ?: $e->getMessage(), [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 'error');

        throw $e;
    }
}
