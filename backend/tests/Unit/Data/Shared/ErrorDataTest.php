<?php

declare(strict_types=1);

/**
 * ErrorData Unit Tests
 *
 * Tests for the ErrorData data transfer object which represents
 * error details in API responses.
 *
 * @see \App\Data\Shared\ErrorData
 */

use App\Data\Shared\ErrorData;

test('can create error data with message only', function (): void {
    $error = new ErrorData(
        message: 'Something went wrong'
    );

    expect($error->message)->toBe('Something went wrong')
        ->and($error->field)->toBeNull()
        ->and($error->code)->toBeNull();
});

test('can create error data with all properties', function (): void {
    $error = new ErrorData(
        message: 'Email is required',
        field: 'email',
        code: 'REQUIRED_FIELD'
    );

    expect($error->message)->toBe('Email is required')
        ->and($error->field)->toBe('email')
        ->and($error->code)->toBe('REQUIRED_FIELD');
});

test('can create error data with message and field', function (): void {
    $error = new ErrorData(
        message: 'Invalid format',
        field: 'phone'
    );

    expect($error->message)->toBe('Invalid format')
        ->and($error->field)->toBe('phone')
        ->and($error->code)->toBeNull();
});

test('can create error data with message and code', function (): void {
    $error = new ErrorData(
        message: 'Rate limit exceeded',
        field: null,
        code: 'RATE_LIMIT'
    );

    expect($error->message)->toBe('Rate limit exceeded')
        ->and($error->field)->toBeNull()
        ->and($error->code)->toBe('RATE_LIMIT');
});

test('can convert to array', function (): void {
    $error = new ErrorData(
        message: 'Validation error',
        field: 'name',
        code: 'MIN_LENGTH'
    );

    $array = $error->toArray();

    expect($array)->toBeArray()
        ->and($array['message'])->toBe('Validation error')
        ->and($array['field'])->toBe('name')
        ->and($array['code'])->toBe('MIN_LENGTH');
});

test('is a final class', function (): void {
    $reflection = new ReflectionClass(ErrorData::class);

    expect($reflection->isFinal())->toBeTrue();
});

test('field property is nullable', function (): void {
    $error = new ErrorData(message: 'Error');

    expect($error->field)->toBeNull();
});

test('code property is nullable', function (): void {
    $error = new ErrorData(message: 'Error');

    expect($error->code)->toBeNull();
});
