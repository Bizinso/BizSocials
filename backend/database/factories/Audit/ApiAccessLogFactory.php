<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Models\Audit\ApiAccessLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ApiAccessLog model.
 *
 * @extends Factory<ApiAccessLog>
 */
final class ApiAccessLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ApiAccessLog>
     */
    protected $model = ApiAccessLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'api_key_id' => null,
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'endpoint' => '/api/v1/' . fake()->slug(3),
            'status_code' => 200,
            'response_time_ms' => fake()->numberBetween(10, 500),
            'request_size_bytes' => fake()->numberBetween(100, 10000),
            'response_size_bytes' => fake()->numberBetween(100, 50000),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'request_headers' => null,
            'request_params' => null,
            'error_message' => null,
            'request_id' => fake()->uuid(),
        ];
    }

    /**
     * Set HTTP method to GET.
     */
    public function get(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'GET',
        ]);
    }

    /**
     * Set HTTP method to POST.
     */
    public function post(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'POST',
        ]);
    }

    /**
     * Set HTTP method to PUT.
     */
    public function put(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'PUT',
        ]);
    }

    /**
     * Set HTTP method to DELETE.
     */
    public function deleteMethod(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'DELETE',
        ]);
    }

    /**
     * Set status code to success (2xx).
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_code' => fake()->randomElement([200, 201, 204]),
            'error_message' => null,
        ]);
    }

    /**
     * Set status code to client error (4xx).
     */
    public function clientError(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_code' => fake()->randomElement([400, 401, 403, 404, 422]),
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Set status code to server error (5xx).
     */
    public function serverError(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_code' => fake()->randomElement([500, 502, 503]),
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Set as slow request.
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'response_time_ms' => fake()->numberBetween(1000, 5000),
        ]);
    }

    /**
     * Set as fast request.
     */
    public function fast(): static
    {
        return $this->state(fn (array $attributes): array => [
            'response_time_ms' => fake()->numberBetween(10, 100),
        ]);
    }

    /**
     * Set for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set for a specific endpoint.
     */
    public function forEndpoint(string $endpoint): static
    {
        return $this->state(fn (array $attributes): array => [
            'endpoint' => $endpoint,
        ]);
    }
}
