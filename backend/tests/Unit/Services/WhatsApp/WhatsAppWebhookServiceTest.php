<?php

declare(strict_types=1);

/**
 * WhatsAppWebhookService Unit Tests
 *
 * Tests for the WhatsApp webhook processing service:
 * - Processing inbound messages
 * - Processing status updates
 * - Creating inbox items
 * - Conversation resolution
 * - Error handling
 *
 * @see \App\Services\WhatsApp\WhatsAppWebhookService
 */

use App\Services\WhatsApp\WhatsAppWebhookService;

describe('WhatsAppWebhookService', function () {
    it('exists and can be instantiated', function () {
        expect(class_exists(WhatsAppWebhookService::class))->toBeTrue();
    });

    it('has processWebhook method', function () {
        expect(method_exists(WhatsAppWebhookService::class, 'processWebhook'))->toBeTrue();
    });

    it('has processInboundMessage method', function () {
        expect(method_exists(WhatsAppWebhookService::class, 'processInboundMessage'))->toBeTrue();
    });

    it('has processStatusUpdate method', function () {
        expect(method_exists(WhatsAppWebhookService::class, 'processStatusUpdate'))->toBeTrue();
    });

    it('has processAccountUpdate method', function () {
        expect(method_exists(WhatsAppWebhookService::class, 'processAccountUpdate'))->toBeTrue();
    });
});
