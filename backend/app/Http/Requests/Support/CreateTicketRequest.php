<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'type' => ['sometimes', Rule::in(SupportTicketType::values())],
            'priority' => ['sometimes', Rule::in(SupportTicketPriority::values())],
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:support_categories,id'],
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
            'subject.required' => 'Please provide a subject for your ticket.',
            'subject.max' => 'Subject cannot exceed 200 characters.',
            'description.required' => 'Please describe your issue.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
