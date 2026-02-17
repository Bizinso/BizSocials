<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Services\BaseService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CodeAnalyzer extends BaseService
{
    /**
     * Analyze a service class for stub patterns.
     */
    public function analyzeService(string $servicePath): array
    {
        try {
            if (! file_exists($servicePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'path' => $servicePath,
                ];
            }

            $content = file_get_contents($servicePath);
            $tokens = token_get_all($content);

            return [
                'success' => true,
                'path' => $servicePath,
                'class_name' => $this->extractClassName($tokens),
                'methods' => $this->extractMethods($tokens, $content),
                'has_database_operations' => $this->hasDatabaseOperations($content),
                'has_api_calls' => $this->hasApiCalls($content),
                'stub_indicators' => $this->findStubIndicators($content),
            ];
        } catch (\Throwable $e) {
            $this->log('Error analyzing service: '.$e->getMessage(), [
                'path' => $servicePath,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'path' => $servicePath,
            ];
        }
    }

    /**
     * Analyze an API endpoint for mock data returns.
     */
    public function analyzeEndpoint(string $routePath): array
    {
        try {
            if (! file_exists($routePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'path' => $routePath,
                ];
            }

            $content = file_get_contents($routePath);
            $tokens = token_get_all($content);

            return [
                'success' => true,
                'path' => $routePath,
                'class_name' => $this->extractClassName($tokens),
                'methods' => $this->extractMethods($tokens, $content),
                'routes' => $this->extractRoutes($content),
                'has_validation' => $this->hasValidation($content),
                'stub_indicators' => $this->findStubIndicators($content),
            ];
        } catch (\Throwable $e) {
            $this->log('Error analyzing endpoint: '.$e->getMessage(), [
                'path' => $routePath,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'path' => $routePath,
            ];
        }
    }

    /**
     * Analyze a Vue component for hardcoded data.
     */
    public function analyzeComponent(string $componentPath): array
    {
        try {
            if (! file_exists($componentPath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'path' => $componentPath,
                ];
            }

            $content = file_get_contents($componentPath);

            return [
                'success' => true,
                'path' => $componentPath,
                'component_name' => $this->extractVueComponentName($componentPath),
                'has_hardcoded_data' => $this->hasHardcodedData($content),
                'api_calls' => $this->extractApiCalls($content),
                'stub_indicators' => $this->findStubIndicators($content),
            ];
        } catch (\Throwable $e) {
            $this->log('Error analyzing component: '.$e->getMessage(), [
                'path' => $componentPath,
                'error' => $e->getMessage(),
            ], 'error');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'path' => $componentPath,
            ];
        }
    }

    /**
     * Get all services in a feature area.
     */
    public function getFeatureServices(string $featureArea): array
    {
        $servicesPath = app_path('Services/'.$featureArea);

        if (! is_dir($servicesPath)) {
            return [];
        }

        $services = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($servicesPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $services[] = $file->getPathname();
            }
        }

        return $services;
    }

    /**
     * Extract class name from tokens.
     */
    private function extractClassName(array $tokens): ?string
    {
        $className = null;
        $namespace = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = $this->extractNamespace($tokens, $i);
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        break;
                    }
                }
                break;
            }
        }

        return $className ? $namespace.'\\'.$className : null;
    }

    /**
     * Extract namespace from tokens.
     */
    private function extractNamespace(array $tokens, int $startIndex): string
    {
        $namespace = '';

        for ($i = $startIndex + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
                $namespace .= $tokens[$i][1];
            } elseif ($tokens[$i] === ';') {
                break;
            }
        }

        return $namespace;
    }

    /**
     * Extract methods from tokens.
     */
    private function extractMethods(array $tokens, string $content): array
    {
        $methods = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                $method = $this->extractMethodInfo($tokens, $i, $content);
                if ($method) {
                    $methods[] = $method;
                }
            }
        }

        return $methods;
    }

    /**
     * Extract method information.
     */
    private function extractMethodInfo(array $tokens, int $startIndex, string $content): ?array
    {
        $methodName = null;
        $visibility = 'public';

        // Look backwards for visibility modifier
        for ($i = $startIndex - 1; $i >= 0; $i--) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                $visibility = $tokens[$i][1];
                break;
            } elseif (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                continue;
            } else {
                break;
            }
        }

        // Look forward for method name
        for ($i = $startIndex + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $methodName = $tokens[$i][1];
                break;
            }
        }

        if (! $methodName) {
            return null;
        }

        return [
            'name' => $methodName,
            'visibility' => $visibility,
            'line' => $tokens[$startIndex][2] ?? null,
        ];
    }

    /**
     * Check if content has database operations.
     */
    private function hasDatabaseOperations(string $content): bool
    {
        $patterns = [
            '/\bDB::/',
            '/\bEloquent\b/',
            '/->save\(\)/',
            '/->create\(/',
            '/->update\(/',
            '/->delete\(/',
            '/->find\(/',
            '/->where\(/',
            '/->get\(\)/',
            '/->first\(\)/',
            '/::query\(\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content has API calls.
     */
    private function hasApiCalls(string $content): bool
    {
        $patterns = [
            '/\bHttp::/',
            '/\bGuzzle\b/',
            '/->get\([\'"]http/',
            '/->post\([\'"]http/',
            '/curl_/',
            '/file_get_contents\([\'"]http/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content has validation.
     */
    private function hasValidation(string $content): bool
    {
        $patterns = [
            '/\bvalidate\(/',
            '/\bvalidator\(/',
            '/\bFormRequest\b/',
            '/->rules\(\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find stub indicators in content.
     */
    private function findStubIndicators(string $content): array
    {
        $indicators = [];
        $patterns = [
            'TODO' => '/\/\/\s*TODO/i',
            'STUB' => '/\/\/\s*STUB/i',
            'MOCK' => '/\/\/\s*MOCK/i',
            'FAKE' => '/\/\/\s*FAKE/i',
            'FIXME' => '/\/\/\s*FIXME/i',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $indicators[] = [
                        'type' => $type,
                        'line' => $line,
                        'text' => trim($match[0]),
                    ];
                }
            }
        }

        return $indicators;
    }

    /**
     * Extract Vue component name from path.
     */
    private function extractVueComponentName(string $path): string
    {
        return basename($path, '.vue');
    }

    /**
     * Check if Vue component has hardcoded data.
     */
    private function hasHardcodedData(string $content): bool
    {
        // Look for data() or setup() with hardcoded arrays/objects
        $patterns = [
            '/data\s*\(\s*\)\s*\{[^}]*return\s*\{/',
            '/const\s+\w+\s*=\s*\[/',
            '/const\s+\w+\s*=\s*\{/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract API calls from Vue component.
     */
    private function extractApiCalls(string $content): array
    {
        $apiCalls = [];

        // Look for axios, fetch, or HTTP calls
        $patterns = [
            '/axios\.(get|post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)/',
            '/fetch\s*\(\s*[\'"]([^\'"]+)/',
            '/\$http\.(get|post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $apiCalls[] = [
                        'method' => $match[1] ?? 'unknown',
                        'url' => $match[2] ?? $match[1],
                    ];
                }
            }
        }

        return $apiCalls;
    }

    /**
     * Extract routes from content.
     */
    private function extractRoutes(string $content): array
    {
        $routes = [];

        // Look for Route:: definitions
        if (preg_match_all('/Route::(get|post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $routes[] = [
                    'method' => strtoupper($match[1]),
                    'path' => $match[2],
                ];
            }
        }

        return $routes;
    }
}
