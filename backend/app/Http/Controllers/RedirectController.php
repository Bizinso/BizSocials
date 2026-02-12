<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Content\LinkShortenerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectController extends Controller
{
    public function __construct(
        private readonly LinkShortenerService $linkShortenerService,
    ) {}

    /**
     * Resolve a short code and redirect to the original URL.
     */
    public function resolve(Request $request, string $code): RedirectResponse
    {
        $link = $this->linkShortenerService->resolve($code);

        if ($link === null) {
            abort(404, 'Short link not found or expired');
        }

        // Record the click
        $this->linkShortenerService->recordClick($link, $request);

        // Build URL with UTM parameters
        $targetUrl = $link->buildUtmUrl();

        return redirect($targetUrl, 302);
    }
}
