<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class SendWhatsAppMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:text,image,video,document,audio,template'],
            'content' => ['required_if:type,text', 'nullable', 'string', 'max:4096'],
            'media_url' => ['required_if:type,image,video,document,audio', 'nullable', 'url'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'template_name' => ['required_if:type,template', 'nullable', 'string'],
            'template_language' => ['sometimes', 'nullable', 'string', 'max:10'],
            'template_components' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'content.required_if' => 'Message content is required for text messages.',
            'media_url.required_if' => 'Media URL is required for media messages.',
            'template_name.required_if' => 'Template name is required for template messages.',
        ];
    }
}
