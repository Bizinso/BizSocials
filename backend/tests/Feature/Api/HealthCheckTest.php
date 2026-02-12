<?php

declare(strict_types=1);

/**
 * Health Check API Feature Tests
 *
 * Tests for the health check endpoint which provides
 * a simple way to verify the API is running.
 *
 * @see routes/api/v1.php
 */
test('health check endpoint returns 200 status', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200);
});

test('health check endpoint returns correct structure', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'version',
            'timestamp',
        ]);
});

test('health check endpoint returns ok status', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'version' => 'v1',
        ]);
});

test('health check endpoint returns valid timestamp', function (): void {
    $response = $this->getJson('/api/v1/health');

    $data = $response->json();

    expect($data['timestamp'])->toBeString()
        ->and(strtotime($data['timestamp']))->not->toBeFalse();
});

test('api root endpoint returns api information', function (): void {
    $response = $this->getJson('/api');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'name',
            'version',
            'docs',
            'status',
        ])
        ->assertJson([
            'name' => 'BizSocials API',
            'version' => 'v1',
            'status' => 'operational',
        ]);
});

test('api root endpoint includes docs url', function (): void {
    $response = $this->getJson('/api');

    $data = $response->json();

    expect($data['docs'])->toBeString()
        ->and($data['docs'])->toContain('/docs/api');
});

test('health check endpoint accepts get request only', function (): void {
    $this->getJson('/api/v1/health')->assertStatus(200);

    // POST should return 405 Method Not Allowed
    $this->postJson('/api/v1/health')->assertStatus(405);
});

test('non-existent api route returns 404 with json', function (): void {
    $response = $this->getJson('/api/v1/non-existent-route');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ]);
});
