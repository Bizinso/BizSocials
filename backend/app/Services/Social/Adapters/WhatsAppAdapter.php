<?php

declare(strict_types=1);

namespace App\Services\Social\Adapters;

use App\Data\Social\OAuthTokenData;
use App\Enums\Inbox\InboxItemType;
use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

final class WhatsAppAdapter implements SocialPlatformAdapter
{
    private const GRAPH_BASE = 'https://graph.facebook.com/v19.0';

    public function __construct(
        private readonly Client $client,
    ) {}

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        // Exchange Facebook Login code for access token
        $response = $this->client->get(self::GRAPH_BASE . '/oauth/access_token', [
            'query' => [
                'client_id' => config('services.whatsapp.app_id'),
                'client_secret' => config('services.whatsapp.app_secret'),
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $accessToken = $data['access_token'] ?? '';

        // Fetch WABA info
        $wabaResponse = $this->client->get(self::GRAPH_BASE . '/me/businesses', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            'query' => ['fields' => 'id,name,owned_whatsapp_business_accounts{id,name}'],
        ]);

        $wabaData = json_decode($wabaResponse->getBody()->getContents(), true);
        $businesses = $wabaData['data'] ?? [];
        $business = $businesses[0] ?? [];
        $wabas = $business['owned_whatsapp_business_accounts']['data'] ?? [];
        $waba = $wabas[0] ?? [];

        return new OAuthTokenData(
            access_token: $accessToken,
            refresh_token: null,
            expires_in: null, // System user tokens don't expire
            platform_account_id: $waba['id'] ?? '',
            account_name: $waba['name'] ?? 'WhatsApp Business',
            account_username: null,
            profile_image_url: null,
            metadata: [
                'waba_id' => $waba['id'] ?? '',
                'business_id' => $business['id'] ?? '',
            ],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        // WhatsApp system user tokens don't expire — validate the token is still active
        try {
            $response = $this->client->get(self::GRAPH_BASE . '/debug_token', [
                'query' => [
                    'input_token' => $refreshToken,
                    'access_token' => $refreshToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $tokenData = $data['data'] ?? [];

            return new OAuthTokenData(
                access_token: $refreshToken,
                refresh_token: null,
                expires_in: null,
                platform_account_id: $tokenData['app_id'] ?? '',
                account_name: 'WhatsApp Business',
                account_username: null,
                profile_image_url: null,
                metadata: null,
            );
        } catch (GuzzleException $e) {
            throw new \RuntimeException('WhatsApp token validation failed: ' . $e->getMessage());
        }
    }

    public function revokeToken(string $accessToken): void
    {
        // Mark account as disconnected — WhatsApp system tokens can't be individually revoked
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        // For WhatsApp, "publishing" means sending a template broadcast
        // This is handled by WhatsAppCampaignService in W2, not the standard adapter
        return PublishResult::failure(
            errorCode: 'WHATSAPP_USE_TEMPLATE',
            errorMessage: 'WhatsApp requires template-based messaging. Use the WhatsApp campaign system instead.',
        );
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        // WhatsApp is webhook-driven — return locally stored messages
        $query = WhatsAppMessage::whereHas('conversation', function ($q) use ($account) {
            $q->where('whatsapp_phone_number_id', $account->platform_account_id);
        })
            ->where('direction', WhatsAppMessageDirection::INBOUND);

        if ($since !== null) {
            $query->where('platform_timestamp', '>=', $since);
        }

        return $query->orderByDesc('platform_timestamp')
            ->limit(50)
            ->get()
            ->map(fn (WhatsAppMessage $msg) => [
                'platform_item_id' => $msg->wamid ?? $msg->id,
                'platform_post_id' => null,
                'post_target_id' => null,
                'type' => InboxItemType::WHATSAPP_MESSAGE,
                'author_name' => $msg->conversation?->customer_name ?? $msg->conversation?->customer_phone ?? 'Unknown',
                'author_username' => $msg->conversation?->customer_phone,
                'author_profile_url' => null,
                'author_avatar_url' => null,
                'content_text' => $msg->content_text ?? '',
                'platform_created_at' => $msg->platform_timestamp->toIso8601String(),
                'metadata' => [
                    'conversation_id' => $msg->conversation_id,
                    'message_type' => $msg->type->value,
                ],
            ])
            ->toArray();
    }

    public function fetchPostMetrics(SocialAccount $account, string $externalPostId): array
    {
        // Return message delivery aggregates
        $conversation = \App\Models\WhatsApp\WhatsAppConversation::where('whatsapp_phone_number_id', $account->platform_account_id)
            ->first();

        if ($conversation === null) {
            return $this->emptyMetrics();
        }

        $messages = $conversation->messages()->where('direction', WhatsAppMessageDirection::OUTBOUND);

        return [
            'impressions' => 0,
            'reach' => 0,
            'engagements' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
            'sent_count' => (clone $messages)->where('status', 'sent')->count(),
            'delivered_count' => (clone $messages)->where('status', 'delivered')->count(),
            'read_count' => (clone $messages)->where('status', 'read')->count(),
            'failed_count' => (clone $messages)->where('status', 'failed')->count(),
        ];
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get(self::GRAPH_BASE . '/me', [
                'query' => [
                    'fields' => 'id,name',
                    'access_token' => $accessToken,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException) {
            return [];
        }
    }

    private function emptyMetrics(): array
    {
        return [
            'impressions' => 0,
            'reach' => 0,
            'engagements' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
        ];
    }
}
