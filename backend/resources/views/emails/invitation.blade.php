<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've been invited to join {{ $tenantName }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; margin: 0; padding: 0; background-color: #f4f5f7;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div style="background-color: #F9FAFB; padding: 24px; text-align: center;">
                <div style="width: 48px; height: 48px; background-color: #4F46E5; border-radius: 50%; margin: 0 auto 16px auto;"></div>
                <h1 style="font-size: 20px; font-weight: 600; color: #111827; margin: 0;">
                    You've been invited!
                </h1>
            </div>

            <div style="padding: 24px 32px;">
                <p style="margin: 0 0 16px 0;">Hi,</p>
                <p style="margin: 0 0 16px 0;">
                    <strong>{{ $inviterName }}</strong> has invited you to join
                    <strong>{{ $tenantName }}</strong> as a <strong>{{ $roleName }}</strong>.
                </p>
                <p style="margin: 0 0 24px 0;">
                    Click the button below to accept the invitation and set up your account.
                </p>

                <p style="text-align: center; margin: 20px 0;">
                    <a href="{{ $acceptUrl }}" style="display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">Accept Invitation</a>
                </p>

                <p style="margin: 16px 0 0 0; font-size: 13px; color: #6B7280;">
                    This invitation expires on {{ $expiresAt }}. If you didn't expect this invitation, you can safely ignore this email.
                </p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px; padding: 16px; color: #9CA3AF; font-size: 12px;">
            <p style="margin: 0;">&copy; {{ date('Y') }} {{ config('app.name', 'BizSocials') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
