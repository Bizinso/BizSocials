<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Services\BaseService;

class PatternDetector extends BaseService
{
    /**
     * Detect if a method returns hardcoded data.
     */
    public function isHardcodedData(string $methodBody): bool
    {
        // Remove comments to avoid false positives
        $cleanBody = $this->removeComments($methodBody);

        // Pattern 1: Direct return of arrays
        if (preg_match('/return\s*\[/', $cleanBody)) {
            // Check if it's not from a database query or API call
            if (! $this->hasDatabaseOperations($cleanBody) && ! $this->hasExternalApiCalls($cleanBody)) {
                return true;
            }
        }

        // Pattern 2: Return of hardcoded objects/arrays assigned to variables
        if (preg_match('/\$\w+\s*=\s*\[.*?\];.*?return\s+\$\w+/s', $cleanBody)) {
            if (! $this->hasDatabaseOperations($cleanBody) && ! $this->hasExternalApiCalls($cleanBody)) {
                return true;
            }
        }

        // Pattern 3: Return of literal values
        if (preg_match('/return\s+[\'"].*?[\'"];/', $cleanBody) ||
            preg_match('/return\s+\d+;/', $cleanBody) ||
            preg_match('/return\s+(true|false|null);/', $cleanBody)) {
            // Only flag if the entire method is just returning a literal
            $lines = array_filter(explode("\n", trim($cleanBody)), fn ($line) => ! empty(trim($line)));
            if (count($lines) <= 3) { // Opening brace, return, closing brace
                return true;
            }
        }

        // Pattern 4: Mock/fake method calls
        if (preg_match('/->getMock|->getFake|->getDummy/i', $cleanBody)) {
            return true;
        }

        return false;
    }

