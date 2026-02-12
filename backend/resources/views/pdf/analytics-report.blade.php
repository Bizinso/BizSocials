<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['name'] }} - Analytics Report</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 40px; }
        .header { margin-bottom: 30px; }
        .company-name { font-size: 22px; font-weight: bold; color: #4F46E5; }
        .report-title { font-size: 20px; font-weight: bold; color: #111; margin-top: 16px; }
        .report-meta { margin-top: 8px; font-size: 11px; color: #666; }
        h2 { font-size: 14px; color: #1F2937; margin-top: 28px; margin-bottom: 12px; border-bottom: 2px solid #4F46E5; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #F3F4F6; padding: 8px 6px; text-align: left; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #4B5563; border-bottom: 2px solid #E5E7EB; }
        td { padding: 7px 6px; border-bottom: 1px solid #E5E7EB; font-size: 11px; }
        .text-right { text-align: right; }
        .metric-value { font-weight: bold; font-size: 18px; color: #111; }
        .metric-label { font-size: 10px; color: #6B7280; text-transform: uppercase; }
        .metrics-grid { margin-bottom: 24px; }
        .metrics-grid table td { border-bottom: none; text-align: center; padding: 12px 8px; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #E5E7EB; font-size: 9px; color: #9CA3AF; text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-primary { background-color: #EEF2FF; color: #4338CA; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%; vertical-align: top; border-bottom: none;">
                    <div class="company-name">BizSocials</div>
                    <div class="report-title">{{ $report['name'] }}</div>
                    @if(!empty($report['description']))
                        <p style="color: #6B7280; margin: 4px 0; font-size: 11px;">{{ $report['description'] }}</p>
                    @endif
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top; border-bottom: none;">
                    <div class="report-meta">
                        <span class="badge badge-primary">{{ strtoupper($report['type_label'] ?? $report['type']) }}</span><br><br>
                        <strong>Period:</strong> {{ $report['date_range']['from'] }} to {{ $report['date_range']['to'] }}<br>
                        <strong>Generated:</strong> {{ \Carbon\Carbon::parse($report['generated_at'])->format('d M Y, H:i') }}<br>
                        @if(!empty($workspaceName))
                            <strong>Workspace:</strong> {{ $workspaceName }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary Metrics --}}
    @if(!empty($dashboardMetrics['metrics']))
        <h2>Summary Metrics</h2>
        <div class="metrics-grid">
            <table>
                <tr>
                    @foreach(array_slice($dashboardMetrics['metrics'], 0, 5) as $key => $value)
                        <td>
                            <div class="metric-value">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                            <div class="metric-label">{{ str_replace('_', ' ', $key) }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    {{-- Engagement Trend --}}
    @if(!empty($engagementTrend))
        <h2>Engagement Trend</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Engagements</th>
                    <th class="text-right">Likes</th>
                    <th class="text-right">Comments</th>
                    <th class="text-right">Shares</th>
                    <th class="text-right">Saves</th>
                </tr>
            </thead>
            <tbody>
                @foreach($engagementTrend as $day)
                <tr>
                    <td>{{ $day['date'] ?? '' }}</td>
                    <td class="text-right">{{ number_format($day['engagements'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($day['likes'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($day['comments'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($day['shares'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($day['saves'] ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Platform Breakdown --}}
    @if(!empty($platformMetrics))
        <h2>Platform Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Platform</th>
                    <th class="text-right">Posts</th>
                    <th class="text-right">Impressions</th>
                    <th class="text-right">Reach</th>
                    <th class="text-right">Engagements</th>
                    <th class="text-right">Eng. Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($platformMetrics as $platform)
                <tr>
                    <td>{{ ucfirst($platform['platform'] ?? '') }}</td>
                    <td class="text-right">{{ number_format($platform['posts_count'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($platform['impressions'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($platform['reach'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($platform['engagements'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($platform['engagement_rate'] ?? 0, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Top Posts --}}
    @if(!empty($topPosts))
        <h2>Top Performing Posts</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Title</th>
                    <th>Platform</th>
                    <th class="text-right">Impressions</th>
                    <th class="text-right">Engagements</th>
                    <th class="text-right">Published</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($topPosts, 0, 10) as $post)
                <tr>
                    <td>{{ \Illuminate\Support\Str::limit($post['title'] ?? 'Untitled', 40) }}</td>
                    <td>{{ ucfirst($post['platform'] ?? '') }}</td>
                    <td class="text-right">{{ number_format($post['impressions'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($post['engagements'] ?? 0) }}</td>
                    <td class="text-right">{{ !empty($post['published_at']) ? \Carbon\Carbon::parse($post['published_at'])->format('d M') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Content Performance by Type --}}
    @if(!empty($byContentType))
        <h2>Performance by Content Type</h2>
        <table>
            <thead>
                <tr>
                    <th>Content Type</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Avg Impressions</th>
                    <th class="text-right">Avg Engagements</th>
                    <th class="text-right">Avg Eng. Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byContentType as $type)
                <tr>
                    <td>{{ ucfirst($type['type'] ?? '') }}</td>
                    <td class="text-right">{{ number_format($type['count'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($type['avg_impressions'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($type['avg_engagements'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($type['avg_engagement_rate'] ?? 0, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Follower Growth --}}
    @if(!empty($followerTrend))
        <h2>Follower Growth</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Followers</th>
                    <th class="text-right">Change</th>
                </tr>
            </thead>
            <tbody>
                @foreach($followerTrend as $point)
                <tr>
                    <td>{{ $point['date'] ?? '' }}</td>
                    <td class="text-right">{{ number_format($point['followers'] ?? 0) }}</td>
                    <td class="text-right" style="color: {{ ($point['change'] ?? 0) >= 0 ? '#059669' : '#DC2626' }}">
                        {{ ($point['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($point['change'] ?? 0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This report was generated by BizSocials Analytics.</p>
        <p>&copy; {{ date('Y') }} BizSocials. All rights reserved.</p>
    </div>
</body>
</html>
