@extends('emails.layouts.base')

@section('title', 'Subscription Confirmed')

@section('content')
    <h2 style="font-size: 22px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">
        Subscription Confirmed
    </h2>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        Hi {{ $user->name }},
    </p>

    <p style="margin: 0 0 24px 0; color: #4B5563;">
        Your subscription to the <strong>{{ $planName }}</strong> plan has been activated successfully.
    </p>

    <div style="background-color: #F9FAFB; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Plan</td>
                <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #111827;">{{ $planName }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Billing Cycle</td>
                <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #111827;">{{ ucfirst($subscription->billing_cycle->value ?? 'Monthly') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Amount</td>
                <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #111827;">{{ $subscription->currency->value ?? 'INR' }} {{ number_format((float) $subscription->amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6B7280; font-size: 14px;">Next Billing Date</td>
                <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #111827;">{{ $subscription->current_period_end?->format('F j, Y') ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $billingUrl }}" style="display: inline-block; padding: 12px 32px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
            View Billing Details
        </a>
    </p>
@endsection
