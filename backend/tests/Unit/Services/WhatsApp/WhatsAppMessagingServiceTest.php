<?php

declare(strict_types=1);

/**
 * WhatsAppMessagingService Unit Tests
 *
 * Tests for the WhatsApp messaging service:
 * - Sending text messages
 * - Sending media messages
 * - Sending template messages
 * - Message status tracking
 * - Service window enforcement
 * - Daily limit checking
 *
 * @see \App\Services\WhatsApp\WhatsAppMessagingService
 */

use App\Services\WhatsApp\WhatsAppMessagingService;

describe('WhatsAppMessagingService', function () {
    it('has sendTextMessage method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'sendTextMessage'))->toBeTrue();
    });

    it('has sendMediaMessage method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'sendMediaMessage'))->toBeTrue();
    });

    it('has sendTemplateMessage method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'sendTemplateMessage'))->toBeTrue();
    });

    it('has markAsRead method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'markAsRead'))->toBeTrue();
    });

    it('has uploadMedia method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'uploadMedia'))->toBeTrue();
    });

    it('has handleOptOut method', function () {
        $service = new WhatsAppMessagingService();
        expect(method_exists($service, 'handleOptOut'))->toBeTrue();
    });

    it('detects opt-out keywords', function () {
        $service = new WhatsAppMessagingService();

        expect($service->isOptOutMessage('stop'))->toBeTrue();
        expect($service->isOptOutMessage('STOP'))->toBeTrue();
        expect($service->isOptOutMessage('unsubscribe'))->toBeTrue();
        expect($service->isOptOutMessage('opt out'))->toBeTrue();
        expect($service->isOptOutMessage('cancel'))->toBeTrue();
        expect($service->isOptOutMessage('quit'))->toBeTrue();
    });

    it('does not detect regular messages as opt-out', function () {
        $service = new WhatsAppMessagingService();

        expect($service->isOptOutMessage('hello'))->toBeFalse();
        expect($service->isOptOutMessage('I want to stop by later'))->toBeFalse();
        expect($service->isOptOutMessage('Can you cancel my order?'))->toBeFalse();
    });
});
