<?php

declare(strict_types=1);

namespace App\Http\Requests\KB;

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubmitFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Public endpoint - always allowed.
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
            'is_helpful' => ['required', 'boolean'],
            'category' => ['sometimes', 'nullable', Rule::in(KBFeedbackCategory::values())],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
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
            'is_helpful.required' => 'Please indicate whether the article was helpful.',
            'comment.max' => 'Feedback comment cannot exceed 2000 characters.',
        ];
    }
}
