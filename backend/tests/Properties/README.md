# Property-Based Testing with Eris

This directory contains property-based tests for the BizSocials platform using the Eris library.

## What is Property-Based Testing?

Property-based testing is a testing approach where you define universal properties that should hold true for all valid inputs, rather than testing specific examples. The testing framework then generates hundreds of random inputs to verify these properties.

## Setup

The property-based testing framework is already configured and ready to use:

- **Library**: Eris (PHP port of QuickCheck)
- **Default Iterations**: 100 per property test
- **Test Suite**: `Properties` (configured in `phpunit.xml`)

## Running Property Tests

```bash
# Run all property tests
php artisan test --testsuite=Properties

# Run a specific property test file
php artisan test --filter=ExamplePropertyTest

# Run with a specific seed for reproducibility
ERIS_SEED=1234567890 php artisan test --testsuite=Properties
```

## Writing Property Tests

### 1. Create a Test Class

```php
<?php

namespace Tests\Properties;

use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

class MyPropertyTest extends TestCase
{
    use PropertyTestTrait;

    // Your tests here
}
```

### 2. Write a Property Test

```php
/**
 * Property: Description of the property being tested
 *
 * Feature: platform-audit-and-testing, Property X: Property Name
 * Validates: Requirements X.Y
 */
public function test_my_property(): void
{
    $this->forAll(
        PropertyGenerators::string(1, 100),
        PropertyGenerators::integer(0, 1000)
    )
        ->then(function ($string, $number) {
            // Your assertions here
            $this->assertTrue(/* some condition */);
        });
}
```

### 3. Use Generators

The `PropertyGenerators` helper class provides many pre-built generators:

```php
// Basic types
PropertyGenerators::string(1, 255)
PropertyGenerators::integer(0, 1000)
PropertyGenerators::boolean()
PropertyGenerators::positiveInteger(100)

// Domain-specific
PropertyGenerators::email()
PropertyGenerators::url()
PropertyGenerators::phoneNumber()
PropertyGenerators::hashtag()
PropertyGenerators::timezone()
PropertyGenerators::languageCode()

// Dates
PropertyGenerators::date('2020-01-01', '2025-12-31')
PropertyGenerators::futureDate(365)
PropertyGenerators::pastDate(365)

// Complex types
PropertyGenerators::array(PropertyGenerators::string(), 0, 10)
PropertyGenerators::nullable(PropertyGenerators::string(), 0.3)
PropertyGenerators::jsonObject()

// Models
PropertyGenerators::user()
PropertyGenerators::post()
PropertyGenerators::tenant()
PropertyGenerators::workspace()
```

## Property Test Tags

Each property test MUST include a comment tag linking it to the design document:

```php
/**
 * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
 * Validates: Requirements 3.1, 16.3
 */
```

This ensures traceability between design properties and test implementations.

## Filtering Inputs

Use `when()` to filter generated inputs (use sparingly as it can slow down tests):

```php
$this->forAll(
    PropertyGenerators::integer(0, 100)
)
    ->when(function ($number) {
        return $number % 2 === 0; // Only test even numbers
    })
    ->then(function ($number) {
        $this->assertEquals(0, $number % 2);
    });
```

## Shrinking

When a property test fails, Eris automatically tries to find the smallest input that still fails. This makes debugging much easier.

Example output:
```
FAILURES!
Tests: 1, Assertions: 826, Failures: 1.

The smallest failing input was: 42
```

## Best Practices

1. **Keep properties simple**: Each test should verify one universal property
2. **Use descriptive names**: Test names should clearly describe the property being tested
3. **Tag all tests**: Always include the Feature and Property tags
4. **Avoid mocking**: Property tests should test real behavior, not mocks
5. **Use appropriate generators**: Choose generators that match your input domain
6. **Set iteration count**: Default is 100, but you can adjust for specific tests
7. **Document properties**: Explain what property is being tested and why

## Iteration Count

To change the number of iterations for a specific test:

```php
protected function getPropertyTestIterations(): int
{
    return 200; // Override default of 100
}
```

Or use the helper method:

```php
$this->runPropertyTest(200, function() {
    $this->forAll(/* ... */)->then(/* ... */);
});
```

## Examples

See `ExamplePropertyTest.php` for comprehensive examples of:
- Email validation
- String validation
- Integer range validation
- URL validation
- Array size validation
- Hashtag format validation
- Phone number validation
- Nullable values
- Date generation

## Troubleshooting

### "Evaluation ratio is under the threshold"

This error occurs when too many generated inputs are filtered out by `when()` clauses or fail assertions. Solutions:
- Reduce or remove `when()` filters
- Use more appropriate generators
- Check that your assertions are correct

### Tests are slow

Property tests run 100+ iterations by default, so they're slower than unit tests. This is expected. To speed up:
- Reduce iteration count for development
- Run property tests separately from unit tests
- Use CI/CD to run full property test suite

### Non-deterministic failures

If tests fail intermittently:
- Use the ERIS_SEED environment variable to reproduce
- Check for non-deterministic code (time, random, external APIs)
- Ensure generators are deterministic

## Resources

- [Eris Documentation](https://github.com/giorgiosironi/eris)
- [QuickCheck Paper](https://www.cs.tufts.edu/~nr/cs257/archive/john-hughes/quick.pdf)
- [Property-Based Testing Guide](https://hypothesis.works/articles/what-is-property-based-testing/)
