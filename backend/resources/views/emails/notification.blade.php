<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; margin: 0; padding: 0; background-color: #f4f5f7;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div style="background-color: #F9FAFB; padding: 24px; text-align: center;">
                <div style="width: 48px; height: 48px; background-color: {{ $isUrgent ? '#EF4444' : '#4F46E5' }}; border-radius: 50%; margin: 0 auto 16px auto;"></div>
                <h1 style="font-size: 20px; font-weight: 600; color: #111827; margin: 0;">
                    {{ $notification->title }}
                </h1>
            </div>

            <div style="padding: 24px 32px;">
                <p style="margin: 0 0 16px 0;">Hi {{ $user->name }},</p>
                <p style="margin: 0 0 16px 0;">{{ $notification->message }}</p>

                @if($actionUrl)
                    <p style="text-align: center; margin: 20px 0;">
                        <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">View Details</a>
                    </p>
                @endif
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px; padding: 16px; color: #9CA3AF; font-size: 12px;">
            <p style="margin: 0;">&copy; {{ date('Y') }} {{ config('app.name', 'BizSocials') }}. All rights reserved.</p>
            <p style="margin: 8px 0 0 0;">
                <a href="{{ $preferencesUrl }}" style="color: #9CA3AF; text-decoration: underline;">Manage notification preferences</a>
            </p>
        </div>
    </div>
</body>
</html>
