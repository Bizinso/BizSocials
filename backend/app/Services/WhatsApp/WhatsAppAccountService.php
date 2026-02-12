<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\WhatsApp\WebhookSubscription;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\BaseService;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class WhatsAppAccountService extends BaseService
{
    private const API_BASE = 'https://graph.facebook.com/v19.0/';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => self::API_BASE, 'timeout' => 30]);
    }

    public function onboard(Tenant $tenant, string $metaAccessToken): WhatsAppBusinessAccount
    {
        return $this->transaction(function () use ($tenant, $metaAccessToken) {
            $wabaInfo = $this->fetchWabaInfo($metaAccessToken);

            $waba = WhatsAppBusinessAccount::create([
                'tenant_id' => $tenant->id,
                'meta_business_account_id' => $wabaInfo['business_id'] ?? '',
                'waba_id' => $wabaInfo['id'],
                'name' => $wabaInfo['name'] ?? $tenant->name,
                'status' => WhatsAppAccountStatus::PENDING_VERIFICATION,
                'quality_rating' => WhatsAppQualityRating::UNKNOWN,
                'access_token_encrypted' => $metaAccessToken,
                'webhook_verify_token' => Str::random(32),
                'webhook_subscribed_fields' => ['messages', 'message_status', 'template_status', 'account_updates'],
                'is_marketing_enabled' => false,
            ]);

            $this->fetchPhoneNumbers($waba);
            $this->registerWebhooks($waba);

            $this->log('WhatsApp Business Account onboarded', [
                'tenant_id' => $tenant->id,
                'waba_id' => $waba->waba_id,
            ]);

            return $waba->load('phoneNumbers');
        });
    }

    public function fetchPhoneNumbers(WhatsAppBusinessAccount $waba): Collection
    {
        $response = $this->client->get($waba->waba_id . '/phone_numbers', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $phones = collect();

        foreach ($data['data'] ?? [] as $phoneData) {
            $phone = WhatsAppPhoneNumber::updateOrCreate(
                ['phone_number_id' => $phoneData['id']],
                [
                    'whatsapp_business_account_id' => $waba->id,
                    'phone_number' => $phoneData['display_phone_number'] ?? '',
                    'display_name' => $phoneData['verified_name'] ?? $phoneData['display_phone_number'] ?? '',
                    'verified_name' => $phoneData['verified_name'] ?? null,
                    'quality_rating' => WhatsAppQualityRating::tryFrom($phoneData['quality_rating'] ?? '') ?? WhatsAppQualityRating::GREEN,
                    'is_primary' => $phones->isEmpty(),
                ],
            );
            $phones->push($phone);
        }

        return $phones;
    }

    public function registerWebhooks(WhatsAppBusinessAccount $waba): void
    {
        WebhookSubscription::updateOrCreate(
            ['platform' => 'whatsapp', 'platform_account_id' => $waba->waba_id],
            [
                'tenant_id' => $waba->tenant_id,
                'verify_token' => $waba->webhook_verify_token,
                'subscribed_fields' => $waba->webhook_subscribed_fields,
                'is_active' => true,
            ],
        );

        try {
            $this->client->post(config('services.whatsapp.app_id', 'APP_ID') . '/subscriptions', [
                'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
                'json' => [
                    'object' => 'whatsapp_business_account',
                    'callback_url' => config('app.url') . '/api/v1/webhooks/whatsapp',
                    'verify_token' => $waba->webhook_verify_token,
                    'fields' => implode(',', $waba->webhook_subscribed_fields ?? []),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->log('Failed to register Meta webhook', ['error' => $e->getMessage()], 'warning');
        }
    }

    public function verifyWebhookChallenge(string $mode, string $token, string $challenge): ?string
    {
        if ($mode !== 'subscribe') {
            return null;
        }

        $subscription = WebhookSubscription::where('platform', 'whatsapp')
            ->where('verify_token', $token)
            ->where('is_active', true)
            ->first();

        return $subscription !== null ? $challenge : null;
    }

    public function updateBusinessProfile(WhatsAppPhoneNumber $phone, array $profile): void
    {
        $waba = $phone->businessAccount;

        $this->client->post($phone->phone_number_id . '/whatsapp_business_profile', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => array_filter([
                'description' => $profile['description'] ?? null,
                'address' => $profile['address'] ?? null,
                'websites' => isset($profile['website']) ? [$profile['website']] : null,
                'email' => $profile['support_email'] ?? null,
            ]),
        ]);

        $phone->update(array_filter([
            'description' => $profile['description'] ?? null,
            'address' => $profile['address'] ?? null,
            'website' => $profile['website'] ?? null,
            'support_email' => $profile['support_email'] ?? null,
        ]));
    }

    public function acceptCompliance(WhatsAppBusinessAccount $waba, User $user): void
    {
        $waba->update([
            'compliance_accepted_at' => now(),
            'compliance_accepted_by_user_id' => $user->id,
        ]);

        $this->log('WhatsApp compliance accepted', ['waba_id' => $waba->id, 'user_id' => $user->id]);
    }

    public function suspendAccount(WhatsAppBusinessAccount $waba, string $reason): void
    {
        $waba->update([
            'status' => WhatsAppAccountStatus::SUSPENDED,
            'suspended_reason' => $reason,
            'is_marketing_enabled' => false,
        ]);

        $this->log('WhatsApp account suspended', ['waba_id' => $waba->id, 'reason' => $reason]);
    }

    private function fetchWabaInfo(string $accessToken): array
    {
        $response = $this->client->get('me/businesses', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            'query' => ['fields' => 'id,name,owned_whatsapp_business_accounts{id,name,currency,timezone_id}'],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $businesses = $data['data'] ?? [];

        if (empty($businesses)) {
            throw new \RuntimeException('No business accounts found');
        }

        $business = $businesses[0];
        $wabas = $business['owned_whatsapp_business_accounts']['data'] ?? [];

        if (empty($wabas)) {
            throw new \RuntimeException('No WhatsApp Business Accounts found');
        }

        return array_merge($wabas[0], ['business_id' => $business['id']]);
    }
}
