<?php

declare(strict_types=1);

namespace App\Http\Requests\Social;

use App\Enums\Social\SocialPlatform;
use Illuminate\Foundation\Http\FormRequest;

final class OAuthConnectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'uuid', 'exists:workspaces,id'],
            'session_key' => ['required', 'string', 'min:20'],
            'page_id' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace must be selected to connect the account.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'session_key.required' => 'The OAuth session key is required. Please try connecting again.',
        ];
    }

    public function getPlatform(): ?SocialPlatform
    {
        $platformValue = $this->route('platform');

        return $platformValue ? SocialPlatform::tryFrom($platformValue) : null;
    }
}
