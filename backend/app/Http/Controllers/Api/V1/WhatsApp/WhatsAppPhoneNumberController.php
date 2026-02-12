<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppPhoneNumberData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use Illuminate\Http\JsonResponse;

final class WhatsAppPhoneNumberController extends Controller
{
    public function index(WhatsAppBusinessAccount $account): JsonResponse
    {
        $phones = $account->phoneNumbers()->get();

        return $this->success(
            WhatsAppPhoneNumberData::collection($phones),
        );
    }
}
