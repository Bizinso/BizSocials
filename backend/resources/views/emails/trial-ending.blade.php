@extends('emails.layouts.base')

@section('title', 'Your Trial is Ending Soon')

@section('content')
    <div style="background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
        <p style="margin: 0; color: #92400E; font-weight: 600; font-size: 14px;">
            Your free trial ends in {{ $daysRemaining }} {{ $daysRemaining === 1 ? 'day' : 'days' }}.
        </p>
    </div>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        Hi {{ $user->name }},
    </p>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        Your trial of the <strong>{{ $planName }}</strong> plan will end on <strong>{{ $trialEndDate }}</strong>.
    </p>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        To continue using all features without interruption, upgrade to a paid plan before your trial expires.
    </p>

    <p style="margin: 0 0 8px 0; font-weight: 600; color: #111827;">What you'll keep with a paid plan:</p>
    <ul style="margin: 0 0 24px 0; padding-left: 20px; color: #4B5563;">
        <li style="margin-bottom: 6px;">All your scheduled and published content</li>
        <li style="margin-bottom: 6px;">Connected social accounts and analytics</li>
        <li style="margin-bottom: 6px;">Team collaboration and inbox management</li>
        <li style="margin-bottom: 6px;">WhatsApp Business messaging</li>
    </ul>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $upgradeUrl }}" style="display: inline-block; padding: 12px 32px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
            Choose a Plan
        </a>
    </p>

    <p style="margin: 16px 0 0 0; color: #6B7280; font-size: 14px;">
        Have questions? Reply to this email or visit our knowledge base.
    </p>
@endsection
