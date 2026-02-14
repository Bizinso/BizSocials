<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Audit\CodeAnalyzer;
use App\Services\Audit\IntegrationValidator;
use App\Services\Audit\PatternDetector;
use App\Services\Audit\ReportGenerator;
use Illuminate\Console\Command;

class AuditPlatform extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:platform
                            {--area= : Specific feature area to audit (e.g., Social, Content, Inbox)}
                            {--full : Run full platform audit}
                            {--export= : Export format (json, html, both)}';

    /**
     * The console command description.
     */
    protected $description = 'Audit the platform for stub implementations and incomplete features';

    /**
     * Execute the console command.
     */
    public function handle(
        CodeAnalyzer $codeAnalyzer,
        PatternDetector $patternDetector,
        IntegrationValidator $integrationValidator,
        ReportGenerator $reportGenerator
    ): int {
        $this->info('🔍 Starting Platform Audit...');
        $this->newLine();

        $area = $this->option('area');
        $full = $this->option('full');
        $export = $this->option('export') ?? 'both';

        if (! $area && ! $full) {
            $this->error('Please specify either --area=<feature> or --full');

            return self::FAILURE;
        }

        $areas = $full ? $this->getAllFeatureAreas() : [$area];

        foreach ($areas as $featureArea) {
            $this->auditFeatureArea($featureArea, $codeAnalyzer, $patternDetector, $integrationValidator, $reportGenerator, $export);
        }

        $this->newLine();
        $this->info('✅ Audit completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Audit a specific feature area.
     */
    private function auditFeatureArea(
        string $featureArea,
        CodeAnalyzer $codeAnalyzer,
        PatternDetector $patternDetector,
        IntegrationValidator $integrationValidator,
        ReportGenerator $reportGenerator,
        string $export
    ): void {
        $this->info("📋 Auditing: {$featureArea}");

        $findings = [];

        // Get all services in the feature area
        $services = $codeAnalyzer->getFeatureServices($featureArea);

        if (empty($services)) {
            $this->warn("  No services found in {$featureArea}");

            return;
        }

        $this->line("  Found ".count($services).' service(s)');

        $progressBar = $this->output->createProgressBar(count($services));
        $progressBar->start();

        foreach ($services as $servicePath) {
            // Analyze service
            $analysis = $codeAnalyzer->analyzeService($servicePath);

            if (! $analysis['success']) {
                $progressBar->advance();

                continue;
            }

            // Detect patterns
            $patternAnalysis = $patternDetector->analyzeFile($servicePath);

            if ($patternAnalysis['success']) {
                $stubScore = $patternAnalysis['stub_score'];

                // Create finding if stub score is high
                if ($stubScore >= 40) {
                    $severity = $this->determineSeverity($stubScore);
                    $type = $this->determineType($patternAnalysis);

                    $findings[] = [
                        'type' => $type,
                        'severity' => $severity,
                        'location' => $servicePath,
                        'description' => $this->generateDescription($patternAnalysis),
                        'evidence' => $this->generateEvidence($patternAnalysis),
                        'recommendation' => $this->generateRecommendation($type, $patternAnalysis),
                    ];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Validate integrations if this is a social feature area
        if (strtolower($featureArea) === 'social') {
            $this->line('  Validating social media integrations...');
            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'];

            foreach ($platforms as $platform) {
                $oauthResult = $integrationValidator->validateOAuthFlow($platform);
                $apiResult = $integrationValidator->validateApiConnection($platform);

                if (! $oauthResult['oauth_configured'] || ! $apiResult['can_connect']) {
                    $findings[] = [
                        'type' => 'incomplete',
                        'severity' => 'high',
                        'location' => "Integration: {$platform}",
                        'description' => "Integration for {$platform} is incomplete or not configured",
                        'evidence' => json_encode([
                            'oauth' => $oauthResult,
                            'api' => $apiResult,
                        ], JSON_PRETTY_PRINT),
                        'recommendation' => "Complete OAuth configuration and API client implementation for {$platform}",
                    ];
                }
            }
        }

        // Generate report
        $this->line('  Generating report...');

        $recommendations = $reportGenerator->generateRecommendations($findings);
        $report = $reportGenerator->generateReport($featureArea, $findings, $recommendations);

        // Export report
        if (in_array($export, ['json', 'both'])) {
            $jsonFile = $reportGenerator->exportAsJson($report);
            $this->line("  📄 JSON report: storage/app/{$jsonFile}");
        }

        if (in_array($export, ['html', 'both'])) {
            $htmlFile = $reportGenerator->exportAsHtml($report);
            $this->line("  📄 HTML report: storage/app/{$htmlFile}");
        }

        // Display summary
        $summary = $report->summary;
        $this->newLine();
        $this->line("  Summary:");
        $this->line("    Total findings: {$summary['total']}");
        $this->line("    Stubs: {$summary['stubs']}");
        $this->line("    Incomplete: {$summary['incomplete']}");
        $this->line("    Missing: {$summary['missing']}");
        $this->line("    Critical: {$summary['by_severity']['critical']}");
        $this->line("    High: {$summary['by_severity']['high']}");
        $this->line("    Medium: {$summary['by_severity']['medium']}");
        $this->line("    Low: {$summary['by_severity']['low']}");
        $this->newLine();
    }

    /**
     * Get all feature areas.
     */
    private function getAllFeatureAreas(): array
    {
        return [
            'Social',
            'Content',
            'Inbox',
            'Analytics',
            'Billing',
            'WhatsApp',
            'Support',
            'KnowledgeBase',
            'Workspace',
            'User',
            'Auth',
        ];
    }

    /**
     * Determine severity based on stub score.
     */
    private function determineSeverity(int $stubScore): string
    {
        return match (true) {
            $stubScore >= 80 => 'critical',
            $stubScore >= 60 => 'high',
            $stubScore >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Determine type based on pattern analysis.
     */
    private function determineType(array $analysis): string
    {
        if ($analysis['has_hardcoded_data'] && ! $analysis['has_database_operations']) {
            return 'stub';
        }

        if (! $analysis['has_error_handling'] || ! $analysis['has_validation']) {
            return 'incomplete';
        }

        if (count($analysis['stub_comments']) > 0) {
            return 'stub';
        }

        return 'incomplete';
    }

    /**
     * Generate description from pattern analysis.
     */
    private function generateDescription(array $analysis): string
    {
        $issues = [];

        if ($analysis['has_hardcoded_data']) {
            $issues[] = 'returns hardcoded data';
        }

        if (! $analysis['has_database_operations']) {
            $issues[] = 'no database operations';
        }

        if (! $analysis['has_api_calls']) {
            $issues[] = 'no external API calls';
        }

        if (! $analysis['has_error_handling']) {
            $issues[] = 'missing error handling';
        }

        if (! $analysis['has_validation']) {
            $issues[] = 'missing validation';
        }

        if (count($analysis['stub_comments']) > 0) {
            $issues[] = count($analysis['stub_comments']).' stub comment(s)';
        }

        return 'Service '.implode(', ', $issues);
    }

    /**
     * Generate evidence from pattern analysis.
     */
    private function generateEvidence(array $analysis): string
    {
        $evidence = [];

        $evidence[] = "Stub Score: {$analysis['stub_score']}/100";

        if (count($analysis['stub_comments']) > 0) {
            $evidence[] = 'Stub Comments:';
            foreach ($analysis['stub_comments'] as $comment) {
                $evidence[] = "  Line {$comment['line']}: {$comment['content']}";
            }
        }

        return implode("\n", $evidence);
    }

    /**
     * Generate recommendation based on type and analysis.
     */
    private function generateRecommendation(string $type, array $analysis): string
    {
        $recommendations = [];

        if ($type === 'stub') {
            $recommendations[] = 'Replace stub implementation with real database-backed logic';
        }

        if (! $analysis['has_database_operations']) {
            $recommendations[] = 'Add database queries to persist and retrieve data';
        }

        if (! $analysis['has_api_calls']) {
            $recommendations[] = 'Implement real API calls to external services';
        }

        if (! $analysis['has_error_handling']) {
            $recommendations[] = 'Add proper error handling with try-catch blocks and logging';
        }

        if (! $analysis['has_validation']) {
            $recommendations[] = 'Implement input validation';
        }

        return implode('. ', $recommendations);
    }
}
