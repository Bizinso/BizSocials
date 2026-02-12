<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-name { font-size: 24px; font-weight: bold; color: #4F46E5; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #111; text-align: right; }
        .invoice-meta { text-align: right; margin-top: 8px; font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 120px; color: #555; }
        .items-table th { background-color: #F3F4F6; padding: 10px 8px; text-align: left; font-weight: 600; border-bottom: 2px solid #E5E7EB; }
        .items-table td { padding: 10px 8px; border-bottom: 1px solid #E5E7EB; }
        .items-table .amount { text-align: right; }
        .totals { margin-top: 20px; float: right; width: 280px; }
        .totals table td { padding: 4px 8px; }
        .totals .total-row { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .gst-details { margin-top: 30px; padding: 12px; background-color: #F9FAFB; border: 1px solid #E5E7EB; font-size: 11px; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #E5E7EB; font-size: 10px; color: #9CA3AF; text-align: center; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background-color: #D1FAE5; color: #065F46; }
        .status-issued { background-color: #FEF3C7; color: #92400E; }
        .status-cancelled { background-color: #FEE2E2; color: #991B1B; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="company-name">BizSocials</div>
                <p style="color: #666; margin: 4px 0;">Social Media Management Platform</p>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <strong>#{{ $invoice->invoice_number }}</strong><br>
                    Date: {{ $invoice->issued_at?->format('d M Y') ?? 'N/A' }}<br>
                    Due: {{ $invoice->due_at?->format('d M Y') ?? 'N/A' }}<br>
                    <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->value }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>Bill To:</strong><br>
                @if(!empty($invoice->billing_address))
                    {{ $invoice->billing_address['name'] ?? $tenantName ?? '' }}<br>
                    @if(!empty($invoice->billing_address['line1'])){{ $invoice->billing_address['line1'] }}<br>@endif
                    @if(!empty($invoice->billing_address['city'])){{ $invoice->billing_address['city'] }}, @endif
                    @if(!empty($invoice->billing_address['state'])){{ $invoice->billing_address['state'] }} @endif
                    @if(!empty($invoice->billing_address['postal_code'])){{ $invoice->billing_address['postal_code'] }}@endif
                @else
                    {{ $tenantName ?? 'Customer' }}
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <strong>Subscription:</strong><br>
                {{ $planName ?? 'N/A' }}<br>
                {{ ucfirst($invoice->subscription?->billing_cycle?->value ?? '') }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>HSN</th>
                <th>Qty</th>
                <th class="amount">Unit Price</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->line_items ?? [] as $item)
            <tr>
                <td>{{ $item['description'] ?? '' }}</td>
                <td>{{ $item['hsn_code'] ?? '998314' }}</td>
                <td>{{ $item['quantity'] ?? 1 }}</td>
                <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($item['amount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if(($invoice->gst_details['cgst'] ?? 0) > 0)
                <tr>
                    <td>CGST (9%):</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->gst_details['cgst'], 2) }}</td>
                </tr>
                <tr>
                    <td>SGST (9%):</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->gst_details['sgst'], 2) }}</td>
                </tr>
                @endif
                @if(($invoice->gst_details['igst'] ?? 0) > 0)
                <tr>
                    <td>IGST (18%):</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->gst_details['igst'], 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->total, 2) }}</td>
                </tr>
                @if($invoice->isPaid())
                <tr>
                    <td>Amount Paid:</td>
                    <td class="amount">{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                @endif
                @if($invoice->amount_due > 0)
                <tr style="color: #DC2626;">
                    <td><strong>Amount Due:</strong></td>
                    <td class="amount"><strong>{{ $invoice->currency->value ?? 'INR' }} {{ number_format($invoice->amount_due, 2) }}</strong></td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if(!empty($invoice->gst_details['gstin']))
    <div class="gst-details">
        <strong>GST Details</strong><br>
        GSTIN: {{ $invoice->gst_details['gstin'] }}<br>
        Place of Supply: {{ $invoice->gst_details['place_of_supply'] ?? 'N/A' }}
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated invoice and does not require a physical signature.</p>
        <p>&copy; {{ date('Y') }} BizSocials. All rights reserved.</p>
    </div>
</body>
</html>
