<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use App\Exceptions\WhatsAppAuthenticationException;
use App\Exceptions\WhatsAppRateLimitException;
use App\Exceptions\WhatsAppServerException;
use App\Exceptions\WhatsAppValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * WhatsApp Business API Client
 * 
 * Centralized client for all WhatsApp Business API interactions
 * with authentication, error handling, and rate limiting.
 */
class WhatsAppClient
{
    private const API_BASE = 'https://graph.facebook.com/v19.0/';
    private const RATE_LIMIT_WINDOW = 60; // seconds
    private const MAX_REQUESTS_PER_WINDOW = 80; // WhatsApp Cloud API limit
    
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::API_BASE,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false, // Handle errors manually
        ]);
    }
    
    /**
     * Send a text message
     */
    public function sendTextMessage(
        string $phoneNumberId,
        string $accessToken,
        string $recipientPhone,
        string $text
    ): array {
        $this->checkRateLimit($phoneNumberId);
        
        return $this->post(
            "{$phoneNumberId}/messages",
            $accessToken,
            [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => 'text',
                'text' => ['body' => $text],
            ]
        );
    }
    
    /**
     * Send a media message (image, video, audio, document)
     */
    public function sendMediaMessage(
        string $phoneNumberId,
        string $accessToken,
        string $recipientPhone,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null
    ): array {
        $this->checkRateLimit($phoneNumberId);
        
        $mediaPayload = ['link' => $mediaUrl];
        if ($caption !== null) {
            $mediaPayload['caption'] = $caption;
        }
        
        return $this->post(
            "{$phoneNumberId}/messages",
            $accessToken,
            [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => $mediaType,
                $mediaType => $mediaPayload,
            ]
        );
    }
    
    /**
     * Send a template message
     */
    public function sendTemplateMessage(
        string $phoneNumberId,
        string $accessToken,
        string $recipientPhone,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        $this->checkRateLimit($phoneNumberId);
        
        return $this->post(
            "{$phoneNumberId}/messages",
            $accessToken,
            [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => $components,
                ],
            ]
        );
    }
    
    /**
     * Mark a message as read
     */
    public function markMessageAsRead(
        string $phoneNumberId,
        string $accessToken,
        string $messageId
    ): array {
        return $this->post(
            "{$phoneNumberId}/messages",
            $accessToken,
            [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]
        );
    }
    
    /**
     * Upload media file
     */
    public function uploadMedia(
        string $phoneNumberId,
        string $accessToken,
        string $filePath,
        string $mimeType,
        string $filename
    ): array {
        $this->checkRateLimit($phoneNumberId);
        
        $response = $this->client->post("{$phoneNumberId}/media", [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $filename,
                ],
                [
                    'name' => 'type',
                    'contents' => $mimeType,
                ],
                [
                    'name' => 'messaging_product',
                    'contents' => 'whatsapp',
                ],
            ],
        ]);
        
        return $this->handleResponse($response);
    }
    
    /**
     * Get media URL
     */
    public function getMediaUrl(string $mediaId, string $accessToken): array
    {
        return $this->get($mediaId, $accessToken);
    }
    
    /**
     * Get phone numbers for a WhatsApp Business Account
     */
    public function getPhoneNumbers(string $wabaId, string $accessToken): array
    {
        return $this->get("{$wabaId}/phone_numbers", $accessToken);
    }
    
    /**
     * Get WhatsApp Business Account info
     */
    public function getBusinessAccountInfo(string $wabaId, string $accessToken): array
    {
        return $this->get($wabaId, $accessToken, [
            'fields' => 'id,name,currency,timezone_id,message_template_namespace',
        ]);
    }
    
    /**
     * Update business profile
     */
    public function updateBusinessProfile(
        string $phoneNumberId,
        string $accessToken,
        array $profile
    ): array {
        return $this->post(
            "{$phoneNumberId}/whatsapp_business_profile",
            $accessToken,
            array_filter([
                'description' => $profile['description'] ?? null,
                'address' => $profile['address'] ?? null,
                'websites' => isset($profile['website']) ? [$profile['website']] : null,
                'email' => $profile['email'] ?? null,
            ])
        );
    }
    
    /**
     * Get message templates
     */
    public function getMessageTemplates(string $wabaId, string $accessToken): array
    {
        return $this->get("{$wabaId}/message_templates", $accessToken, [
            'fields' => 'name,status,category,language,components',
        ]);
    }
    
    /**
     * Create message template
     */
    public function createMessageTemplate(
        string $wabaId,
        string $accessToken,
        array $templateData
    ): array {
        return $this->post("{$wabaId}/message_templates", $accessToken, $templateData);
    }
    
    /**
     * Delete message template
     */
    public function deleteMessageTemplate(
        string $wabaId,
        string $accessToken,
        string $templateName
    ): array {
        return $this->delete("{$wabaId}/message_templates", $accessToken, [
            'name' => $templateName,
        ]);
    }
    
    /**
     * Register webhook subscription
     */
    public function registerWebhook(
        string $appId,
        string $accessToken,
        string $callbackUrl,
        string $verifyToken,
        array $fields
    ): array {
        return $this->post(
            "{$appId}/subscriptions",
            $accessToken,
            [
                'object' => 'whatsapp_business_account',
                'callback_url' => $callbackUrl,
                'verify_token' => $verifyToken,
                'fields' => implode(',', $fields),
            ]
        );
    }
    
    /**
     * Generic GET request
     */
    private function get(string $endpoint, string $accessToken, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => $query,
            ]);
            
            return $this->handleResponse($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                return $this->handleResponse($e->getResponse());
            }
            throw new WhatsAppApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Generic POST request
     */
    private function post(string $endpoint, string $accessToken, array $data): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);
            
            return $this->handleResponse($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                return $this->handleResponse($e->getResponse());
            }
            throw new WhatsAppApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Generic DELETE request
     */
    private function delete(string $endpoint, string $accessToken, array $query = []): array
    {
        try {
            $response = $this->client->delete($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => $query,
            ]);
            
            return $this->handleResponse($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                return $this->handleResponse($e->getResponse());
            }
            throw new WhatsAppApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Handle API response and errors
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        // Success responses (2xx)
        if ($statusCode >= 200 && $statusCode < 300) {
            return $data ?? [];
        }
        
        // Error responses
        $error = $data['error'] ?? [];
        $errorCode = $error['code'] ?? $statusCode;
        $errorMessage = $error['message'] ?? 'Unknown WhatsApp API error';
        $errorType = $error['type'] ?? 'APIError';
        
        Log::error('WhatsApp API Error', [
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'response_body' => $body,
        ]);
        
        // Handle specific error types
        throw match (true) {
            $statusCode === 429 => new WhatsAppRateLimitException(
                "Rate limit exceeded: {$errorMessage}",
                $errorCode
            ),
            $statusCode === 401 || $statusCode === 403 => new WhatsAppAuthenticationException(
                "Authentication failed: {$errorMessage}",
                $errorCode
            ),
            $statusCode === 400 => new WhatsAppValidationException(
                "Validation error: {$errorMessage}",
                $errorCode
            ),
            $statusCode >= 500 => new WhatsAppServerException(
                "WhatsApp server error: {$errorMessage}",
                $errorCode
            ),
            default => new WhatsAppApiException(
                "WhatsApp API error: {$errorMessage}",
                $errorCode
            ),
        };
    }
    
    /**
     * Check and enforce rate limiting
     */
    private function checkRateLimit(string $phoneNumberId): void
    {
        $cacheKey = "whatsapp_rate_limit:{$phoneNumberId}";
        $requestCount = (int) Cache::get($cacheKey, 0);
        
        if ($requestCount >= self::MAX_REQUESTS_PER_WINDOW) {
            throw new WhatsAppRateLimitException(
                'Rate limit exceeded. Please try again later.',
                429
            );
        }
        
        // Increment counter
        Cache::put(
            $cacheKey,
            $requestCount + 1,
            now()->addSeconds(self::RATE_LIMIT_WINDOW)
        );
    }
    
    /**
     * Get current rate limit status
     */
    public function getRateLimitStatus(string $phoneNumberId): array
    {
        $cacheKey = "whatsapp_rate_limit:{$phoneNumberId}";
        $requestCount = (int) Cache::get($cacheKey, 0);
        $remaining = max(0, self::MAX_REQUESTS_PER_WINDOW - $requestCount);
        
        return [
            'limit' => self::MAX_REQUESTS_PER_WINDOW,
            'remaining' => $remaining,
            'used' => $requestCount,
            'window_seconds' => self::RATE_LIMIT_WINDOW,
        ];
    }
}
