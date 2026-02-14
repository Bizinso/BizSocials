<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Eris\TestTrait as ErisTestTrait;

/**
 * Property Test Trait
 *
 * Extends Eris TestTrait with additional helpers for property-based testing.
 * Use this trait in test classes that need property-based testing capabilities.
 */
trait PropertyTestTrait
{
    use ErisTestTrait;

    /**
     * Get the number of iterations for property tests.
     * Can be overridden in test classes.
     *
     * @return int
     */
    protected function getPropertyTestIterations(): int
    {
        return PropertyGenerators::getDefaultIterations();
    }

    /**
     * Configure Eris to use the specified number of iterations.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set the number of iterations for property tests
        $this->iterations = $this->getPropertyTestIterations();
    }

    /**
     * Helper to run a property test with custom iterations.
     *
     * @param int $iterations Number of iterations
     * @param callable $test Test function
     * @return void
     */
    protected function runPropertyTest(int $iterations, callable $test): void
    {
        $originalIterations = $this->iterations;
        $this->iterations = $iterations;
        
        try {
            $test();
        } finally {
            $this->iterations = $originalIterations;
        }
    }
}
