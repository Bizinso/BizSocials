<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'BizSocials'))</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; margin: 0; padding: 0; background-color: #f4f5f7;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden;">
            {{-- Header --}}
            <div style="background-color: #F9FAFB; padding: 24px; text-align: center; border-bottom: 1px solid #E5E7EB;">
                <h1 style="font-size: 20px; font-weight: 700; color: #4F46E5; margin: 0;">
                    {{ config('app.name', 'BizSocials') }}
                </h1>
            </div>

            {{-- Content --}}
            <div style="padding: 32px;">
                @yield('content')
            </div>
        </div>

        {{-- Footer --}}
        <div style="text-align: center; margin-top: 24px; padding: 16px; color: #9CA3AF; font-size: 12px;">
            <p style="margin: 0;">&copy; {{ date('Y') }} {{ config('app.name', 'BizSocials') }}. All rights reserved.</p>
            @hasSection('footer-links')
                @yield('footer-links')
            @endif
        </div>
    </div>
</body>
</html>
