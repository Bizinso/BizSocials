@extends('emails.layouts.base')

@section('title', 'Payment Receipt')

@section('content')
    <h2 style="font-size: 22px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">
        Payment Receipt
    </h2>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        Hi {{ $user->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #4B5563;">
        We've received your payment. Here are the details:
    </p>

    <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <div style="text-align: center; margin-bottom: 12px;">
            <span style="font-size: 28px; font-weight: 700; color: #166534;">{{ $currency }} {{ $amount }}</span>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Payment ID</td>
                <td style="padding: 8px 0; text-align: right; font-size: 13px; color: #374151; font-family: monospace;">{{ $payment->razorpay_payment_id ?? $payment->id }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Date</td>
                <td style="padding: 8px 0; text-align: right; color: #374151;">{{ $payment->captured_at?->format('F j, Y') ?? $payment->created_at->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Method</td>
                <td style="padding: 8px 0; text-align: right; color: #374151;">{{ ucfirst($payment->method ?? 'Razorpay') }}</td>
            </tr>
        </table>
    </div>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $invoiceUrl }}" style="display: inline-block; padding: 12px 32px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
            View Invoices
        </a>
    </p>
@endsection
