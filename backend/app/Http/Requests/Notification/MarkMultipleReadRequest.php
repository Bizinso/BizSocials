<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

final class MarkMultipleReadRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'uuid', 'exists:notifications,id'],
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
            'ids.required' => 'Notification IDs are required.',
            'ids.array' => 'Notification IDs must be an array.',
            'ids.min' => 'At least one notification ID is required.',
            'ids.max' => 'Cannot process more than 100 notifications at once.',
            'ids.*.required' => 'Each notification ID is required.',
            'ids.*.uuid' => 'Each notification ID must be a valid UUID.',
            'ids.*.exists' => 'One or more selected notifications do not exist.',
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
            'ids' => 'notification IDs',
            'ids.*' => 'notification ID',
        ];
    }
}
