<?php

declare(strict_types=1);

namespace App\Http\Requests\KB;

use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Admin middleware handles authorization.
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
            'category_id' => ['sometimes', 'uuid', 'exists:kb_categories,id'],
            'title' => ['sometimes', 'string', 'max:200'],
            'content' => ['sometimes', 'string'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'article_type' => ['sometimes', Rule::in(KBArticleType::values())],
            'difficulty_level' => ['sometimes', Rule::in(KBDifficultyLevel::values())],
            'is_featured' => ['sometimes', 'boolean'],
            'featured_image' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:100'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:kb_tags,id'],
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
            'category_id.exists' => 'The selected category does not exist.',
            'title.max' => 'Article title cannot exceed 200 characters.',
            'slug.regex' => 'Slug must be lowercase letters and numbers separated by hyphens.',
            'tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }
}
