@extends('emails.layouts.base')

@section('title', 'Welcome to ' . config('app.name', 'BizSocials'))

@section('content')
    <h2 style="font-size: 22px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">
        Welcome aboard, {{ $user->name }}!
    </h2>

    <p style="margin: 0 0 16px 0; color: #4B5563;">
        We're thrilled to have you on {{ config('app.name', 'BizSocials') }}. Your account is ready and you can start managing your social media presence right away.
    </p>

    <p style="margin: 0 0 8px 0; font-weight: 600; color: #111827;">Here's how to get started:</p>
    <ol style="margin: 0 0 24px 0; padding-left: 20px; color: #4B5563;">
        <li style="margin-bottom: 8px;">Connect your social media accounts</li>
        <li style="margin-bottom: 8px;">Create your first workspace</li>
        <li style="margin-bottom: 8px;">Schedule your first post</li>
    </ol>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $dashboardUrl }}" style="display: inline-block; padding: 12px 32px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
            Go to Dashboard
        </a>
    </p>

    <p style="margin: 16px 0 0 0; color: #6B7280; font-size: 14px;">
        Need help? Check out our <a href="{{ $docsUrl }}" style="color: #4F46E5; text-decoration: none;">knowledge base</a> for guides and tutorials.
    </p>
@endsection
