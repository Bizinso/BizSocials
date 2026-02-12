<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Enums\Notification\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'notification_type' => [
                'required',
                'string',
                Rule::in(NotificationType::values()),
            ],
            'in_app_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notification_type.required' => 'Notification type is required.',
            'notification_type.in' => 'Invalid notification type. Please select a valid notification type.',
            'in_app_enabled.boolean' => 'In-app enabled must be true or false.',
            'email_enabled.boolean' => 'Email enabled must be true or false.',
            'push_enabled.boolean' => 'Push enabled must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notification_type' => 'notification type',
            'in_app_enabled' => 'in-app notifications',
            'email_enabled' => 'email notifications',
            'push_enabled' => 'push notifications',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = ['in_app_enabled', 'email_enabled', 'push_enabled'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_string($value)) {
                    $this->merge([
                        $field => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value,
                    ]);
                }
            }
        }
    }
}
