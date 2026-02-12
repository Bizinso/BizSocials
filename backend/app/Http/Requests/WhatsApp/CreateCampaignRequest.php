<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'whatsapp_phone_number_id' => ['required', 'uuid', 'exists:whatsapp_phone_numbers,id'],
            'template_id' => ['required', 'uuid', 'exists:whatsapp_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'template_params_mapping' => ['nullable', 'array'],
            'audience_filter' => ['nullable', 'array'],
            'audience_filter.tags' => ['nullable', 'array'],
            'audience_filter.tags.*' => ['string'],
            'audience_filter.opt_in_after' => ['nullable', 'date'],
            'audience_filter.exclude_tags' => ['nullable', 'array'],
            'audience_filter.exclude_tags.*' => ['string'],
        ];
    }
}
