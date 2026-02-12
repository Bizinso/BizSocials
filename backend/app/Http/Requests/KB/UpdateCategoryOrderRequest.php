<?php

declare(strict_types=1);

namespace App\Http\Requests\KB;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCategoryOrderRequest extends FormRequest
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
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'uuid', 'exists:kb_categories,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
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
            'order.required' => 'Category order data is required.',
            'order.*.id.required' => 'Each category must have an ID.',
            'order.*.id.exists' => 'One or more categories do not exist.',
            'order.*.sort_order.required' => 'Each category must have a sort order.',
        ];
    }
}
