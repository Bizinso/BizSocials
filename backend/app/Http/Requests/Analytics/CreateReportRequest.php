<?php

declare(strict_types=1);

namespace App\Http\Requests\Analytics;

use App\Enums\Analytics\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateReportRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'report_type' => [
                'required',
                'string',
                Rule::in(array_column(ReportType::cases(), 'value')),
            ],
            'date_from' => ['required', 'date', 'before_or_equal:date_to'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'social_account_ids' => ['nullable', 'array'],
            'social_account_ids.*' => ['uuid', 'exists:social_accounts,id'],
            'metrics' => ['nullable', 'array'],
            'metrics.*' => ['string'],
            'filters' => ['nullable', 'array'],
            'file_format' => ['nullable', 'string', Rule::in(['pdf', 'csv', 'xlsx'])],
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
            'name.required' => 'Report name is required.',
            'name.max' => 'Report name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'report_type.required' => 'Report type is required.',
            'report_type.in' => 'Invalid report type. Please select a valid report type.',
            'date_from.required' => 'Start date is required.',
            'date_from.date' => 'Start date must be a valid date.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.required' => 'End date is required.',
            'date_to.date' => 'End date must be a valid date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'date_to.before_or_equal' => 'End date cannot be in the future.',
            'social_account_ids.array' => 'Social account IDs must be an array.',
            'social_account_ids.*.uuid' => 'Each social account ID must be a valid UUID.',
            'social_account_ids.*.exists' => 'One or more social accounts do not exist.',
            'file_format.in' => 'File format must be one of: pdf, csv, xlsx.',
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
            'name' => 'report name',
            'description' => 'description',
            'report_type' => 'report type',
            'date_from' => 'start date',
            'date_to' => 'end date',
            'social_account_ids' => 'social accounts',
            'metrics' => 'metrics',
            'filters' => 'filters',
            'file_format' => 'file format',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default file format if not provided
        if (!$this->has('file_format')) {
            $this->merge([
                'file_format' => 'pdf',
            ]);
        }
    }
}
