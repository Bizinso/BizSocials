@extends('emails.layouts.base')

@section('title', 'Payment Failed')

@section('content')
    <div style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
        <p style="margin: 0; color: #991B1B; font-weight: 600; font-size: 14px;">
            Action Required: Your payment could not be processed.
        </p>
    </div>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        Hi {{ $user->name }},
    </p>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        We were unable to process the payment for your <strong>{{ $planName }}</strong> subscription.
    </p>

    @if($reason)
        <p style="margin: 0 0 16px 0; color: #4B5563;">
            <strong>Reason:</strong> {{ $reason }}
        </p>
    @endif

    <p style="margin: 0 0 24px 0; color: #4B5563;">
        Please update your payment method to avoid any interruption to your service. Your account will remain active for a grace period, but features may be limited if payment is not resolved.
    </p>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $billingUrl }}" style="display: inline-block; padding: 12px 32px; background-color: #EF4444; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
            Update Payment Method
        </a>
    </p>

    <p style="margin: 16px 0 0 0; color: #6B7280; font-size: 14px;">
        If you believe this is an error, please contact our support team.
    </p>
@endsection
