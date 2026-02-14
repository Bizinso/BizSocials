<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Audit\ReportGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateComprehensiveReport extends Command
{
    protected $signature = 'audit:comprehensive-report 
                            {--format=all : Output format (markdown, json, html, all)}
                            {--output= : Output directory (default: storage/app/private/audit-reports)}';

    protected $description = 'Generate comprehensive audit report consolidating all feature area findings';

    public function handle(ReportGenerator $reportGenerator): int
    {
        $this->info('Generating comprehensive audit report...');
        $this->newLine();

        // Consolidate all findings
        $this->info('Step 1: Consolidating all feature area findings...');
        $consolidated = $reportGenerator->consolidateAllFindings();
        
        $this->info("✓ Consolidated {$consolidated['total_reports']} audit reports");
        $this->info("✓ Total findings: {$consolidated['overall_summary']['total']}");
        $this->newLine();

        // Display summary
        $this->displaySummary($consolidated['overall_summary']);
        $this->newLine();

        // Categorize findings
        $this->info('Step 2: Categorizing findings by type and severity...');
        $categorized = $reportGenerator->categorizeWithSeverity($consolidated['all_findings']);
        
        foreach ($categorized as $type => $findings) {
            if (count($findings) > 0) {
                $this->info("✓ {$type}: " . count($findings) . ' findings');
            }
        }
        $this->newLine();

        // Generate recommendations
        $this->info('Step 3: Generating recommendations...');
        $recommendations = $reportGenerator->generateRecommendations($consolidated['all_findings']);
        $this->info('✓ Generated ' . count($recommendations) . ' recommendations');
        $this->newLine();

        // Create rectification roadmap
        $this->info('Step 4: Creating prioritized rectification roadmap...');
        $roadmap = $reportGenerator->createRectificationRoadmap($consolidated['all_findings']);
        $this->info("✓ Created roadmap with {$roadmap['total_estimated_weeks']} weeks across " . count($roadmap['phases']) . ' phases');
        $this->newLine();

        // Generate executive summary
        $this->info('Step 5: Generating executive summary...');
        $executiveSummary = $reportGenerator->generateExecutiveSummary($consolidated, $roadmap);
        $this->info("✓ Platform health score: {$executiveSummary['platform_health_score']}/100");
        $this->info("✓ Completion percentage: {$executiveSummary['completion_percentage']}%");
        $this->newLine();

        // Export reports
        $format = $this->option('format');
        $outputDir = $this->option('output') ?? 'audit-reports';
        
        $this->info('Step 6: Exporting comprehensive report...');
        
        $exports = [];
        
        if (in_array($format, ['markdown', 'all'])) {
            $markdownFile = $this->exportMarkdown($consolidated, $categorized, $recommendations, $roadmap, $executiveSummary, $outputDir);
            $exports[] = $markdownFile;
            $this->info("✓ Markdown report: {$markdownFile}");
        }
        
        if (in_array($format, ['json', 'all'])) {
            $jsonFile = $this->exportJson($consolidated, $categorized, $recommendations, $roadmap, $executiveSummary, $outputDir);
            $exports[] = $jsonFile;
            $this->info("✓ JSON report: {$jsonFile}");
        }
        
        if (in_array($format, ['html', 'all'])) {
            $htmlFile = $this->exportHtml($consolidated, $categorized, $recommendations, $roadmap, $executiveSummary, $outputDir);
            $exports[] = $htmlFile;
            $this->info("✓ HTML report: {$htmlFile}");
        }

        $this->newLine();
        $this->info('✅ Comprehensive audit report generated successfully!');
        $this->newLine();
        
        foreach ($exports as $file) {
            $this->line("   📄 {$file}");
        }

        return self::SUCCESS;
    }

    private function displaySummary(array $summary): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Findings', $summary['total']],
                ['Stubs', $summary['stubs']],
                ['Incomplete', $summary['incomplete']],
                ['Missing', $summary['missing']],
                ['Complete', $summary['complete']],
                ['', ''],
                ['Critical Severity', $summary['by_severity']['critical']],
                ['High Severity', $summary['by_severity']['high']],
                ['Medium Severity', $summary['by_severity']['medium']],
                ['Low Severity', $summary['by_severity']['low']],
            ]
        );
    }

    private function exportMarkdown(array $consolidated, array $categorized, array $recommendations, array $roadmap, array $executiveSummary, string $outputDir): string
    {
        $summary = $consolidated['overall_summary'];
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $markdown = "# Comprehensive Platform Audit Report\n\n";
        $markdown .= "**Generated:** {$timestamp}\n\n";
        $markdown .= "**Total Reports Analyzed:** {$consolidated['total_reports']}\n\n";
        
        $markdown .= "## Executive Summary\n\n";
        
        // Platform Health
        $healthScore = $executiveSummary['platform_health_score'];
        $healthStatus = match(true) {
            $healthScore >= 80 => '🟢 Excellent',
            $healthScore >= 60 => '🟡 Good',
            $healthScore >= 40 => '🟠 Fair',
            default => '🔴 Poor',
        };
        
        $markdown .= "### Platform Health: {$healthStatus} ({$healthScore}/100)\n\n";
        $markdown .= "**Completion Rate:** {$executiveSummary['completion_percentage']}%\n\n";
        $markdown .= "**Estimated Rectification Time:** {$executiveSummary['estimated_rectification_time']} weeks\n\n";
        
        $markdown .= "This comprehensive audit report consolidates findings from all {$consolidated['total_reports']} feature area audits conducted on the BizSocials platform. ";
        $markdown .= "The audit identified **{$summary['total']} total findings** across the platform.\n\n";
        
        // Key Insights
        $markdown .= "### Key Insights\n\n";
        foreach ($executiveSummary['key_insights'] as $insight) {
            $markdown .= "- {$insight}\n";
        }
        $markdown .= "\n";
        
        // Critical Areas
        if (count($executiveSummary['critical_areas']) > 0) {
            $markdown .= "### Critical Areas Requiring Attention\n\n";
            $markdown .= "| Feature Area | Findings | Status |\n";
            $markdown .= "|--------------|----------|--------|\n";
            foreach ($executiveSummary['critical_areas'] as $area) {
                $markdown .= "| {$area['name']} | {$area['findings_count']} | {$area['status']} |\n";
            }
            $markdown .= "\n";
        }
        
        // Executive Recommendations
        $markdown .= "### Executive Recommendations\n\n";
        foreach ($executiveSummary['recommendations'] as $rec) {
            $markdown .= "#### {$rec['priority']} Priority: {$rec['action']}\n\n";
            $markdown .= "{$rec['description']}\n\n";
        }
        
        // Risk Assessment
        if (count($executiveSummary['risk_assessment']) > 0) {
            $markdown .= "### Risk Assessment\n\n";
            foreach ($executiveSummary['risk_assessment'] as $risk) {
                $markdown .= "#### {$risk['level']} Risk: {$risk['category']}\n\n";
                $markdown .= "**Description:** {$risk['description']}\n\n";
                $markdown .= "**Mitigation:** {$risk['mitigation']}\n\n";
            }
        }
        
        $markdown .= "### Overall Statistics\n\n";
        $markdown .= "| Category | Count | Percentage |\n";
        $markdown .= "|----------|-------|------------|\n";
        $markdown .= "| **Total Findings** | {$summary['total']} | 100% |\n";
        
        if ($summary['total'] > 0) {
            $stubPct = round(($summary['stubs'] / $summary['total']) * 100, 1);
            $incompletePct = round(($summary['incomplete'] / $summary['total']) * 100, 1);
            $missingPct = round(($summary['missing'] / $summary['total']) * 100, 1);
            $completePct = round(($summary['complete'] / $summary['total']) * 100, 1);
            
            $markdown .= "| Stub Implementations | {$summary['stubs']} | {$stubPct}% |\n";
            $markdown .= "| Incomplete Features | {$summary['incomplete']} | {$incompletePct}% |\n";
            $markdown .= "| Missing Features | {$summary['missing']} | {$missingPct}% |\n";
            $markdown .= "| Complete Features | {$summary['complete']} | {$completePct}% |\n";
        }
        
        $markdown .= "\n### Findings by Severity\n\n";
        $markdown .= "| Severity | Count | Priority |\n";
        $markdown .= "|----------|-------|----------|\n";
        $markdown .= "| 🔴 Critical | {$summary['by_severity']['critical']} | Immediate action required |\n";
        $markdown .= "| 🟠 High | {$summary['by_severity']['high']} | Address in current sprint |\n";
        $markdown .= "| 🟡 Medium | {$summary['by_severity']['medium']} | Plan for next sprint |\n";
        $markdown .= "| 🟢 Low | {$summary['by_severity']['low']} | Address as time permits |\n\n";
        
        $markdown .= "## Prioritized Rectification Roadmap\n\n";
        $markdown .= "**Total Estimated Time:** {$roadmap['total_estimated_weeks']} weeks\n\n";
        $markdown .= "The rectification work has been organized into " . count($roadmap['phases']) . " phases based on priority, dependencies, and estimated effort.\n\n";
        
        foreach ($roadmap['phases'] as $phase) {
            $markdown .= "### Phase {$phase['phase']}: {$phase['name']}\n\n";
            $markdown .= "- **Duration:** {$phase['estimated_weeks']} weeks\n";
            $markdown .= "- **Findings:** {$phase['findings_count']}\n\n";
            
            $markdown .= "| # | Description | Feature Area | Severity | Effort |\n";
            $markdown .= "|---|-------------|--------------|----------|--------|\n";
            
            foreach ($phase['findings'] as $i => $finding) {
                $num = $i + 1;
                $desc = substr($finding['description'], 0, 60) . (strlen($finding['description']) > 60 ? '...' : '');
                $markdown .= "| {$num} | {$desc} | {$finding['feature_area']} | {$finding['severity']} | {$finding['estimated_days']}d |\n";
            }
            
            $markdown .= "\n";
        }
        
        $markdown .= "## Feature Area Breakdown\n\n";
        foreach ($consolidated['feature_areas'] as $featureArea => $data) {
            $markdown .= "### {$featureArea}\n\n";
            $markdown .= "- **Status:** {$data['status']}\n";
            $markdown .= "- **Completed:** {$data['completed_at']}\n";
            $markdown .= "- **Findings:** {$data['findings_count']}\n\n";
        }
        
        $markdown .= "## Detailed Findings by Category\n\n";
        
        foreach ($categorized as $type => $findings) {
            if (count($findings) === 0) {
                continue;
            }
            
            $markdown .= "### " . ucfirst($type) . " (" . count($findings) . ")\n\n";
            
            foreach ($findings as $finding) {
                $severityEmoji = match($finding['severity']) {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪',
                };
                
                $markdown .= "#### {$severityEmoji} {$finding['description']}\n\n";
                $markdown .= "- **Feature Area:** {$finding['feature_area']}\n";
                $markdown .= "- **Location:** `{$finding['location']}`\n";
                $markdown .= "- **Severity:** {$finding['severity']}\n";
                $markdown .= "- **Status:** {$finding['status']}\n";
                
                if (!empty($finding['evidence'])) {
                    $markdown .= "\n**Evidence:**\n```\n{$finding['evidence']}\n```\n";
                }
                
                if (!empty($finding['recommendation'])) {
                    $markdown .= "\n**Recommendation:** {$finding['recommendation']}\n";
                }
                
                $markdown .= "\n---\n\n";
            }
        }
        
        $markdown .= "## Recommendations\n\n";
        foreach ($recommendations as $i => $recommendation) {
            $markdown .= ($i + 1) . ". {$recommendation}\n";
        }
        
        $markdown .= "\n## Next Steps\n\n";
        $markdown .= "1. Review and prioritize critical and high-severity findings\n";
        $markdown .= "2. Create rectification tasks for each finding\n";
        $markdown .= "3. Assign findings to development team members\n";
        $markdown .= "4. Establish timeline for addressing each category\n";
        $markdown .= "5. Set up tracking system for monitoring progress\n";
        
        $filename = "{$outputDir}/COMPREHENSIVE_AUDIT_REPORT.md";
        Storage::disk('local')->put($filename, $markdown);
        
        return $filename;
    }

    private function exportJson(array $consolidated, array $categorized, array $recommendations, array $roadmap, array $executiveSummary, string $outputDir): string
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'executive_summary' => $executiveSummary,
            'total_reports' => $consolidated['total_reports'],
            'overall_summary' => $consolidated['overall_summary'],
            'feature_areas' => $consolidated['feature_areas'],
            'categorized_findings' => $categorized,
            'recommendations' => $recommendations,
            'rectification_roadmap' => $roadmap,
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = "{$outputDir}/comprehensive-audit-report.json";
        Storage::disk('local')->put($filename, $json);
        
        return $filename;
    }

    private function exportHtml(array $consolidated, array $categorized, array $recommendations, array $roadmap, array $executiveSummary, string $outputDir): string
    {
        $summary = $consolidated['overall_summary'];
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Platform Audit Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 4px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #555;
            margin-top: 40px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        h3 {
            color: #666;
            margin-top: 30px;
        }
        .meta {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            opacity: 0.9;
        }
        .summary-card .value {
            font-size: 42px;
            font-weight: bold;
            margin: 10px 0;
        }
        .summary-card .label {
            font-size: 13px;
            opacity: 0.8;
        }
        .severity-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .severity-card {
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .severity-card.critical {
            background: #fee;
            border-left: 4px solid #dc3545;
        }
        .severity-card.high {
            background: #fff3e0;
            border-left: 4px solid #fd7e14;
        }
        .severity-card.medium {
            background: #fffbea;
            border-left: 4px solid #ffc107;
        }
        .severity-card.low {
            background: #e8f5e9;
            border-left: 4px solid #28a745;
        }
        .severity-card .count {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .severity-card .label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .feature-area {
            background: #f8f9fa;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        .finding {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            margin: 15px 0;
        }
        .finding-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 8px;
        }
        .badge-critical { background: #dc3545; color: white; }
        .badge-high { background: #fd7e14; color: white; }
        .badge-medium { background: #ffc107; color: #333; }
        .badge-low { background: #28a745; color: white; }
        .location {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .evidence {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 12px 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .recommendation {
            background: #e7f3ff;
            border-left: 3px solid #007bff;
            padding: 12px 15px;
            margin: 15px 0;
        }
        .recommendations-list {
            background: #f8f9fa;
            padding: 20px 30px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .recommendations-list li {
            margin: 10px 0;
        }
        .health-score {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin: 20px 0;
        }
        .health-score .score {
            font-size: 72px;
            font-weight: bold;
            margin: 10px 0;
        }
        .health-score .label {
            font-size: 18px;
            opacity: 0.9;
        }
        .insight-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .risk-box {
            background: #fff3e0;
            border-left: 4px solid #fd7e14;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .risk-box.high {
            background: #fee;
            border-left-color: #dc3545;
        }
        .risk-box.medium {
            background: #fffbea;
            border-left-color: #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Comprehensive Platform Audit Report</h1>
        
        <div class="meta">
            <strong>Generated:</strong> {$timestamp}<br>
            <strong>Total Reports Analyzed:</strong> {$consolidated['total_reports']}<br>
            <strong>Total Findings:</strong> {$summary['total']}
        </div>

        <h2>Executive Summary</h2>
        <p>This comprehensive audit report consolidates findings from all {$consolidated['total_reports']} feature area audits conducted on the BizSocials platform. The audit identified <strong>{$summary['total']} total findings</strong> across the platform.</p>

        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Findings</h3>
                <div class="value">{$summary['total']}</div>
                <div class="label">Across all feature areas</div>
            </div>
            <div class="summary-card">
                <h3>Stub Implementations</h3>
                <div class="value">{$summary['stubs']}</div>
                <div class="label">Require real implementation</div>
            </div>
            <div class="summary-card">
                <h3>Incomplete Features</h3>
                <div class="value">{$summary['incomplete']}</div>
                <div class="label">Need completion</div>
            </div>
            <div class="summary-card">
                <h3>Missing Features</h3>
                <div class="value">{$summary['missing']}</div>
                <div class="label">Not yet implemented</div>
            </div>
        </div>

        <h2>Findings by Severity</h2>
        <div class="severity-grid">
            <div class="severity-card critical">
                <div class="label">🔴 Critical</div>
                <div class="count">{$summary['by_severity']['critical']}</div>
                <small>Immediate action required</small>
            </div>
            <div class="severity-card high">
                <div class="label">🟠 High</div>
                <div class="count">{$summary['by_severity']['high']}</div>
                <small>Address in current sprint</small>
            </div>
            <div class="severity-card medium">
                <div class="label">🟡 Medium</div>
                <div class="count">{$summary['by_severity']['medium']}</div>
                <small>Plan for next sprint</small>
            </div>
            <div class="severity-card low">
                <div class="label">🟢 Low</div>
                <div class="count">{$summary['by_severity']['low']}</div>
                <small>Address as time permits</small>
            </div>
        </div>

        <h2>Feature Area Breakdown</h2>
HTML;

        foreach ($consolidated['feature_areas'] as $featureArea => $data) {
            $html .= <<<HTML

        <div class="feature-area">
            <strong>{$featureArea}</strong><br>
            <small>Status: {$data['status']} | Completed: {$data['completed_at']} | Findings: {$data['findings_count']}</small>
        </div>
HTML;
        }

        $html .= "\n        <h2>Prioritized Rectification Roadmap</h2>\n";
        $html .= "        <p><strong>Total Estimated Time:</strong> {$roadmap['total_estimated_weeks']} weeks</p>\n";
        $html .= "        <p>The rectification work has been organized into " . count($roadmap['phases']) . " phases based on priority, dependencies, and estimated effort.</p>\n";

        foreach ($roadmap['phases'] as $phase) {
            $html .= <<<HTML

        <h3>Phase {$phase['phase']}: {$phase['name']}</h3>
        <p><strong>Duration:</strong> {$phase['estimated_weeks']} weeks | <strong>Findings:</strong> {$phase['findings_count']}</p>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Feature Area</th>
                    <th>Severity</th>
                    <th>Effort</th>
                </tr>
            </thead>
            <tbody>
HTML;

            foreach ($phase['findings'] as $i => $finding) {
                $num = $i + 1;
                $desc = htmlspecialchars(substr($finding['description'], 0, 80) . (strlen($finding['description']) > 80 ? '...' : ''));
                $featureArea = htmlspecialchars($finding['feature_area']);
                $severity = htmlspecialchars($finding['severity']);
                $effort = htmlspecialchars($finding['estimated_days'] . 'd');

                $html .= <<<HTML

                <tr>
                    <td>{$num}</td>
                    <td>{$desc}</td>
                    <td>{$featureArea}</td>
                    <td><span class="badge badge-{$severity}">{$severity}</span></td>
                    <td>{$effort}</td>
                </tr>
HTML;
            }

            $html .= <<<HTML

            </tbody>
        </table>
HTML;
        }

        $html .= "\n        <h2>Detailed Findings by Category</h2>\n";

        foreach ($categorized as $type => $findings) {
            if (count($findings) === 0) {
                continue;
            }

            $html .= "\n        <h3>" . ucfirst($type) . " (" . count($findings) . ")</h3>\n";

            foreach ($findings as $finding) {
                $severity = htmlspecialchars($finding['severity']);
                $description = htmlspecialchars($finding['description']);
                $location = htmlspecialchars($finding['location']);
                $featureArea = htmlspecialchars($finding['feature_area']);
                $status = htmlspecialchars($finding['status']);

                $html .= <<<HTML

        <div class="finding">
            <div class="finding-header">
                <div>
                    <span class="badge badge-{$severity}">{$severity}</span>
                    <span class="location">{$location}</span>
                </div>
                <small>{$featureArea}</small>
            </div>
            <p><strong>{$description}</strong></p>
            <p><small>Status: {$status}</small></p>
HTML;

                if (!empty($finding['evidence'])) {
                    $evidence = htmlspecialchars($finding['evidence']);
                    $html .= <<<HTML

            <div class="evidence">
                <strong>Evidence:</strong><br>
                {$evidence}
            </div>
HTML;
                }

                if (!empty($finding['recommendation'])) {
                    $recommendation = htmlspecialchars($finding['recommendation']);
                    $html .= <<<HTML

            <div class="recommendation">
                <strong>Recommendation:</strong> {$recommendation}
            </div>
HTML;
                }

                $html .= "        </div>\n";
            }
        }

        $html .= "\n        <h2>Recommendations</h2>\n        <div class=\"recommendations-list\">\n            <ol>\n";
        foreach ($recommendations as $recommendation) {
            $rec = htmlspecialchars($recommendation);
            $html .= "                <li>{$rec}</li>\n";
        }
        $html .= "            </ol>\n        </div>\n";

        $html .= <<<HTML

        <h2>Next Steps</h2>
        <ol>
            <li>Review and prioritize critical and high-severity findings</li>
            <li>Create rectification tasks for each finding</li>
            <li>Assign findings to development team members</li>
            <li>Establish timeline for addressing each category</li>
            <li>Set up tracking system for monitoring progress</li>
        </ol>
    </div>
</body>
</html>
HTML;

        $filename = "{$outputDir}/comprehensive-audit-report.html";
        Storage::disk('local')->put($filename, $html);

        return $filename;
    }
}
