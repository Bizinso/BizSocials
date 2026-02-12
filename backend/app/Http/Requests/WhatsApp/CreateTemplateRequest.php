<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use App\Enums\WhatsApp\WhatsAppTemplateCategory;
use Illuminate\Foundation\Http\FormRequest;

final class CreateTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:512'],
            'language' => ['sometimes', 'string', 'max:10'],
            'category' => ['required', 'string', 'in:' . implode(',', WhatsAppTemplateCategory::values())],
            'header_type' => ['sometimes', 'string', 'in:none,text,image,video,document'],
            'header_content' => ['nullable', 'string'],
            'body_text' => ['required', 'string', 'max:1024'],
            'footer_text' => ['nullable', 'string', 'max:60'],
            'buttons' => ['nullable', 'array', 'max:3'],
            'buttons.*.type' => ['required_with:buttons', 'string', 'in:QUICK_REPLY,URL,PHONE_NUMBER'],
            'buttons.*.text' => ['required_with:buttons', 'string', 'max:25'],
            'buttons.*.url' => ['nullable', 'url'],
            'buttons.*.phone_number' => ['nullable', 'string'],
            'sample_values' => ['nullable', 'array'],
        ];
    }
}
