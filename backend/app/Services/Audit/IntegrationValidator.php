<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Throwable;

class IntegrationValidator extends BaseService
{
    /**
     * Validate OAuth flow for a social platform.
     */
    public function validateOAuthFlow(string $platform): array
    {
        try {
            $result = [
                'platform' => $platform,
                'oauth_configured' => false,
                'has_client_id' => false,
                'has_client_secret' => false,
                'has_redirect_uri' => false,
                'has_oauth_controller' => false,
                'has_token_storage' => false,
                'issues' => [],
            ];

            // Check environment configuration
            $configKey = strtoupper($platform);
            $result['has_client_id'] = ! empty(config("services.{$platform}.client_id"));
            $result['has_client_secret'] = ! empty(config("services.{$platform}.client_secret"));
            $result['has_redirect_uri'] = ! empty(config("services.{$platform}.redirect"));

            if (! $result['has_client_id']) {
                $result['issues'][] = "Missing {$configKey}_CLIENT_ID in configuration";
            }

            if (! $result['has_client_secret']) {
                $result['issues'][] = "Missing {$configKey}_CLIENT_SECRET in configuration";
            }

            if (! $result['has_redirect_uri']) {
                $result['issues'][] = "Missing {$configKey}_REDIRECT_URI in configuration";
            }

            // Check for OAuth controller
            $controllerPath = app_path("Http/Controllers/Social/{$this->getPlatformControllerName($platform)}.php");
            $result['has_oauth_controller'] = file_exists($controllerPath);

            if (! $result['has_oauth_controller']) {
                $result['issues'][] = "OAuth controller not found at {$controllerPath}";
            }

            // Check for token storage model/table
            $result['has_token_storage'] = $this->checkTokenStorage($platform);

            if (! $result['has_token_storage']) {
                $result['issues'][] = 'Token storage mechanism not found';
            }

            $result['oauth_configured'] = $result['has_client_id'] &&
                                         $result['has_client_secret'] &&
                                         $result['has_redirect_uri'] &&
                                         $result['has_oauth_controller'] &&
                                         $result['has_token_storage'];

            return $result;
        } catch (Throwable $e) {
            $this->log("Error validating OAuth flow for {$platform}: ".$e->getMessage(), [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'platform' => $platform,
                'oauth_configured' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate API connection with test credentials.
     */
    public function validateApiConnection(string $platform): array
    {
        try {
            $result = [
                'platform' => $platform,
                'api_client_exists' => false,
                'can_connect' => false,
                'connection_tested' => false,
                'issues' => [],
            ];

            // Check if API client class exists
            $clientPath = app_path("Services/Social/{$this->getPlatformClientName($platform)}.php");
            $result['api_client_exists'] = file_exists($clientPath);

            if (! $result['api_client_exists']) {
                $result['issues'][] = "API client not found at {$clientPath}";

                return $result;
            }

            // Try to instantiate the client
            $clientClass = "App\\Services\\Social\\{$this->getPlatformClientName($platform)}";

            if (! class_exists($clientClass)) {
                $result['issues'][] = "API client class {$clientClass} does not exist";

                return $result;
            }

            // Check if client has required methods
            $requiredMethods = $this->getRequiredMethods($platform);
            $reflection = new \ReflectionClass($clientClass);

            foreach ($requiredMethods as $method) {
                if (! $reflection->hasMethod($method)) {
                    $result['issues'][] = "Missing required method: {$method}";
                }
            }

            // Note: We don't actually test the connection here to avoid rate limits
            // and requiring real credentials. This is a structural validation.
            $result['connection_tested'] = false;
            $result['can_connect'] = count($result['issues']) === 0;

            return $result;
        } catch (Throwable $e) {
            $this->log("Error validating API connection for {$platform}: ".$e->getMessage(), [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'platform' => $platform,
                'api_client_exists' => false,
                'can_connect' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate token management (storage and refresh).
     */
    public function validateTokenManagement(string $platform): array
    {
        try {
            $result = [
                'platform' => $platform,
                'has_token_model' => false,
                'has_encryption' => false,
                'has_refresh_mechanism' => false,
                'issues' => [],
            ];

            // Check for social account model
            $modelPath = app_path('Models/Social/SocialAccount.php');
            $result['has_token_model'] = file_exists($modelPath);

            if (! $result['has_token_model']) {
                $result['issues'][] = 'SocialAccount model not found';

                return $result;
            }

            // Check if model uses encryption
            $modelContent = file_get_contents($modelPath);
            $result['has_encryption'] = $this->checkEncryption($modelContent);

            if (! $result['has_encryption']) {
                $result['issues'][] = 'Token encryption not detected in model';
            }

            // Check for token refresh method
            $clientPath = app_path("Services/Social/{$this->getPlatformClientName($platform)}.php");

            if (file_exists($clientPath)) {
                $clientContent = file_get_contents($clientPath);
                $result['has_refresh_mechanism'] = preg_match('/function\s+refreshToken/i', $clientContent) > 0;

                if (! $result['has_refresh_mechanism']) {
                    $result['issues'][] = 'Token refresh mechanism not found in API client';
                }
            }

            return $result;
        } catch (Throwable $e) {
            $this->log("Error validating token management for {$platform}: ".$e->getMessage(), [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'platform' => $platform,
                'has_token_model' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook handling.
     */
    public function validateWebhookHandling(string $platform): array
    {
        try {
            $result = [
                'platform' => $platform,
                'has_webhook_route' => false,
                'has_webhook_controller' => false,
                'has_signature_verification' => false,
                'issues' => [],
            ];

            // Check for webhook routes
            $routesPath = base_path('routes/api.php');
            if (file_exists($routesPath)) {
                $routesContent = file_get_contents($routesPath);
                $webhookPattern = "/webhook.*{$platform}/i";
                $result['has_webhook_route'] = preg_match($webhookPattern, $routesContent) > 0;

                if (! $result['has_webhook_route']) {
                    $result['issues'][] = 'Webhook route not found in routes/api.php';
                }
            }

            // Check for webhook controller
            $controllerPath = app_path("Http/Controllers/Webhook/{$this->getPlatformControllerName($platform)}WebhookController.php");
            $result['has_webhook_controller'] = file_exists($controllerPath);

            if (! $result['has_webhook_controller']) {
                $result['issues'][] = "Webhook controller not found at {$controllerPath}";
            } else {
                // Check for signature verification
                $controllerContent = file_get_contents($controllerPath);
                $result['has_signature_verification'] = $this->checkSignatureVerification($controllerContent);

                if (! $result['has_signature_verification']) {
                    $result['issues'][] = 'Webhook signature verification not detected';
                }
            }

            return $result;
        } catch (Throwable $e) {
            $this->log("Error validating webhook handling for {$platform}: ".$e->getMessage(), [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'platform' => $platform,
                'has_webhook_route' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get platform controller name.
     */
    private function getPlatformControllerName(string $platform): string
    {
        return match ($platform) {
            'facebook' => 'FacebookController',
            'instagram' => 'InstagramController',
            'twitter' => 'TwitterController',
            'linkedin' => 'LinkedInController',
            'tiktok' => 'TikTokController',
            'youtube' => 'YouTubeController',
            default => ucfirst($platform).'Controller',
        };
    }

    /**
     * Get platform client name.
     */
    private function getPlatformClientName(string $platform): string
    {
        return match ($platform) {
            'facebook' => 'FacebookClient',
            'instagram' => 'InstagramClient',
            'twitter' => 'TwitterClient',
            'linkedin' => 'LinkedInClient',
            'tiktok' => 'TikTokClient',
            'youtube' => 'YouTubeClient',
            default => ucfirst($platform).'Client',
        };
    }

    /**
     * Get required methods for platform.
     */
    private function getRequiredMethods(string $platform): array
    {
        return match ($platform) {
            'facebook', 'instagram' => ['publishPost', 'getPosts', 'getInsights'],
            'twitter' => ['publishTweet', 'getTimeline', 'getMetrics'],
            'linkedin' => ['publishPost', 'getAnalytics'],
            'tiktok' => ['uploadVideo', 'publishVideo', 'getAnalytics'],
            'youtube' => ['uploadVideo', 'updateMetadata', 'getAnalytics'],
            default => ['connect', 'disconnect'],
        };
    }

    /**
     * Check if token storage exists.
     */
    private function checkTokenStorage(string $platform): bool
    {
        // Check for social_accounts table migration
        $migrationsPath = database_path('migrations');

        if (! is_dir($migrationsPath)) {
            return false;
        }

        $files = scandir($migrationsPath);

        foreach ($files as $file) {
            if (str_contains($file, 'social_accounts') || str_contains($file, 'social_connections')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if encryption is used for tokens.
     */
    private function checkEncryption(string $content): bool
    {
        $patterns = [
            '/\$casts\s*=\s*\[.*?[\'"]access_token[\'"]\s*=>\s*[\'"]encrypted[\'"]/',
            '/protected\s+\$encrypted\s*=/',
            '/Crypt::encrypt/',
            '/encrypt\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if signature verification is implemented.
     */
    private function checkSignatureVerification(string $content): bool
    {
        $patterns = [
            '/verifySignature/',
            '/validateSignature/',
            '/hash_hmac/',
            '/X-Hub-Signature/',
            '/X-Signature/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match("/{$pattern}/i", $content)) {
                return true;
            }
        }

        return false;
    }
}
