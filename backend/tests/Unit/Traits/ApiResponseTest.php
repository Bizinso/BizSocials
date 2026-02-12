<?php

declare(strict_types=1);

/**
 * ApiResponse Trait Unit Tests
 *
 * Tests for the ApiResponse trait which provides standardized
 * JSON response methods for API controllers.
 *
 * @see \App\Traits\ApiResponse
 */

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

// Create a test class that uses the trait
beforeEach(function () {
    $this->controller = new class
    {
        use ApiResponse;

        public function testSuccess(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
        {
            return $this->success($data, $message, $code);
        }

        public function testCreated(mixed $data = null, string $message = 'Created'): JsonResponse
        {
            return $this->created($data, $message);
        }

        public function testNoContent(): JsonResponse
        {
            return $this->noContent();
        }

        public function testError(string $message = 'Error', int $code = 400, array $errors = []): JsonResponse
        {
            return $this->error($message, $code, $errors);
        }

        public function testNotFound(string $message = 'Resource not found'): JsonResponse
        {
            return $this->notFound($message);
        }

        public function testUnauthorized(string $message = 'Unauthorized'): JsonResponse
        {
            return $this->unauthorized($message);
        }

        public function testForbidden(string $message = 'Forbidden'): JsonResponse
        {
            return $this->forbidden($message);
        }

        public function testValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
        {
            return $this->validationError($errors, $message);
        }

        public function testPaginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
        {
            return $this->paginated($paginator, $message);
        }
    };
});

test('success returns 200 status code with correct structure', function (): void {
    $response = $this->controller->testSuccess(['id' => 1], 'Operation successful');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200);

    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toBe('Operation successful')
        ->and($data['data'])->toBe(['id' => 1]);
});

test('success with custom status code', function (): void {
    $response = $this->controller->testSuccess(null, 'Accepted', 202);

    expect($response->getStatusCode())->toBe(202);
});

test('success with null data', function (): void {
    $response = $this->controller->testSuccess();

    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toBe('Success')
        ->and($data['data'])->toBeNull();
});

test('created returns 201 status code', function (): void {
    $response = $this->controller->testCreated(['id' => 'uuid-123'], 'Resource created');

    expect($response->getStatusCode())->toBe(201);

    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toBe('Resource created')
        ->and($data['data']['id'])->toBe('uuid-123');
});

test('noContent returns 204 status code', function (): void {
    $response = $this->controller->testNoContent();

    expect($response->getStatusCode())->toBe(204);
});

test('error returns correct structure without errors array', function (): void {
    $response = $this->controller->testError('Something went wrong', 400);

    expect($response->getStatusCode())->toBe(400);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Something went wrong')
        ->and(array_key_exists('errors', $data))->toBeFalse();
});

test('error returns correct structure with errors array', function (): void {
    $errors = ['field' => ['Field is required']];
    $response = $this->controller->testError('Validation failed', 422, $errors);

    expect($response->getStatusCode())->toBe(422);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Validation failed')
        ->and($data['errors'])->toBe($errors);
});

test('notFound returns 404 status code', function (): void {
    $response = $this->controller->testNotFound();

    expect($response->getStatusCode())->toBe(404);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Resource not found');
});

test('notFound with custom message', function (): void {
    $response = $this->controller->testNotFound('User not found');

    $data = $response->getData(true);

    expect($data['message'])->toBe('User not found');
});

test('unauthorized returns 401 status code', function (): void {
    $response = $this->controller->testUnauthorized();

    expect($response->getStatusCode())->toBe(401);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Unauthorized');
});

test('unauthorized with custom message', function (): void {
    $response = $this->controller->testUnauthorized('Invalid token');

    $data = $response->getData(true);

    expect($data['message'])->toBe('Invalid token');
});

test('forbidden returns 403 status code', function (): void {
    $response = $this->controller->testForbidden();

    expect($response->getStatusCode())->toBe(403);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Forbidden');
});

test('forbidden with custom message', function (): void {
    $response = $this->controller->testForbidden('Access denied');

    $data = $response->getData(true);

    expect($data['message'])->toBe('Access denied');
});

test('validationError returns 422 status code with errors', function (): void {
    $errors = [
        'email' => ['The email field is required.'],
        'name' => ['The name must be at least 3 characters.'],
    ];

    $response = $this->controller->testValidationError($errors);

    expect($response->getStatusCode())->toBe(422);

    $data = $response->getData(true);

    expect($data['success'])->toBeFalse()
        ->and($data['message'])->toBe('Validation failed')
        ->and($data['errors'])->toBe($errors);
});

test('validationError with custom message', function (): void {
    $errors = ['field' => ['Error']];
    $response = $this->controller->testValidationError($errors, 'Please check your input');

    $data = $response->getData(true);

    expect($data['message'])->toBe('Please check your input');
});

test('paginated returns correct structure with meta and links', function (): void {
    $items = collect([
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
    ]);

    $paginator = new LengthAwarePaginator(
        items: $items,
        total: 50,
        perPage: 10,
        currentPage: 2,
        options: ['path' => 'http://example.com/api/items']
    );

    $response = $this->controller->testPaginated($paginator, 'Items retrieved');

    expect($response->getStatusCode())->toBe(200);

    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toBe('Items retrieved')
        ->and($data['data'])->toHaveCount(2)
        ->and($data['meta']['current_page'])->toBe(2)
        ->and($data['meta']['last_page'])->toBe(5)
        ->and($data['meta']['per_page'])->toBe(10)
        ->and($data['meta']['total'])->toBe(50)
        ->and($data['meta']['from'])->toBe(11)
        ->and($data['meta']['to'])->toBe(12)
        ->and(array_key_exists('links', $data))->toBeTrue()
        ->and(array_key_exists('first', $data['links']))->toBeTrue()
        ->and(array_key_exists('last', $data['links']))->toBeTrue()
        ->and(array_key_exists('prev', $data['links']))->toBeTrue()
        ->and(array_key_exists('next', $data['links']))->toBeTrue();
});

test('paginated handles empty results', function (): void {
    $paginator = new LengthAwarePaginator(
        items: collect([]),
        total: 0,
        perPage: 10,
        currentPage: 1
    );

    $response = $this->controller->testPaginated($paginator);

    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['data'])->toBeEmpty()
        ->and($data['meta']['total'])->toBe(0)
        ->and($data['meta']['from'])->toBeNull()
        ->and($data['meta']['to'])->toBeNull();
});
