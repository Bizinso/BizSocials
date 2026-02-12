<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    /**
     * Additional error details.
     *
     * @var array<string, mixed>
     */
    protected array $errors = [];

    /**
     * Create a new API exception instance.
     *
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message = 'An error occurred',
        int $code = 400,
        array $errors = []
    ) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if (! empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Get the additional error details.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
