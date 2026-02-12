<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\ShortLink;
use App\Models\Content\ShortLinkClick;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class LinkShortenerService extends BaseService
{
    /**
     * Shorten a URL.
     *
     * @param array<string, mixed> $options
     */
    public function shorten(
        string $workspaceId,
        string $userId,
        string $originalUrl,
        array $options = []
    ): ShortLink {
        $shortCode = $this->generateShortCode();

        $link = ShortLink::create([
            'workspace_id' => $workspaceId,
            'original_url' => $originalUrl,
            'short_code' => $shortCode,
            'custom_alias' => $options['custom_alias'] ?? null,
            'title' => $options['title'] ?? null,
            'click_count' => 0,
            'utm_source' => $options['utm_source'] ?? null,
            'utm_medium' => $options['utm_medium'] ?? null,
            'utm_campaign' => $options['utm_campaign'] ?? null,
            'utm_term' => $options['utm_term'] ?? null,
            'utm_content' => $options['utm_content'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'created_by_user_id' => $userId,
        ]);

        $this->log('Short link created', ['link_id' => $link->id]);

        return $link;
    }

    /**
     * Resolve a short code to a link.
     */
    public function resolve(string $code): ?ShortLink
    {
        $link = ShortLink::where('short_code', $code)
            ->orWhere('custom_alias', $code)
            ->first();

        if ($link === null || $link->isExpired()) {
            return null;
        }

        return $link;
    }

    /**
     * Record a click on a short link.
     */
    public function recordClick(ShortLink $link, Request $request): ShortLinkClick
    {
        return $this->transaction(function () use ($link, $request) {
            // Increment counter
            $link->increment('click_count');

            // Create click record
            $click = ShortLinkClick::create([
                'short_link_id' => $link->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'clicked_at' => now(),
            ]);

            return $click;
        });
    }

    /**
     * Generate a unique short code.
     */
    public function generateShortCode(): string
    {
        do {
            $code = Str::random(7);
        } while (ShortLink::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Build URL with UTM parameters.
     *
     * @param array<string, string> $utmParams
     */
    public function buildUtmUrl(string $url, array $utmParams): string
    {
        $params = array_filter($utmParams);

        if (empty($params)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }

    /**
     * Get statistics for a short link.
     *
     * @return array<string, mixed>
     */
    public function getStats(ShortLink $link): array
    {
        $recentClicks = $link->clicks()
            ->orderBy('clicked_at', 'desc')
            ->limit(10)
            ->get();

        $deviceBreakdown = $link->clicks()
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        return [
            'total_clicks' => $link->click_count,
            'recent_clicks' => $recentClicks,
            'device_breakdown' => $deviceBreakdown,
            'full_url' => $link->getFullUrl(),
            'target_url' => $link->buildUtmUrl(),
        ];
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if ($userAgent === null) {
            return 'unknown';
        }

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            if (preg_match('/ipad|tablet/i', $userAgent)) {
                return 'tablet';
            }

            return 'mobile';
        }

        return 'desktop';
    }
}
