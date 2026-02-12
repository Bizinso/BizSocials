<?php

declare(strict_types=1);

/**
 * SupportTicketAttachment Model Unit Tests
 *
 * Tests for the SupportTicketAttachment model.
 *
 * @see \App\Models\Support\SupportTicketAttachment
 */

use App\Enums\Support\SupportAttachmentType;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create attachment with factory', function (): void {
    $attachment = SupportTicketAttachment::factory()->create();

    expect($attachment)->toBeInstanceOf(SupportTicketAttachment::class)
        ->and($attachment->id)->not->toBeNull()
        ->and($attachment->filename)->not->toBeNull();
});

test('has correct table name', function (): void {
    $attachment = new SupportTicketAttachment();

    expect($attachment->getTable())->toBe('support_ticket_attachments');
});

test('casts attributes correctly', function (): void {
    $attachment = SupportTicketAttachment::factory()->create();

    expect($attachment->attachment_type)->toBeInstanceOf(SupportAttachmentType::class)
        ->and($attachment->file_size)->toBeInt()
        ->and($attachment->is_inline)->toBeBool();
});

test('ticket relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $attachment = SupportTicketAttachment::factory()->forTicket($ticket)->create();

    expect($attachment->ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($attachment->ticket->id)->toBe($ticket->id);
});

test('comment relationship works', function (): void {
    $comment = SupportTicketComment::factory()->create();
    $attachment = SupportTicketAttachment::factory()->forComment($comment)->create();

    expect($attachment->comment)->toBeInstanceOf(SupportTicketComment::class)
        ->and($attachment->comment->id)->toBe($comment->id);
});

test('uploader relationship works', function (): void {
    $user = User::factory()->create();
    $attachment = SupportTicketAttachment::factory()->uploadedBy($user)->create();

    expect($attachment->uploader)->toBeInstanceOf(User::class)
        ->and($attachment->uploader->id)->toBe($user->id);
});

test('forTicket scope filters by ticket', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketAttachment::factory()->forTicket($ticket)->count(2)->create();
    SupportTicketAttachment::factory()->create();

    expect(SupportTicketAttachment::forTicket($ticket->id)->count())->toBe(2);
});

test('forComment scope filters by comment', function (): void {
    $comment = SupportTicketComment::factory()->create();
    SupportTicketAttachment::factory()->forComment($comment)->count(2)->create();
    SupportTicketAttachment::factory()->create();

    expect(SupportTicketAttachment::forComment($comment->id)->count())->toBe(2);
});

test('images scope filters images', function (): void {
    SupportTicketAttachment::factory()->image()->count(2)->create();
    SupportTicketAttachment::factory()->document()->create();

    expect(SupportTicketAttachment::images()->count())->toBe(2);
});

test('documents scope filters documents', function (): void {
    SupportTicketAttachment::factory()->document()->count(2)->create();
    SupportTicketAttachment::factory()->image()->create();

    expect(SupportTicketAttachment::documents()->count())->toBe(2);
});

test('inline scope filters inline attachments', function (): void {
    SupportTicketAttachment::factory()->inline()->count(2)->create();
    SupportTicketAttachment::factory()->create(['is_inline' => false]);

    expect(SupportTicketAttachment::inline()->count())->toBe(2);
});

test('isImage returns correct value', function (): void {
    $image = SupportTicketAttachment::factory()->image()->create();
    $document = SupportTicketAttachment::factory()->document()->create();

    expect($image->isImage())->toBeTrue()
        ->and($document->isImage())->toBeFalse();
});

test('isDocument returns correct value', function (): void {
    $document = SupportTicketAttachment::factory()->document()->create();
    $image = SupportTicketAttachment::factory()->image()->create();

    expect($document->isDocument())->toBeTrue()
        ->and($image->isDocument())->toBeFalse();
});

test('isInline returns correct value', function (): void {
    $inline = SupportTicketAttachment::factory()->inline()->create();
    $notInline = SupportTicketAttachment::factory()->create(['is_inline' => false]);

    expect($inline->isInline())->toBeTrue()
        ->and($notInline->isInline())->toBeFalse();
});

test('getUrl returns storage url', function (): void {
    $attachment = SupportTicketAttachment::factory()->create([
        'file_path' => 'support/attachments/test.png',
    ]);

    expect($attachment->getUrl())->toContain('test.png');
});

test('getHumanFileSize returns readable size for bytes', function (): void {
    $attachment = SupportTicketAttachment::factory()->create(['file_size' => 500]);

    expect($attachment->getHumanFileSize())->toBe('500 B');
});

test('getHumanFileSize returns readable size for kilobytes', function (): void {
    $attachment = SupportTicketAttachment::factory()->create(['file_size' => 2048]);

    expect($attachment->getHumanFileSize())->toBe('2 KB');
});

test('getHumanFileSize returns readable size for megabytes', function (): void {
    $attachment = SupportTicketAttachment::factory()->create(['file_size' => 5242880]);

    expect($attachment->getHumanFileSize())->toBe('5 MB');
});
