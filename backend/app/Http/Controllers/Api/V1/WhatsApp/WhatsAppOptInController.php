<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppOptInData;
use App\Enums\WhatsApp\WhatsAppOptInSource;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\CreateOptInRequest;
use App\Http\Requests\WhatsApp\ImportOptInsRequest;
use App\Models\WhatsApp\WhatsAppOptIn;
use App\Models\Workspace\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppOptInController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $query = WhatsAppOptIn::forWorkspace($workspace->id)
            ->orderByDesc('created_at');

        if ($request->has('active_only')) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate((int) $request->query('per_page', '20'));

        return $this->paginated($contacts, WhatsAppOptInData::class);
    }

    public function store(CreateOptInRequest $request, Workspace $workspace): JsonResponse
    {
        $optIn = WhatsAppOptIn::create([
            'workspace_id' => $workspace->id,
            'phone_number' => $request->validated('phone_number'),
            'customer_name' => $request->validated('customer_name'),
            'source' => WhatsAppOptInSource::from($request->validated('source', 'manual')),
            'opted_in_at' => now(),
            'is_active' => true,
            'tags' => $request->validated('tags'),
        ]);

        return $this->created(
            WhatsAppOptInData::fromModel($optIn),
            'Contact added',
        );
    }

    public function show(Workspace $workspace, WhatsAppOptIn $whatsappContact): JsonResponse
    {
        return $this->success(
            WhatsAppOptInData::fromModel($whatsappContact),
        );
    }

    public function update(CreateOptInRequest $request, Workspace $workspace, WhatsAppOptIn $whatsappContact): JsonResponse
    {
        $whatsappContact->update($request->validated());

        return $this->success(
            WhatsAppOptInData::fromModel($whatsappContact->fresh()),
            'Contact updated',
        );
    }

    public function destroy(Workspace $workspace, WhatsAppOptIn $whatsappContact): JsonResponse
    {
        $whatsappContact->delete();

        return $this->noContent();
    }

    public function import(ImportOptInsRequest $request, Workspace $workspace): JsonResponse
    {
        $file = $request->file('file');
        $imported = 0;
        $skipped = 0;

        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                $phone = $data['phone_number'] ?? $data['phone'] ?? '';

                if ($phone === '') {
                    $skipped++;
                    continue;
                }

                WhatsAppOptIn::firstOrCreate(
                    ['workspace_id' => $workspace->id, 'phone_number' => $phone],
                    [
                        'customer_name' => $data['name'] ?? $data['customer_name'] ?? null,
                        'source' => WhatsAppOptInSource::IMPORT,
                        'opted_in_at' => now(),
                        'is_active' => true,
                        'tags' => isset($data['tags']) ? explode(',', $data['tags']) : null,
                    ],
                );

                $imported++;
            }

            fclose($handle);
        }

        return $this->success([
            'imported' => $imported,
            'skipped' => $skipped,
        ], "Imported {$imported} contacts, skipped {$skipped}");
    }
}
