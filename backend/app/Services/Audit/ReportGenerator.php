<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\Audit\AuditFinding;
use App\Models\Audit\AuditReport;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportGenerator extends BaseService
{
    /**
     * Generate an audit report for a feature area.
     */
    public function generateReport(string $featureArea, array $findings, array $recommendations = []): AuditReport
    {
        try {
            $summary = $this->generateSummary($findings);

            $report = AuditReport::create([
                'feature_area' => $featureArea,
                'findings' => $findings,
                'summary' => $summary,
                'recommendations' => $recommendations,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create individual finding records
            foreach ($findings as $finding) {
                AuditFinding::create([
                    'audit_report_id' => $report->id,
                    'type' => $finding['type'] ?? 'unknown',
                    'severity' => $finding['severity'] ?? 'medium',
                    'location' => $finding['location'] ?? '',
                    'description' => $finding['description'] ?? '',
                    'evidence' => $finding['evidence'] ?? null,
                    'recommendation' => $finding['recommendation'] ?? null,
                    'status' => 'open',
                ]);
            }

            $this->log("Generated audit report for {$featureArea}", [
                'report_id' => $report->id,
                'findings_count' => count($findings),
            ]);

            return $report;
        } catch (Throwable $e) {
            $this->handleException($e, "Failed to generate audit report for {$featureArea}");
        }
    }

    /**
     * Generate summary statistics from findings.
     */
    public function generateSummary(array $findings): array
    {
        $summary = [
            'total' => count($findings),
            'stubs' => 0,
            'incomplete' => 0,
            'missing' => 0,
            'complete' => 0,
            'by_severity' => [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
        ];

        foreach ($findings as $finding) {
            $type = $finding['type'] ?? 'unknown';
            $severity = $finding['severity'] ?? 'medium';

            // Count by type
            if (isset($summary[$type])) {
                $summary[$type]++;
            }

            // Count by severity
            if (isset($summary['by_severity'][$severity])) {
                $summary['by_severity'][$severity]++;
            }
        }

        return $summary;
    }

    /**
     * Export report as JSON.
     */
    public function exportAsJson(AuditReport $report): string
    {
        $data = [
            'id' => $report->id,
            'feature_area' => $report->feature_area,
            'status' => $report->status,
            'completed_at' => $report->completed_at?->toIso8601String(),
            'summary' => $report->summary,
            'findings' => $report->findings,
            'recommendations' => $report->recommendations,
            'created_at' => $report->created_at->toIso8601String(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Save to storage
        $filename = "audit-reports/{$report->feature_area}-{$report->id}.json";
        Storage::disk('local')->put($filename, $json);

        $this->log("Exported audit report as JSON", [
            'report_id' => $report->id,
            'filename' => $filename,
        ]);

        return $filename;
    }

    /**
     * Export report as HTML.
     */
    public function exportAsHtml(AuditReport $report): string
    {
        $html = $this->generateHtmlReport($report);

        // Save to storage
        $filename = "audit-reports/{$report->feature_area}-{$report->id}.html";
        Storage::disk('local')->put($filename, $html);

        $this->log("Exported audit report as HTML", [
            'report_id' => $report->id,
            'filename' => $filename,
        ]);

        return $filename;
    }

    /**
     * Generate HTML report content.
     */
    private function generateHtmlReport(AuditReport $report): string
    {
        $summary = $report->summary;
        $findings = $report->findings;
        $recommendations = $report->recommendations;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - {$report->feature_area}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
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
            margin-bottom: 10px;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-critical { background: #dc3545; color: white; }
        .badge-high { background: #fd7e14; color: white; }
        .badge-medium { background: #ffc107; color: #333; }
        .badge-low { background: #28a745; color: white; }
        .badge-stub { background: #6c757d; color: white; }
        .badge-incomplete { background: #17a2b8; color: white; }
        .badge-missing { background: #dc3545; color: white; }
        .badge-complete { background: #28a745; color: white; }
        .location {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        .evidence {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 10px 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        .recommendation {
            background: #e7f3ff;
            border-left: 3px solid #007bff;
            padding: 10px 15px;
            margin: 10px 0;
        }
        .meta {
            color: #666;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Audit Report: {$report->feature_area}</h1>
        
        <div class="meta">
            <strong>Report ID:</strong> {$report->id}<br>
            <strong>Status:</strong> {$report->status}<br>
            <strong>Completed:</strong> {$report->completed_at?->format('Y-m-d H:i:s')}<br>
            <strong>Generated:</strong> {$report->created_at->format('Y-m-d H:i:s')}
        </div>

        <h2>Summary</h2>
        <div class="summary">
            <div class="summary-card">
                <h3>Total Findings</h3>
                <div class="value">{$summary['total']}</div>
            </div>
            <div class="summary-card">
                <h3>Stubs</h3>
                <div class="value">{$summary['stubs']}</div>
            </div>
            <div class="summary-card">
                <h3>Incomplete</h3>
                <div class="value">{$summary['incomplete']}</div>
            </div>
            <div class="summary-card">
                <h3>Missing</h3>
                <div class="value">{$summary['missing']}</div>
            </div>
        </div>

        <h2>Findings by Severity</h2>
        <div class="summary">
            <div class="summary-card">
                <h3>Critical</h3>
                <div class="value">{$summary['by_severity']['critical']}</div>
            </div>
            <div class="summary-card">
                <h3>High</h3>
                <div class="value">{$summary['by_severity']['high']}</div>
            </div>
            <div class="summary-card">
                <h3>Medium</h3>
                <div class="value">{$summary['by_severity']['medium']}</div>
            </div>
            <div class="summary-card">
                <h3>Low</h3>
                <div class="value">{$summary['by_severity']['low']}</div>
            </div>
        </div>

        <h2>Detailed Findings</h2>
HTML;

        foreach ($findings as $finding) {
            $type = $finding['type'] ?? 'unknown';
            $severity = $finding['severity'] ?? 'medium';
            $location = htmlspecialchars($finding['location'] ?? 'Unknown');
            $description = htmlspecialchars($finding['description'] ?? 'No description');
            $evidence = isset($finding['evidence']) ? htmlspecialchars($finding['evidence']) : null;
            $recommendation = isset($finding['recommendation']) ? htmlspecialchars($finding['recommendation']) : null;

            $html .= <<<HTML

        <div class="finding">
            <div class="finding-header">
                <div>
                    <span class="badge badge-{$type}">{$type}</span>
                    <span class="badge badge-{$severity}">{$severity}</span>
                </div>
                <span class="location">{$location}</span>
            </div>
            <p><strong>Description:</strong> {$description}</p>
HTML;

            if ($evidence) {
                $html .= <<<HTML

            <div class="evidence">
                <strong>Evidence:</strong><br>
                {$evidence}
            </div>
HTML;
            }

            if ($recommendation) {
                $html .= <<<HTML

            <div class="recommendation">
                <strong>Recommendation:</strong> {$recommendation}
            </div>
HTML;
            }

            $html .= '        </div>';
        }

        if (! empty($recommendations)) {
            $html .= "\n        <h2>Recommendations</h2>\n        <ul>\n";
            foreach ($recommendations as $rec) {
                $recText = htmlspecialchars($rec);
                $html .= "            <li>{$recText}</li>\n";
            }
            $html .= "        </ul>\n";
        }

        $html .= <<<HTML

    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Categorize findings by type.
     */
    public function categorizeFindings(array $findings): array
    {
        $categorized = [
            'stub' => [],
            'incomplete' => [],
            'missing' => [],
            'complete' => [],
        ];

        foreach ($findings as $finding) {
            $type = $finding['type'] ?? 'unknown';
            if (isset($categorized[$type])) {
                $categorized[$type][] = $finding;
            }
        }

        return $categorized;
    }

    /**
     * Generate recommendations based on findings.
     */
    public function generateRecommendations(array $findings): array
    {
        $recommendations = [];
        $summary = $this->generateSummary($findings);

        if ($summary['stubs'] > 0) {
            $recommendations[] = "Replace {$summary['stubs']} stub implementation(s) with real database-backed logic";
        }

        if ($summary['incomplete'] > 0) {
            $recommendations[] = "Complete {$summary['incomplete']} incomplete implementation(s) with proper error handling and validation";
        }

        if ($summary['missing'] > 0) {
            $recommendations[] = "Implement {$summary['missing']} missing feature(s) according to specifications";
        }

        if ($summary['by_severity']['critical'] > 0) {
            $recommendations[] = "Address {$summary['by_severity']['critical']} critical issue(s) immediately";
        }

        if ($summary['by_severity']['high'] > 0) {
            $recommendations[] = "Prioritize {$summary['by_severity']['high']} high-severity issue(s) in next sprint";
        }

        return $recommendations;
    }

    /**
     * Consolidate all feature area findings into a comprehensive report.
     */
    public function consolidateAllFindings(): array
    {
        try {
            $reports = AuditReport::with('auditFindings')->get();
            
            $consolidated = [
                'total_reports' => $reports->count(),
                'feature_areas' => [],
                'all_findings' => [],
                'overall_summary' => [
                    'total' => 0,
                    'stubs' => 0,
                    'incomplete' => 0,
                    'missing' => 0,
                    'complete' => 0,
                    'by_severity' => [
                        'critical' => 0,
                        'high' => 0,
                        'medium' => 0,
                        'low' => 0,
                    ],
                ],
            ];

            foreach ($reports as $report) {
                $featureArea = $report->feature_area;
                $findings = $report->auditFindings;
                
                // Aggregate findings from database
                $featureFindings = [];
                foreach ($findings as $finding) {
                    $findingData = [
                        'id' => $finding->id,
                        'type' => $finding->type,
                        'severity' => $finding->severity,
                        'location' => $finding->location,
                        'description' => $finding->description,
                        'evidence' => $finding->evidence,
                        'recommendation' => $finding->recommendation,
                        'status' => $finding->status,
                        'feature_area' => $featureArea,
                    ];
                    
                    $featureFindings[] = $findingData;
                    $consolidated['all_findings'][] = $findingData;
                    
                    // Update overall summary
                    $consolidated['overall_summary']['total']++;
                    
                    // Count by type
                    if (in_array($finding->type, ['stub', 'incomplete', 'missing', 'complete'])) {
                        $consolidated['overall_summary'][$finding->type]++;
                    }
                    
                    // Count by severity
                    if (isset($consolidated['overall_summary']['by_severity'][$finding->severity])) {
                        $consolidated['overall_summary']['by_severity'][$finding->severity]++;
                    }
                }
                
                $consolidated['feature_areas'][$featureArea] = [
                    'report_id' => $report->id,
                    'status' => $report->status,
                    'completed_at' => $report->completed_at?->toIso8601String(),
                    'findings_count' => count($featureFindings),
                    'findings' => $featureFindings,
                    'summary' => $report->summary ?? [],
                ];
            }

            $this->log('Consolidated all audit findings', [
                'total_reports' => $consolidated['total_reports'],
                'total_findings' => $consolidated['overall_summary']['total'],
            ]);

            return $consolidated;
        } catch (Throwable $e) {
            $this->handleException($e, 'Failed to consolidate audit findings');
        }
    }

    /**
     * Assign severity levels to findings based on type and impact.
     */
    public function assignSeverityLevels(array &$findings): void
    {
        foreach ($findings as &$finding) {
            // If severity is already assigned, skip
            if (isset($finding['severity']) && $finding['severity'] !== 'medium') {
                continue;
            }

            $type = $finding['type'] ?? 'unknown';
            $location = strtolower($finding['location'] ?? '');
            $description = strtolower($finding['description'] ?? '');

            // Determine severity based on type and context
            if ($type === 'missing') {
                // Missing implementations are critical if they're core features
                if (str_contains($location, 'auth') || 
                    str_contains($location, 'payment') || 
                    str_contains($location, 'billing') ||
                    str_contains($description, 'security') ||
                    str_contains($description, 'authentication')) {
                    $finding['severity'] = 'critical';
                } else {
                    $finding['severity'] = 'high';
                }
            } elseif ($type === 'stub') {
                // Stubs are high priority if they're in critical paths
                if (str_contains($location, 'payment') || 
                    str_contains($location, 'billing') ||
                    str_contains($location, 'oauth') ||
                    str_contains($description, 'api call') ||
                    str_contains($description, 'database')) {
                    $finding['severity'] = 'high';
                } else {
                    $finding['severity'] = 'medium';
                }
            } elseif ($type === 'incomplete') {
                // Incomplete implementations are medium unless they affect critical features
                if (str_contains($location, 'auth') || 
                    str_contains($location, 'payment') ||
                    str_contains($description, 'validation') ||
                    str_contains($description, 'error handling')) {
                    $finding['severity'] = 'high';
                } else {
                    $finding['severity'] = 'medium';
                }
            } elseif ($type === 'complete') {
                $finding['severity'] = 'low';
            } else {
                $finding['severity'] = 'medium';
            }
        }
    }

    /**
     * Categorize findings by type with severity assignment.
     */
    public function categorizeWithSeverity(array $findings): array
    {
        // Assign severity levels first
        $this->assignSeverityLevels($findings);
        
        $categorized = [
            'stub' => [],
            'incomplete' => [],
            'missing' => [],
            'complete' => [],
        ];

        foreach ($findings as $finding) {
            $type = $finding['type'] ?? 'unknown';
            if (isset($categorized[$type])) {
                $categorized[$type][] = $finding;
            }
        }

        // Sort each category by severity
        foreach ($categorized as $type => &$items) {
            usort($items, function ($a, $b) {
                $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
                $aSeverity = $severityOrder[$a['severity'] ?? 'medium'] ?? 2;
                $bSeverity = $severityOrder[$b['severity'] ?? 'medium'] ?? 2;
                return $aSeverity <=> $bSeverity;
            });
        }

        return $categorized;
    }

    /**
     * Create prioritized rectification roadmap.
     */
    public function createRectificationRoadmap(array $findings): array
    {
        try {
            // Assign severity levels
            $this->assignSeverityLevels($findings);
            
            // Sort findings by priority (severity + dependencies)
            $prioritized = $this->prioritizeFindings($findings);
            
            // Estimate effort for each finding
            $withEffort = $this->estimateEffort($prioritized);
            
            // Group into phases
            $phases = $this->groupIntoPhases($withEffort);
            
            $roadmap = [
                'total_findings' => count($findings),
                'total_estimated_weeks' => $this->calculateTotalWeeks($phases),
                'phases' => $phases,
                'prioritized_findings' => $withEffort,
            ];

            $this->log('Created rectification roadmap', [
                'total_findings' => $roadmap['total_findings'],
                'total_weeks' => $roadmap['total_estimated_weeks'],
                'phases' => count($phases),
            ]);

            return $roadmap;
        } catch (Throwable $e) {
            $this->handleException($e, 'Failed to create rectification roadmap');
        }
    }

    /**
     * Prioritize findings by severity and dependencies.
     */
    private function prioritizeFindings(array $findings): array
    {
        // Define dependency order (features that others depend on)
        $dependencyOrder = [
            'auth' => 1,
            'workspace' => 2,
            'user' => 3,
            'social' => 4,
            'content' => 5,
            'inbox' => 6,
            'analytics' => 7,
            'workflow' => 8,
            'whatsapp' => 9,
            'support' => 10,
            'knowledgebase' => 11,
            'billing' => 12,
        ];

        usort($findings, function ($a, $b) use ($dependencyOrder) {
            // First, sort by severity
            $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            $aSeverity = $severityOrder[$a['severity'] ?? 'medium'] ?? 2;
            $bSeverity = $severityOrder[$b['severity'] ?? 'medium'] ?? 2;
            
            if ($aSeverity !== $bSeverity) {
                return $aSeverity <=> $bSeverity;
            }
            
            // Then, sort by dependency order
            $aFeature = strtolower($a['feature_area'] ?? '');
            $bFeature = strtolower($b['feature_area'] ?? '');
            
            $aOrder = 999;
            $bOrder = 999;
            
            foreach ($dependencyOrder as $key => $order) {
                if (str_contains($aFeature, $key)) {
                    $aOrder = min($aOrder, $order);
                }
                if (str_contains($bFeature, $key)) {
                    $bOrder = min($bOrder, $order);
                }
            }
            
            return $aOrder <=> $bOrder;
        });

        return $findings;
    }

    /**
     * Estimate effort for each finding.
     */
    private function estimateEffort(array $findings): array
    {
        foreach ($findings as &$finding) {
            $type = $finding['type'] ?? 'unknown';
            $location = strtolower($finding['location'] ?? '');
            $description = strtolower($finding['description'] ?? '');
            
            // Base effort by type
            $effort = match($type) {
                'stub' => 3, // 3 days
                'incomplete' => 2, // 2 days
                'missing' => 5, // 5 days
                'complete' => 0,
                default => 2,
            };
            
            // Adjust based on complexity indicators
            if (str_contains($location, 'oauth') || str_contains($description, 'oauth')) {
                $effort += 2;
            }
            if (str_contains($location, 'api') || str_contains($description, 'api integration')) {
                $effort += 2;
            }
            if (str_contains($description, 'webhook')) {
                $effort += 1;
            }
            if (str_contains($description, 'payment') || str_contains($description, 'billing')) {
                $effort += 2;
            }
            if (str_contains($description, 'real-time') || str_contains($description, 'websocket')) {
                $effort += 2;
            }
            
            $finding['estimated_days'] = $effort;
            $finding['estimated_weeks'] = round($effort / 5, 1);
        }

        return $findings;
    }

    /**
     * Group findings into implementation phases.
     */
    private function groupIntoPhases(array $findings): array
    {
        $phases = [];
        $currentPhase = 1;
        $currentWeeks = 0;
        $maxWeeksPerPhase = 3; // Max 3 weeks per phase
        
        $phaseFindings = [];
        
        foreach ($findings as $finding) {
            $weeks = $finding['estimated_weeks'] ?? 0.4;
            
            // If adding this finding exceeds max weeks, start new phase
            if ($currentWeeks + $weeks > $maxWeeksPerPhase && count($phaseFindings) > 0) {
                $phases[] = [
                    'phase' => $currentPhase,
                    'name' => $this->generatePhaseName($phaseFindings),
                    'estimated_weeks' => round($currentWeeks, 1),
                    'findings_count' => count($phaseFindings),
                    'findings' => $phaseFindings,
                ];
                
                $currentPhase++;
                $currentWeeks = 0;
                $phaseFindings = [];
            }
            
            $phaseFindings[] = $finding;
            $currentWeeks += $weeks;
        }
        
        // Add remaining findings as final phase
        if (count($phaseFindings) > 0) {
            $phases[] = [
                'phase' => $currentPhase,
                'name' => $this->generatePhaseName($phaseFindings),
                'estimated_weeks' => round($currentWeeks, 1),
                'findings_count' => count($phaseFindings),
                'findings' => $phaseFindings,
            ];
        }

        return $phases;
    }

    /**
     * Generate a descriptive name for a phase based on its findings.
     */
    private function generatePhaseName(array $findings): string
    {
        if (empty($findings)) {
            return 'Miscellaneous';
        }
        
        // Get most common feature area
        $featureAreas = array_count_values(array_column($findings, 'feature_area'));
        arsort($featureAreas);
        $primaryFeature = array_key_first($featureAreas);
        
        // Get severity distribution
        $criticalCount = count(array_filter($findings, fn($f) => ($f['severity'] ?? '') === 'critical'));
        $highCount = count(array_filter($findings, fn($f) => ($f['severity'] ?? '') === 'high'));
        
        if ($criticalCount > 0) {
            return "{$primaryFeature} - Critical Issues";
        } elseif ($highCount > count($findings) / 2) {
            return "{$primaryFeature} - High Priority";
        } else {
            return "{$primaryFeature} - Improvements";
        }
    }

    /**
     * Calculate total weeks across all phases.
     */
    private function calculateTotalWeeks(array $phases): float
    {
        return round(array_sum(array_column($phases, 'estimated_weeks')), 1);
    }

    /**
     * Generate executive summary report.
     */
    public function generateExecutiveSummary(array $consolidated, array $roadmap): array
    {
        try {
            $summary = $consolidated['overall_summary'];
            $totalFindings = $summary['total'];
            
            // Calculate platform health score (0-100)
            $healthScore = $this->calculatePlatformHealth($summary);
            
            // Identify critical areas
            $criticalAreas = $this->identifyCriticalAreas($consolidated['feature_areas']);
            
            // Generate key insights
            $insights = $this->generateKeyInsights($summary, $consolidated['feature_areas']);
            
            // Calculate completion percentage
            $completionPct = $totalFindings > 0 
                ? round(($summary['complete'] / $totalFindings) * 100, 1) 
                : 0;
            
            $executiveSummary = [
                'platform_health_score' => $healthScore,
                'completion_percentage' => $completionPct,
                'total_findings' => $totalFindings,
                'critical_findings' => $summary['by_severity']['critical'],
                'high_priority_findings' => $summary['by_severity']['high'],
                'estimated_rectification_time' => $roadmap['total_estimated_weeks'],
                'critical_areas' => $criticalAreas,
                'key_insights' => $insights,
                'recommendations' => $this->generateExecutiveRecommendations($summary, $criticalAreas),
                'risk_assessment' => $this->assessRisks($summary, $consolidated['feature_areas']),
            ];

            $this->log('Generated executive summary', [
                'health_score' => $healthScore,
                'completion_pct' => $completionPct,
            ]);

            return $executiveSummary;
        } catch (Throwable $e) {
            $this->handleException($e, 'Failed to generate executive summary');
        }
    }

    /**
     * Calculate platform health score (0-100).
     */
    private function calculatePlatformHealth(array $summary): int
    {
        $total = $summary['total'];
        
        if ($total === 0) {
            return 100;
        }
        
        // Scoring weights
        $completeWeight = 100;
        $stubPenalty = 30;
        $incompletePenalty = 20;
        $missingPenalty = 40;
        $criticalPenalty = 15;
        $highPenalty = 10;
        
        $score = 100;
        
        // Deduct for incomplete implementations
        $score -= ($summary['stubs'] * $stubPenalty) / max($total, 1);
        $score -= ($summary['incomplete'] * $incompletePenalty) / max($total, 1);
        $score -= ($summary['missing'] * $missingPenalty) / max($total, 1);
        
        // Additional penalty for severity
        $score -= ($summary['by_severity']['critical'] * $criticalPenalty) / max($total, 1);
        $score -= ($summary['by_severity']['high'] * $highPenalty) / max($total, 1);
        
        return max(0, min(100, (int) round($score)));
    }

    /**
     * Identify critical areas that need immediate attention.
     */
    private function identifyCriticalAreas(array $featureAreas): array
    {
        $critical = [];
        
        foreach ($featureAreas as $name => $data) {
            if ($data['findings_count'] > 0) {
                $critical[] = [
                    'name' => $name,
                    'findings_count' => $data['findings_count'],
                    'status' => $data['status'],
                ];
            }
        }
        
        // Sort by findings count descending
        usort($critical, fn($a, $b) => $b['findings_count'] <=> $a['findings_count']);
        
        return array_slice($critical, 0, 5); // Top 5 critical areas
    }

    /**
     * Generate key insights from the audit.
     */
    private function generateKeyInsights(array $summary, array $featureAreas): array
    {
        $insights = [];
        $total = $summary['total'];
        
        if ($total === 0) {
            $insights[] = 'Platform audit found no issues - all features are fully implemented.';
            return $insights;
        }
        
        // Insight about stub implementations
        if ($summary['stubs'] > 0) {
            $stubPct = round(($summary['stubs'] / $total) * 100, 1);
            $insights[] = "{$stubPct}% of findings are stub implementations that return hardcoded data instead of real database queries or API calls.";
        }
        
        // Insight about incomplete features
        if ($summary['incomplete'] > 0) {
            $incompletePct = round(($summary['incomplete'] / $total) * 100, 1);
            $insights[] = "{$incompletePct}% of findings are incomplete implementations that need proper error handling, validation, or additional functionality.";
        }
        
        // Insight about missing features
        if ($summary['missing'] > 0) {
            $missingPct = round(($summary['missing'] / $total) * 100, 1);
            $insights[] = "{$missingPct}% of findings are completely missing features that need to be implemented from scratch.";
        }
        
        // Insight about severity distribution
        $criticalAndHigh = $summary['by_severity']['critical'] + $summary['by_severity']['high'];
        if ($criticalAndHigh > 0) {
            $urgentPct = round(($criticalAndHigh / $total) * 100, 1);
            $insights[] = "{$urgentPct}% of findings are critical or high severity, requiring immediate attention.";
        }
        
        // Insight about feature areas
        $areasWithFindings = count(array_filter($featureAreas, fn($area) => $area['findings_count'] > 0));
        $totalAreas = count($featureAreas);
        if ($areasWithFindings > 0) {
            $insights[] = "{$areasWithFindings} out of {$totalAreas} feature areas have findings that need to be addressed.";
        }
        
        return $insights;
    }

    /**
     * Generate executive-level recommendations.
     */
    private function generateExecutiveRecommendations(array $summary, array $criticalAreas): array
    {
        $recommendations = [];
        
        if ($summary['by_severity']['critical'] > 0) {
            $recommendations[] = [
                'priority' => 'Immediate',
                'action' => 'Address Critical Issues',
                'description' => "Allocate resources to fix {$summary['by_severity']['critical']} critical severity findings immediately to prevent production issues.",
            ];
        }
        
        if ($summary['by_severity']['high'] > 0) {
            $recommendations[] = [
                'priority' => 'High',
                'action' => 'Plan Sprint for High-Priority Items',
                'description' => "Schedule {$summary['by_severity']['high']} high-priority findings for the current or next sprint.",
            ];
        }
        
        if (count($criticalAreas) > 0) {
            $topArea = $criticalAreas[0]['name'];
            $recommendations[] = [
                'priority' => 'High',
                'action' => "Focus on {$topArea} Feature Area",
                'description' => "The {$topArea} area has the most findings ({$criticalAreas[0]['findings_count']}). Prioritize rectification efforts here.",
            ];
        }
        
        if ($summary['stubs'] > 0) {
            $recommendations[] = [
                'priority' => 'Medium',
                'action' => 'Replace Stub Implementations',
                'description' => "Replace {$summary['stubs']} stub implementations with real database-backed logic and external API integrations.",
            ];
        }
        
        $recommendations[] = [
            'priority' => 'Medium',
            'action' => 'Establish Testing Infrastructure',
            'description' => 'Set up comprehensive testing (unit, integration, E2E, property-based) to prevent regressions during rectification.',
        ];
        
        return $recommendations;
    }

    /**
     * Assess risks based on findings.
     */
    private function assessRisks(array $summary, array $featureAreas): array
    {
        $risks = [];
        
        // Risk from critical findings
        if ($summary['by_severity']['critical'] > 0) {
            $risks[] = [
                'level' => 'High',
                'category' => 'Production Stability',
                'description' => "Critical severity findings pose immediate risk to production stability and user experience.",
                'mitigation' => 'Address critical findings before any production deployment.',
            ];
        }
        
        // Risk from stub implementations
        if ($summary['stubs'] > 0) {
            $risks[] = [
                'level' => 'High',
                'category' => 'Data Integrity',
                'description' => "Stub implementations returning hardcoded data may lead to incorrect business decisions and data inconsistencies.",
                'mitigation' => 'Replace stubs with real implementations that persist to database and call external APIs.',
            ];
        }
        
        // Risk from missing features
        if ($summary['missing'] > 0) {
            $risks[] = [
                'level' => 'Medium',
                'category' => 'Feature Completeness',
                'description' => "Missing features may prevent users from completing critical workflows.",
                'mitigation' => 'Prioritize missing features based on user impact and business value.',
            ];
        }
        
        // Risk from incomplete implementations
        if ($summary['incomplete'] > 0) {
            $risks[] = [
                'level' => 'Medium',
                'category' => 'Error Handling',
                'description' => "Incomplete implementations may lack proper error handling, leading to poor user experience.",
                'mitigation' => 'Add comprehensive error handling, validation, and user feedback mechanisms.',
            ];
        }
        
        // Risk from multiple affected areas
        $areasWithFindings = count(array_filter($featureAreas, fn($area) => $area['findings_count'] > 0));
        if ($areasWithFindings > 5) {
            $risks[] = [
                'level' => 'Medium',
                'category' => 'Technical Debt',
                'description' => "Multiple feature areas have findings, indicating widespread technical debt.",
                'mitigation' => 'Establish a systematic rectification plan with clear phases and milestones.',
            ];
        }
        
        return $risks;
    }
}
