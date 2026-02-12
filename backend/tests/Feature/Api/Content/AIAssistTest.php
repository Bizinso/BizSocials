<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config(['services.openai.api_key' => 'test-key']);
    config(['services.openai.model' => 'gpt-4o-mini']);

    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->user, WorkspaceRole::ADMIN);

    Sanctum::actingAs($this->user);
});

describe('AI Assist - Generate Caption', function () {
    it('generates a caption successfully', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Check out our amazing new product! 🚀']],
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/caption", [
            'topic' => 'new product launch',
            'platform' => 'instagram',
            'tone' => 'exciting',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'Check out our amazing new product! 🚀');
    });

    it('requires topic and platform', function () {
        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/caption", []);

        $response->assertUnprocessable();
    });
});

describe('AI Assist - Suggest Hashtags', function () {
    it('suggests hashtags successfully', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '["#marketing", "#socialmedia", "#growth"]']],
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/hashtags", [
            'content' => 'Tips for growing your business online',
            'platform' => 'twitter',
            'count' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.hashtags');
    });
});

describe('AI Assist - Improve Content', function () {
    it('improves content successfully', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Improved version of the post']],
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/improve", [
            'content' => 'Original post text',
            'instruction' => 'Make it more engaging',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Improved version of the post');
    });
});

describe('AI Assist - Generate Ideas', function () {
    it('generates post ideas successfully', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '["Idea 1", "Idea 2", "Idea 3"]']],
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/ideas", [
            'topic' => 'digital marketing',
            'platform' => 'linkedin',
            'count' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.ideas');
    });
});

describe('AI Assist - Error Handling', function () {
    it('returns 503 when API key is not configured', function () {
        config(['services.openai.api_key' => '']);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/caption", [
            'topic' => 'test',
            'platform' => 'instagram',
        ]);

        $response->assertStatus(503);
    });

    it('requires authentication', function () {
        // Reset auth
        $this->app['auth']->forgetGuards();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/ai-assist/caption", [
            'topic' => 'test',
            'platform' => 'instagram',
        ]);

        $response->assertUnauthorized();
    });
});
