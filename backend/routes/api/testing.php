<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Testing\TestDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Testing API Routes
|--------------------------------------------------------------------------
|
| These routes are only available in non-production environments.
| They provide endpoints for E2E tests to seed and cleanup test data.
|
*/

Route::prefix('v1/testing')->middleware(['testing'])->group(function () {
    // Test data creation endpoints
    Route::post('/users', [TestDataController::class, 'createUser']);
    Route::post('/posts', [TestDataController::class, 'createPosts']);
    Route::post('/inbox-items', [TestDataController::class, 'createInboxItems']);
    Route::post('/tickets', [TestDataController::class, 'createTickets']);
    Route::post('/social-accounts', [TestDataController::class, 'createSocialAccounts']);
    
    // Test data cleanup endpoints
    Route::post('/cleanup', [TestDataController::class, 'cleanup']);
    Route::post('/reset', [TestDataController::class, 'reset']);
});
