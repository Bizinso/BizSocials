<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Data\Billing\InvoiceData;
use App\Http\Controllers\Api\V1\Controller;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * List invoices for the current tenant.
     *
     * GET /api/v1/billing/invoices
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $filters = [
            'status' => $request->query('status'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'per_page' => $request->query('per_page', 15),
        ];

        $invoices = $this->invoiceService->listForTenant($tenant, $filters);

        $data = $invoices->through(fn ($invoice) => InvoiceData::fromModel($invoice));

        return $this->paginated($invoices->setCollection(
            collect($data->items())
        ), 'Invoices retrieved');
    }

    /**
     * Get a specific invoice.
     *
     * GET /api/v1/billing/invoices/{invoice}
     */
    public function show(Request $request, string $invoice): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $invoiceModel = $this->invoiceService->getByTenant($tenant, $invoice);

        return $this->success(
            InvoiceData::fromModel($invoiceModel),
            'Invoice retrieved'
        );
    }

    /**
     * Download invoice PDF.
     *
     * GET /api/v1/billing/invoices/{invoice}/download
     */
    public function download(Request $request, string $invoice): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $invoiceModel = $this->invoiceService->getByTenant($tenant, $invoice);
        $url = $this->invoiceService->downloadUrl($invoiceModel);

        return $this->success([
            'download_url' => $url,
        ], 'Download URL generated');
    }
}
