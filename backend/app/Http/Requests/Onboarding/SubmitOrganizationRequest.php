<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitOrganizationRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'timezone' => ['required', 'string', 'max:100', 'timezone'],
            'industry' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
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
            'country.size' => 'Country must be a valid ISO 3166-1 alpha-2 code.',
            'timezone.timezone' => 'Please select a valid timezone.',
        ];
    }
}
