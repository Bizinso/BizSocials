<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audit;

use App\Services\Audit\CodeAnalyzer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class CodeAnalyzerTest extends TestCase
{
    private CodeAnalyzer $analyzer;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new CodeAnalyzer();
        $this->testFilesPath = base_path('tests/Fixtures/CodeAnalyzer');
        
        // Create test fixtures directory if it doesn't exist
        if (!File::exists($this->testFilesPath)) {
            File::makeDirectory($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test fixtures
        if (File::exists($this->testFilesPath)) {
            File::deleteDirectory($this->testFilesPath);
        }

        parent::tearDown();
    }

    public function test_analyzes_service_successfully(): void
    {
        $servicePath = $this->createTestServiceFile();

        $result = $this->analyzer->analyzeService($servicePath);

        $this->assertTrue($result['success']);
        $this->assertEquals($servicePath, $result['path']);
        $this->assertArrayHasKey('class_name', $result);
        $this->assertArrayHasKey('methods', $result);
        $this->assertArrayHasKey('has_database_operations', $result);
        $this->assertArrayHasKey('has_api_calls', $result);
        $this->assertArrayHasKey('stub_indicators', $result);
    }

    public function test_handles_missing_service_file(): void
    {
        $nonExistentPath = $this->testFilesPath . '/NonExistent.php';

        $result = $this->analyzer->analyzeService($nonExistentPath);

        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
        $this->assertEquals($nonExistentPath, $result['path']);
    }

    public function test_handles_parse_errors_in_service(): void
    {
        // Create a file that will cause an exception during processing
        $invalidPath = $this->testFilesPath . '/Unreadable.php';
        File::put($invalidPath, '<?php class Test {}');
        
        // Make the file unreadable to trigger an error
        chmod($invalidPath, 0000);

        $result = $this->analyzer->analyzeService($invalidPath);

        // The analyzer should catch the error and return success=false
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals($invalidPath, $result['path']);
        
        // Restore permissions for cleanup
        chmod($invalidPath, 0644);
    }

    public function test_analyzes_endpoint_successfully(): void
    {
        $endpointPath = $this->createTestEndpointFile();

        $result = $this->analyzer->analyzeEndpoint($endpointPath);

        $this->assertTrue($result['success']);
        $this->assertEquals($endpointPath, $result['path']);
        $this->assertArrayHasKey('class_name', $result);
        $this->assertArrayHasKey('methods', $result);
        $this->assertArrayHasKey('routes', $result);
        $this->assertArrayHasKey('has_validation', $result);
        $this->assertArrayHasKey('stub_indicators', $result);
    }

    public function test_handles_missing_endpoint_file(): void
    {
        $nonExistentPath = $this->testFilesPath . '/NonExistentController.php';

        $result = $this->analyzer->analyzeEndpoint($nonExistentPath);

        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
        $this->assertEquals($nonExistentPath, $result['path']);
    }

    public function test_handles_parse_errors_in_endpoint(): void
    {
        // Create a file that will cause an exception during processing
        $invalidPath = $this->testFilesPath . '/UnreadableController.php';
        File::put($invalidPath, '<?php class TestController {}');
        
        // Make the file unreadable to trigger an error
        chmod($invalidPath, 0000);

        $result = $this->analyzer->analyzeEndpoint($invalidPath);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals($invalidPath, $result['path']);
        
        // Restore permissions for cleanup
        chmod($invalidPath, 0644);
    }

    public function test_analyzes_component_successfully(): void
    {
        $componentPath = $this->createTestVueComponent();

        $result = $this->analyzer->analyzeComponent($componentPath);

        $this->assertTrue($result['success']);
        $this->assertEquals($componentPath, $result['path']);
        $this->assertArrayHasKey('component_name', $result);
        $this->assertArrayHasKey('has_hardcoded_data', $result);
        $this->assertArrayHasKey('api_calls', $result);
        $this->assertArrayHasKey('stub_indicators', $result);
    }

    public function test_handles_missing_component_file(): void
    {
        $nonExistentPath = $this->testFilesPath . '/NonExistent.vue';

        $result = $this->analyzer->analyzeComponent($nonExistentPath);

        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
        $this->assertEquals($nonExistentPath, $result['path']);
    }

    public function test_discovers_services_in_feature_area(): void
    {
        // Create a test feature area with multiple service files
        $featureArea = 'TestFeature';
        $featurePath = app_path('Services/' . $featureArea);
        
        File::makeDirectory($featurePath, 0755, true);
        
        // Create test service files
        File::put($featurePath . '/TestService1.php', $this->getTestServiceContent('TestService1'));
        File::put($featurePath . '/TestService2.php', $this->getTestServiceContent('TestService2'));
        
        // Create a subdirectory with another service
        File::makeDirectory($featurePath . '/SubFeature', 0755, true);
        File::put($featurePath . '/SubFeature/TestService3.php', $this->getTestServiceContent('TestService3'));

        $services = $this->analyzer->getFeatureServices($featureArea);

        $this->assertCount(3, $services);
        $this->assertContains($featurePath . '/TestService1.php', $services);
        $this->assertContains($featurePath . '/TestService2.php', $services);
        $this->assertContains($featurePath . '/SubFeature/TestService3.php', $services);

        // Clean up
        File::deleteDirectory($featurePath);
    }

    public function test_returns_empty_array_for_non_existent_feature_area(): void
    {
        $services = $this->analyzer->getFeatureServices('NonExistentFeature');

        $this->assertIsArray($services);
        $this->assertEmpty($services);
    }

    public function test_discovers_only_php_files_in_feature_area(): void
    {
        $featureArea = 'TestFeature';
        $featurePath = app_path('Services/' . $featureArea);
        
        File::makeDirectory($featurePath, 0755, true);
        
        // Create PHP and non-PHP files
        File::put($featurePath . '/TestService.php', $this->getTestServiceContent('TestService'));
        File::put($featurePath . '/README.md', '# Test');
        File::put($featurePath . '/config.json', '{}');

        $services = $this->analyzer->getFeatureServices($featureArea);

        $this->assertCount(1, $services);
        $this->assertContains($featurePath . '/TestService.php', $services);

        // Clean up
        File::deleteDirectory($featurePath);
    }

    public function test_detects_database_operations_in_service(): void
    {
        $content = <<<'PHP'
<?php
namespace App\Services\Test;

class TestService
{
    public function getData()
    {
        return User::where('active', true)->get();
    }
}
PHP;

        $path = $this->testFilesPath . '/ServiceWithDb.php';
        File::put($path, $content);

        $result = $this->analyzer->analyzeService($path);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_database_operations']);
    }

    public function test_detects_api_calls_in_service(): void
    {
        $content = <<<'PHP'
<?php
namespace App\Services\Test;

use Illuminate\Support\Facades\Http;

class TestService
{
    public function fetchData()
    {
        return Http::get('https://api.example.com/data');
    }
}
PHP;

        $path = $this->testFilesPath . '/ServiceWithApi.php';
        File::put($path, $content);

        $result = $this->analyzer->analyzeService($path);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_api_calls']);
    }

    public function test_detects_stub_indicators(): void
    {
        $content = <<<'PHP'
<?php
namespace App\Services\Test;

class TestService
{
    public function getData()
    {
        // TODO: Implement real data fetching
        return ['mock' => 'data'];
    }
    
    public function process()
    {
        // STUB: Replace with actual implementation
        return true;
    }
}
PHP;

        $path = $this->testFilesPath . '/ServiceWithStubs.php';
        File::put($path, $content);

        $result = $this->analyzer->analyzeService($path);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['stub_indicators']);
        $this->assertCount(2, $result['stub_indicators']);
        
        $types = array_column($result['stub_indicators'], 'type');
        $this->assertContains('TODO', $types);
        $this->assertContains('STUB', $types);
    }

    public function test_extracts_methods_from_service(): void
    {
        $servicePath = $this->createTestServiceFile();

        $result = $this->analyzer->analyzeService($servicePath);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['methods']);
        $this->assertNotEmpty($result['methods']);
        
        foreach ($result['methods'] as $method) {
            $this->assertArrayHasKey('name', $method);
            $this->assertArrayHasKey('visibility', $method);
            $this->assertArrayHasKey('line', $method);
        }
    }

    public function test_detects_validation_in_endpoint(): void
    {
        $content = <<<'PHP'
<?php
namespace App\Http\Controllers;

class TestController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);
        
        return response()->json($validated);
    }
}
PHP;

        $path = $this->testFilesPath . '/ControllerWithValidation.php';
        File::put($path, $content);

        $result = $this->analyzer->analyzeEndpoint($path);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_validation']);
    }

    private function createTestServiceFile(): string
    {
        $content = $this->getTestServiceContent('TestService');
        $path = $this->testFilesPath . '/TestService.php';
        File::put($path, $content);
        
        return $path;
    }

    private function createTestEndpointFile(): string
    {
        $content = <<<'PHP'
<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class TestController
{
    public function index()
    {
        return response()->json(['data' => []]);
    }
    
    public function store(Request $request)
    {
        return response()->json(['success' => true]);
    }
}
PHP;

        $path = $this->testFilesPath . '/TestController.php';
        File::put($path, $content);
        
        return $path;
    }

    private function createTestVueComponent(): string
    {
        $content = <<<'VUE'
<template>
  <div>
    <h1>Test Component</h1>
  </div>
</template>

<script>
export default {
  name: 'TestComponent',
  data() {
    return {
      items: []
    }
  }
}
</script>
VUE;

        $path = $this->testFilesPath . '/TestComponent.vue';
        File::put($path, $content);
        
        return $path;
    }

    private function createInvalidPhpFile(): string
    {
        $content = <<<'PHP'
<?php
// This file has invalid PHP syntax
class InvalidClass {
    public function test(
        // Missing closing parenthesis and brace
PHP;

        $path = $this->testFilesPath . '/Invalid.php';
        File::put($path, $content);
        
        return $path;
    }

    private function getTestServiceContent(string $className): string
    {
        return <<<PHP
<?php
namespace App\Services\Test;

class {$className}
{
    public function getData()
    {
        return [];
    }
    
    private function processData(\$data)
    {
        return \$data;
    }
}
PHP;
    }
}

