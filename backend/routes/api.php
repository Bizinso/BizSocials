<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here are the main API routes for the BizSocials application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Broadcasting authentication routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Testing routes (only available in non-production)
if (!app()->environment('production')) {
    require base_path('routes/api/testing.php');
}

// Root endpoint - API information
Route::get('/', fn () => response()->json([
    'name' => 'BizSocials API',
    'version' => 'v1',
    'docs' => url('/docs/api'),
    'status' => 'operational',
]));
