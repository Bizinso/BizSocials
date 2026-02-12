<?php

declare(strict_types=1);

/**
 * SupportTicketComment Model Unit Tests
 *
 * Tests for the SupportTicketComment model.
 *
 * @see \App\Models\Support\SupportTicketComment
 */

use App\Enums\Support\SupportCommentType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create comment with factory', function (): void {
    $comment = SupportTicketComment::factory()->create();

    expect($comment)->toBeInstanceOf(SupportTicketComment::class)
        ->and($comment->id)->not->toBeNull()
        ->and($comment->content)->not->toBeNull();
});

test('has correct table name', function (): void {
    $comment = new SupportTicketComment();

    expect($comment->getTable())->toBe('support_ticket_comments');
});

test('casts attributes correctly', function (): void {
    $comment = SupportTicketComment::factory()->create();

    expect($comment->comment_type)->toBeInstanceOf(SupportCommentType::class)
        ->and($comment->is_internal)->toBeBool();
});

test('ticket relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $comment = SupportTicketComment::factory()->forTicket($ticket)->create();

    expect($comment->ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($comment->ticket->id)->toBe($ticket->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $comment = SupportTicketComment::factory()->byUser($user)->create();

    expect($comment->user)->toBeInstanceOf(User::class)
        ->and($comment->user->id)->toBe($user->id);
});

test('admin relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $comment = SupportTicketComment::factory()->byAdmin($admin)->create();

    expect($comment->admin)->toBeInstanceOf(SuperAdminUser::class)
        ->and($comment->admin->id)->toBe($admin->id);
});

test('attachments relationship works', function (): void {
    $comment = SupportTicketComment::factory()->create();
    SupportTicketAttachment::factory()->forComment($comment)->count(2)->create();

    expect($comment->attachments)->toHaveCount(2)
        ->and($comment->attachments->first())->toBeInstanceOf(SupportTicketAttachment::class);
});

test('forTicket scope filters by ticket', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketComment::factory()->forTicket($ticket)->count(2)->create();
    SupportTicketComment::factory()->create();

    expect(SupportTicketComment::forTicket($ticket->id)->count())->toBe(2);
});

test('public scope filters public comments', function (): void {
    SupportTicketComment::factory()->reply()->count(2)->create();
    SupportTicketComment::factory()->note()->create();

    expect(SupportTicketComment::public()->count())->toBe(2);
});

test('internal scope filters internal comments', function (): void {
    SupportTicketComment::factory()->note()->count(2)->create();
    SupportTicketComment::factory()->reply()->create();

    expect(SupportTicketComment::internal()->count())->toBe(2);
});

test('replies scope filters replies', function (): void {
    SupportTicketComment::factory()->reply()->count(2)->create();
    SupportTicketComment::factory()->note()->create();

    expect(SupportTicketComment::replies()->count())->toBe(2);
});

test('notes scope filters notes', function (): void {
    SupportTicketComment::factory()->note()->count(2)->create();
    SupportTicketComment::factory()->reply()->create();

    expect(SupportTicketComment::notes()->count())->toBe(2);
});

test('system scope filters system comments', function (): void {
    SupportTicketComment::factory()->statusChange()->create();
    SupportTicketComment::factory()->assignment()->create();
    SupportTicketComment::factory()->system()->create();
    SupportTicketComment::factory()->reply()->create();

    expect(SupportTicketComment::system()->count())->toBe(3);
});

test('isPublic returns correct value', function (): void {
    $reply = SupportTicketComment::factory()->reply()->create();
    $note = SupportTicketComment::factory()->note()->create();

    expect($reply->isPublic())->toBeTrue()
        ->and($note->isPublic())->toBeFalse();
});

test('isInternal returns correct value', function (): void {
    $note = SupportTicketComment::factory()->note()->create();
    $reply = SupportTicketComment::factory()->reply()->create();

    expect($note->isInternal())->toBeTrue()
        ->and($reply->isInternal())->toBeFalse();
});

test('isReply returns correct value', function (): void {
    $reply = SupportTicketComment::factory()->reply()->create();
    $note = SupportTicketComment::factory()->note()->create();

    expect($reply->isReply())->toBeTrue()
        ->and($note->isReply())->toBeFalse();
});

test('isNote returns correct value', function (): void {
    $note = SupportTicketComment::factory()->note()->create();
    $reply = SupportTicketComment::factory()->reply()->create();

    expect($note->isNote())->toBeTrue()
        ->and($reply->isNote())->toBeFalse();
});

test('isSystem returns correct value', function (): void {
    $system = SupportTicketComment::factory()->system()->create();
    $statusChange = SupportTicketComment::factory()->statusChange()->create();
    $reply = SupportTicketComment::factory()->reply()->create();

    expect($system->isSystem())->toBeTrue()
        ->and($statusChange->isSystem())->toBeTrue()
        ->and($reply->isSystem())->toBeFalse();
});

test('getAuthorName returns correct name', function (): void {
    $admin = SuperAdminUser::factory()->create(['name' => 'Admin User']);
    $comment = SupportTicketComment::factory()->byAdmin($admin)->create(['author_name' => null]);

    expect($comment->getAuthorName())->toBe('Admin User');
});

test('getAuthorName returns author_name if set', function (): void {
    $comment = SupportTicketComment::factory()->create(['author_name' => 'Custom Name']);

    expect($comment->getAuthorName())->toBe('Custom Name');
});

test('getAuthorEmail returns correct email', function (): void {
    $admin = SuperAdminUser::factory()->create(['email' => 'admin@test.com']);
    $comment = SupportTicketComment::factory()->byAdmin($admin)->create(['author_email' => null]);

    expect($comment->getAuthorEmail())->toBe('admin@test.com');
});