    /**
     * Detect if database queries are present.
     */
    public function hasDatabaseOperations(string $methodBody): bool
    {
        $patterns = [
            '/\bDB::/',
            '/->save\(\)/',
            '/->create\(/',
            '/->update\(/',
            '/->delete\(/',
            '/->find\(/',
            '/->findOrFail\(/',
            '/->where\(/',
            '/->get\(\)/',
            '/->first\(\)/',
            '/->all\(\)/',
            '/::query\(\)/',
            '/->insert\(/',
            '/->select\(/',
            '/->join\(/',
            '/->orderBy\(/',
            '/->groupBy\(/',
            '/->pluck\(/',
            '/->count\(\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $methodBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if external API calls are made.
     */
    public function hasExternalApiCalls(string $methodBody): bool
    {
        $patterns = [
            '/\bHttp::/',
            '/\bGuzzle\b/',
            '/->get\([\'"]http/',
            '/->post\([\'"]http/',
            '/->put\([\'"]http/',
            '/->patch\([\'"]http/',
            '/->delete\([\'"]http/',
            '/curl_init\(/',
            '/curl_exec\(/',
            '/file_get_contents\([\'"]http/',
            '/->request\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $methodBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if proper error handling exists.
     */
    public function hasProperErrorHandling(string $methodBody): bool
    {
        // Check for try-catch blocks
        $hasTryCatch = preg_match('/try\s*\{.*?catch\s*\(/s', $methodBody);

        // Check for empty catch blocks (anti-pattern)
        $hasEmptyCatch = preg_match('/catch\s*\([^)]+\)\s*\{\s*\}/s', $methodBody);

        // Check for error logging
        $hasLogging = preg_match('/Log::|->log\(|logger\(/', $methodBody);

        // Check for exception throwing
        $hasThrow = preg_match('/throw\s+new/', $methodBody);

        // Proper error handling means: has try-catch AND (has logging OR throws) AND not empty catch
        if ($hasTryCatch && ! $hasEmptyCatch && ($hasLogging || $hasThrow)) {
            return true;
        }

        // Or at least throws exceptions
        if ($hasThrow) {
            return true;
        }

        return false;
    }

    /**
     * Detect if validation is implemented.
     */
    public function hasValidation(string $methodBody): bool
    {
        $patterns = [
            '/\bvalidate\(/',
            '/\bvalidator\(/',
            '/->rules\(\)/',
            '/->validated\(\)/',
            '/\bValidator::make/',
            '/\bValidationException/',
            '/if\s*\(\s*!\s*\$/',  // Basic if checks
            '/empty\(/',
            '/isset\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $methodBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect stub/mock/TODO comments.
     */
    public function detectStubComments(string $code): array
    {
        $findings = [];
        $lines = explode("\n", $code);

        foreach ($lines as $lineNumber => $line) {
            // Check for TODO comments
            if (preg_match('/\/\/\s*TODO/i', $line) || preg_match('/\/\*.*?TODO.*?\*\//i', $line)) {
                $findings[] = [
                    'type' => 'TODO',
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                ];
            }

            // Check for STUB comments
            if (preg_match('/\/\/\s*STUB/i', $line) || preg_match('/\/\*.*?STUB.*?\*\//i', $line)) {
                $findings[] = [
                    'type' => 'STUB',
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                ];
            }

            // Check for MOCK comments
            if (preg_match('/\/\/\s*MOCK/i', $line) || preg_match('/\/\*.*?MOCK.*?\*\//i', $line)) {
                $findings[] = [
                    'type' => 'MOCK',
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                ];
            }

            // Check for FAKE comments
            if (preg_match('/\/\/\s*FAKE/i', $line) || preg_match('/\/\*.*?FAKE.*?\*\//i', $line)) {
                $findings[] = [
                    'type' => 'FAKE',
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                ];
            }

            // Check for FIXME comments
            if (preg_match('/\/\/\s*FIXME/i', $line) || preg_match('/\/\*.*?FIXME.*?\*\//i', $line)) {
                $findings[] = [
                    'type' => 'FIXME',
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                ];
            }
        }

        return $findings;
    }

    /**
     * Analyze a complete file for stub patterns.
     */
    public function analyzeFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found',
            ];
        }

        $content = file_get_contents($filePath);

        return [
            'success' => true,
            'path' => $filePath,
            'has_hardcoded_data' => $this->isHardcodedData($content),
            'has_database_operations' => $this->hasDatabaseOperations($content),
            'has_api_calls' => $this->hasExternalApiCalls($content),
            'has_error_handling' => $this->hasProperErrorHandling($content),
            'has_validation' => $this->hasValidation($content),
            'stub_comments' => $this->detectStubComments($content),
            'stub_score' => $this->calculateStubScore($content),
        ];
    }

    /**
     * Calculate a stub score (0-100, higher means more likely to be a stub).
     */
    private function calculateStubScore(string $content): int
    {
        $score = 0;

        // High indicators of stub implementation
        if ($this->isHardcodedData($content)) {
            $score += 40;
        }

        if (! $this->hasDatabaseOperations($content)) {
            $score += 20;
        }

        if (! $this->hasExternalApiCalls($content)) {
            $score += 10;
        }

        if (! $this->hasProperErrorHandling($content)) {
            $score += 15;
        }

        if (! $this->hasValidation($content)) {
            $score += 10;
        }

        $stubComments = $this->detectStubComments($content);
        if (count($stubComments) > 0) {
            $score += min(count($stubComments) * 5, 20);
        }

        // Check for mock method names
        if (preg_match('/function\s+(getMock|getFake|getDummy|mockData)/i', $content)) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Remove comments from code to avoid false positives.
     */
    private function removeComments(string $code): string
    {
        // Remove single-line comments
        $code = preg_replace('/\/\/.*$/m', '', $code);

        // Remove multi-line comments
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);

        return $code;
    }

    /**
     * Detect hardcoded arrays in return statements.
     */
    public function detectHardcodedArrays(string $code): array
    {
        $findings = [];
        $lines = explode("\n", $code);

        foreach ($lines as $lineNumber => $line) {
            // Look for return statements with arrays
            if (preg_match('/return\s*\[/', $line)) {
                // Extract context (a few lines around it)
                $context = $this->extractContext($lines, $lineNumber, 3);

                // Check if it's not from a query
                if (! preg_match('/->get\(\)|->all\(\)|->pluck\(/', $context)) {
                    $findings[] = [
                        'line' => $lineNumber + 1,
                        'content' => trim($line),
                        'context' => $context,
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * Extract context lines around a specific line.
     */
    private function extractContext(array $lines, int $lineNumber, int $contextSize): string
    {
        $start = max(0, $lineNumber - $contextSize);
        $end = min(count($lines) - 1, $lineNumber + $contextSize);

        $contextLines = [];
        for ($i = $start; $i <= $end; $i++) {
            $contextLines[] = $lines[$i];
        }

        return implode("\n", $contextLines);
    }
}
