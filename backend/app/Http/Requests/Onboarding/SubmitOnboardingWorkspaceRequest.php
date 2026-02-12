<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitOnboardingWorkspaceRequest extends FormRequest
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
            'purpose' => ['required', 'string', 'in:marketing,support,brand,agency'],
            'approval_mode' => ['required', 'string', 'in:auto,manual'],
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
            'purpose.in' => 'Purpose must be one of: marketing, support, brand, agency.',
            'approval_mode.in' => 'Approval mode must be either auto or manual.',
        ];
    }
}
