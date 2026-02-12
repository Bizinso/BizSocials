<?php

declare(strict_types=1);

/**
 * DeviceType Enum Unit Tests
 *
 * Tests for the DeviceType enum which defines the type
 * of device used for a session.
 *
 * @see \App\Enums\User\DeviceType
 */

use App\Enums\User\DeviceType;

test('has all expected cases', function (): void {
    $cases = DeviceType::cases();

    expect($cases)->toHaveCount(4)
        ->and(DeviceType::DESKTOP->value)->toBe('desktop')
        ->and(DeviceType::MOBILE->value)->toBe('mobile')
        ->and(DeviceType::TABLET->value)->toBe('tablet')
        ->and(DeviceType::API->value)->toBe('api');
});

test('label returns correct labels', function (): void {
    expect(DeviceType::DESKTOP->label())->toBe('Desktop')
        ->and(DeviceType::MOBILE->label())->toBe('Mobile')
        ->and(DeviceType::TABLET->label())->toBe('Tablet')
        ->and(DeviceType::API->label())->toBe('API');
});

test('fromUserAgent detects desktop browsers', function (): void {
    $chromeWindows = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $chromeMac = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $firefox = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0';

    expect(DeviceType::fromUserAgent($chromeWindows))->toBe(DeviceType::DESKTOP)
        ->and(DeviceType::fromUserAgent($chromeMac))->toBe(DeviceType::DESKTOP)
        ->and(DeviceType::fromUserAgent($firefox))->toBe(DeviceType::DESKTOP);
});

test('fromUserAgent detects mobile devices', function (): void {
    $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
    $android = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

    expect(DeviceType::fromUserAgent($iphone))->toBe(DeviceType::MOBILE)
        ->and(DeviceType::fromUserAgent($android))->toBe(DeviceType::MOBILE);
});

test('fromUserAgent detects tablets', function (): void {
    $ipad = 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
    $androidTablet = 'Mozilla/5.0 (Linux; Android 14; SM-X910) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    expect(DeviceType::fromUserAgent($ipad))->toBe(DeviceType::TABLET)
        ->and(DeviceType::fromUserAgent($androidTablet))->toBe(DeviceType::TABLET);
});

test('fromUserAgent detects API clients', function (): void {
    $curl = 'curl/8.4.0';
    $postman = 'PostmanRuntime/7.36.0';
    $requests = 'python-requests/2.31.0';
    $guzzle = 'GuzzleHttp/7.8.1 curl/8.4.0 PHP/8.3.0';
    $axios = 'axios/1.6.2';
    $insomnia = 'insomnia/2023.5.6';
    $httpie = 'HTTPie/3.2.1';

    expect(DeviceType::fromUserAgent($curl))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($postman))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($requests))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($guzzle))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($axios))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($insomnia))->toBe(DeviceType::API)
        ->and(DeviceType::fromUserAgent($httpie))->toBe(DeviceType::API);
});

test('fromUserAgent defaults to desktop for unknown user agents', function (): void {
    $unknown = 'SomeUnknownBrowser/1.0';

    expect(DeviceType::fromUserAgent($unknown))->toBe(DeviceType::DESKTOP);
});

test('values returns all enum values', function (): void {
    $values = DeviceType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('desktop')
        ->and($values)->toContain('mobile')
        ->and($values)->toContain('tablet')
        ->and($values)->toContain('api');
});

test('can create enum from string value', function (): void {
    $type = DeviceType::from('mobile');

    expect($type)->toBe(DeviceType::MOBILE);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = DeviceType::tryFrom('invalid');

    expect($type)->toBeNull();
});
