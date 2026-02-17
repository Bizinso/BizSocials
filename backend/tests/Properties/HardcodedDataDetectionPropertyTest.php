<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Services\Audit\PatternDetector;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Hardcoded Data Detection Property Test
 *
 * Tests that the PatternDetector correctly identifies methods returning hardcoded data.
 *
 * Feature: platform-audit-and-testing
 */
class HardcodedDataDetectionPropertyTest extends TestCase
{
    use PropertyTestTrait;

    private PatternDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PatternDetector();
    }

    /**
     * Property 2: Hardcoded Data Detection
     *
     * For any method that returns hardcoded arrays or objects without database queries
     * or API calls, the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_hardcoded_array_returns(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 5),
            PropertyGenerators::string(5, 20),
            PropertyGenerators::string(10, 50)
        )
            ->then(function ($arraySize, $key, $value) {
                // Generate a method body that returns a hardcoded array
                $arrayElements = [];
                for ($i = 0; $i < $arraySize; $i++) {
                    $arrayElements[] = "'{$key}_{$i}' => '{$value}_{$i}'";
                }
                $arrayString = implode(', ', $arrayElements);
                
                $methodBody = "public function getData() {\n";
                $methodBody .= "    return [{$arrayString}];\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify hardcoded array return: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Variable Assignment
     *
     * For any method that assigns a hardcoded array to a variable and returns it,
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_hardcoded_array_via_variable(): void
    {
        $this->forAll(
            PropertyGenerators::alphanumeric(5, 15),
            PropertyGenerators::string(5, 20),
            PropertyGenerators::integer(1, 100)
        )
            ->then(function ($varName, $key, $value) {
                // Generate a method body that assigns hardcoded data to a variable
                $methodBody = "public function getData() {\n";
                $methodBody .= "    \${$varName} = ['{$key}' => '{$value}'];\n";
                $methodBody .= "    return \${$varName};\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify hardcoded array via variable: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Literal Values
     *
     * For any method that returns only a literal value (string, number, boolean),
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_hardcoded_literal_returns(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 30)
        )
            ->then(function ($literalValue) {
                // Generate a simple method that returns a literal string
                $methodBody = "public function getMessage() {\n";
                $methodBody .= "    return '{$literalValue}';\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify hardcoded literal return: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Mock Method Calls
     *
     * For any method that calls getMock, getFake, or getDummy methods,
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_mock_method_calls(): void
    {
        $this->forAll(
            PropertyGenerators::alphanumeric(5, 15)
        )
            ->then(function ($entityName) {
                $mockMethods = ['getMock', 'getFake', 'getDummy'];
                $mockMethod = $mockMethods[array_rand($mockMethods)];
                
                // Generate a method that calls a mock method
                $methodBody = "public function get{$entityName}() {\n";
                $methodBody .= "    return \$this->{$mockMethod}{$entityName}();\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify mock method call: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Database Operations Exclusion
     *
     * For any method that returns an array but includes database operations,
     * the PatternDetector should NOT identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_does_not_detect_database_query_results_as_hardcoded(): void
    {
        $this->forAll(
            PropertyGenerators::alphanumeric(5, 15),
            PropertyGenerators::alphanumeric(5, 15)
        )
            ->then(function ($modelName, $column) {
                $dbMethods = ['get()', 'all()', 'first()', 'pluck(\'' . $column . '\')'];
                $dbMethod = $dbMethods[array_rand($dbMethods)];
                
                // Generate a method that queries the database
                $methodBody = "public function get{$modelName}() {\n";
                $methodBody .= "    return {$modelName}::where('active', true)->{$dbMethod};\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertFalse(
                    $result,
                    "Expected PatternDetector to NOT identify database query as hardcoded: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - API Call Exclusion
     *
     * For any method that returns data from an external API call,
     * the PatternDetector should NOT identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_does_not_detect_api_call_results_as_hardcoded(): void
    {
        $this->forAll(
            PropertyGenerators::alphanumeric(5, 15),
            PropertyGenerators::alphanumeric(5, 15)
        )
            ->then(function ($endpoint, $domain) {
                $httpMethods = ['get', 'post', 'put', 'patch'];
                $httpMethod = $httpMethods[array_rand($httpMethods)];
                $url = "https://{$domain}.com/api/endpoint";
                
                // Generate a method that makes an API call
                $methodBody = "public function fetch{$endpoint}() {\n";
                $methodBody .= "    \$response = Http::{$httpMethod}('{$url}');\n";
                $methodBody .= "    return \$response->json();\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertFalse(
                    $result,
                    "Expected PatternDetector to NOT identify API call as hardcoded: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Complex Methods
     *
     * For any method with multiple lines of logic that returns a hardcoded array,
     * the PatternDetector should still identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_hardcoded_data_in_complex_methods(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 20),
            PropertyGenerators::integer(1, 100),
            PropertyGenerators::boolean()
        )
            ->then(function ($key, $value, $condition) {
                // Generate a method with some logic but still returns hardcoded data
                $methodBody = "public function getData() {\n";
                $methodBody .= "    \$condition = " . ($condition ? 'true' : 'false') . ";\n";
                $methodBody .= "    if (\$condition) {\n";
                $methodBody .= "        \$data = ['{$key}' => {$value}];\n";
                $methodBody .= "    } else {\n";
                $methodBody .= "        \$data = ['{$key}' => " . ($value + 1) . "];\n";
                $methodBody .= "    }\n";
                $methodBody .= "    return \$data;\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify hardcoded data in complex method: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Empty Arrays
     *
     * For any method that returns an empty array without database or API operations,
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_empty_array_returns(): void
    {
        $this->forAll(
            PropertyGenerators::alphanumeric(5, 15)
        )
            ->then(function ($methodName) {
                // Generate a method that returns an empty array
                $methodBody = "public function get{$methodName}() {\n";
                $methodBody .= "    return [];\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify empty array return as hardcoded: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Nested Arrays
     *
     * For any method that returns nested hardcoded arrays,
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_nested_hardcoded_arrays(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 15),
            PropertyGenerators::string(5, 15),
            PropertyGenerators::integer(1, 50)
        )
            ->then(function ($key1, $key2, $value) {
                // Generate a method that returns nested arrays
                $methodBody = "public function getData() {\n";
                $methodBody .= "    return [\n";
                $methodBody .= "        '{$key1}' => [\n";
                $methodBody .= "            '{$key2}' => {$value}\n";
                $methodBody .= "        ]\n";
                $methodBody .= "    ];\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify nested hardcoded arrays: {$methodBody}"
                );
            });
    }

    /**
     * Property 2: Hardcoded Data Detection - Boolean Returns
     *
     * For any simple method that returns only a boolean literal,
     * the PatternDetector should identify it as returning hardcoded data.
     *
     * Feature: platform-audit-and-testing, Property 2: Hardcoded Data Detection
     * Validates: Requirements 1.4, 2.9
     */
    public function test_detects_simple_boolean_returns(): void
    {
        $this->forAll(
            PropertyGenerators::boolean()
        )
            ->then(function ($boolValue) {
                // Generate a simple method that returns a boolean
                $boolString = $boolValue ? 'true' : 'false';
                $methodBody = "public function isActive() {\n";
                $methodBody .= "    return {$boolString};\n";
                $methodBody .= "}\n";

                $result = $this->detector->isHardcodedData($methodBody);

                $this->assertTrue(
                    $result,
                    "Expected PatternDetector to identify simple boolean return as hardcoded: {$methodBody}"
                );
            });
    }
}
