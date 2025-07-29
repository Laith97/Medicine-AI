<?php

/**
 * Test Runner Script for Medicine-AI Application
 *
 * This script provides a comprehensive way to run all unit tests
 * with proper setup and reporting.
 */

require_once __DIR__ . '/../vendor/autoload.php';

class TestRunner
{
    private $testSuites = [
        'Models' => [
            'Tests\Unit\Models\UserTest',
            'Tests\Unit\Models\DoctorTest',
            'Tests\Unit\Models\AppointmentTest',
            'Tests\Unit\Models\ReviewTest',
            'Tests\Unit\Models\DoctorNoteTest',
            'Tests\Unit\Models\SubscriptionTest',
        ],
        'Services' => [
            'Tests\Unit\Services\OpenAIClientTest',
            'Tests\Unit\Services\StripeServiceTest',
            'Tests\Unit\Services\EmailServiceTest',
            'Tests\Unit\Services\MonthlyInvoiceServiceTest',
            'Tests\Unit\Services\NotificationServiceTest',
        ],
        'Controllers' => [
            'Tests\Unit\Controllers\OpenAIControllerTest',
            'Tests\Unit\Controllers\DashboardControllerTest',
            'Tests\Unit\Controllers\SubscriptionControllerTest',
        ],
        'Middleware' => [
            'Tests\Unit\Middleware\CheckAccessRestrictionsTest',
            'Tests\Unit\Middleware\CheckSubscriptionStatusTest',
        ],
        'Jobs' => [
            'Tests\Unit\Jobs\ProcessSubscriptionLifecycleTest',
        ],
    ];

    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function run($suite = null)
    {
        $this->displayHeader();

        if ($suite && isset($this->testSuites[$suite])) {
            $this->runTestSuite($suite, $this->testSuites[$suite]);
        } else {
            foreach ($this->testSuites as $suiteName => $tests) {
                $this->runTestSuite($suiteName, $tests);
            }
        }

        $this->displaySummary();
        return $this->failedTests === 0;
    }

    private function displayHeader()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                    Medicine-AI Test Runner                   ║\n";
        echo "║                                                              ║\n";
        echo "║  Comprehensive Unit Test Suite for Medical AI Application   ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function runTestSuite($suiteName, $tests)
    {
        echo "🧪 Running {$suiteName} Tests...\n";
        echo str_repeat("─", 60) . "\n";

        $suiteResults = [
            'passed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($tests as $testClass) {
            $result = $this->runSingleTest($testClass);

            if ($result['success']) {
                $suiteResults['passed']++;
                $this->passedTests++;
                echo "  ✅ {$testClass} - PASSED ({$result['tests']} tests)\n";
            } else {
                $suiteResults['failed']++;
                $this->failedTests++;
                $suiteResults['errors'][] = $result['error'];
                echo "  ❌ {$testClass} - FAILED\n";
                echo "     Error: {$result['error']}\n";
            }

            $this->totalTests += $result['tests'];
        }

        $this->results[$suiteName] = $suiteResults;
        echo "\n";
    }

    private function runSingleTest($testClass)
    {
        try {
            // Use PHPUnit to run the test
            $command = "vendor/bin/phpunit --testdox --colors=never {$testClass}";
            $output = [];
            $returnCode = 0;

            exec($command . " 2>&1", $output, $returnCode);

            $outputString = implode("\n", $output);

            // Count tests from output
            $testCount = $this->extractTestCount($outputString);

            return [
                'success' => $returnCode === 0,
                'tests' => $testCount,
                'error' => $returnCode !== 0 ? $this->extractError($outputString) : null,
                'output' => $outputString
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'tests' => 0,
                'error' => $e->getMessage(),
                'output' => ''
            ];
        }
    }

    private function extractTestCount($output)
    {
        // Try to extract test count from PHPUnit output
        if (preg_match('/(\d+) tests?/', $output, $matches)) {
            return (int) $matches[1];
        }

        // Fallback: count test method patterns
        return substr_count($output, '✓') + substr_count($output, '✗');
    }

    private function extractError($output)
    {
        $lines = explode("\n", $output);

        // Look for error patterns
        foreach ($lines as $line) {
            if (strpos($line, 'FAILURES!') !== false ||
                strpos($line, 'ERRORS!') !== false ||
                strpos($line, 'Fatal error') !== false) {
                return trim($line);
            }
        }

        // Return last few lines as error context
        return implode("\n", array_slice($lines, -3));
    }

    private function displaySummary()
    {
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                        TEST SUMMARY                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        foreach ($this->results as $suiteName => $result) {
            $total = $result['passed'] + $result['failed'];
            $status = $result['failed'] === 0 ? '✅' : '❌';

            echo "{$status} {$suiteName}: {$result['passed']}/{$total} test classes passed\n";

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    echo "   └─ Error: {$error}\n";
                }
            }
        }

        echo "\n";
        echo str_repeat("═", 60) . "\n";

        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 1) : 0;

        if ($this->failedTests === 0) {
            echo "🎉 ALL TESTS PASSED! ({$this->totalTests} tests, {$successRate}% success rate)\n";
        } else {
            echo "⚠️  SOME TESTS FAILED: {$this->passedTests} passed, {$this->failedTests} failed\n";
            echo "   Success Rate: {$successRate}%\n";
        }

        echo str_repeat("═", 60) . "\n";
        echo "\n";
    }

    public function runCoverage()
    {
        echo "📊 Generating Code Coverage Report...\n";

        $command = "vendor/bin/phpunit --coverage-html coverage-report --coverage-text";
        $output = [];
        $returnCode = 0;

        exec($command . " 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            echo "✅ Coverage report generated in coverage-report/ directory\n";
        } else {
            echo "❌ Failed to generate coverage report\n";
            echo implode("\n", $output) . "\n";
        }
    }

    public function validateTestEnvironment()
    {
        echo "🔍 Validating Test Environment...\n";

        $checks = [
            'PHP Version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PHPUnit Available' => file_exists(__DIR__ . '/../vendor/bin/phpunit'),
            'Laravel Framework' => class_exists('Illuminate\Foundation\Application'),
            'Test Database' => $this->checkTestDatabase(),
            'Required Extensions' => $this->checkRequiredExtensions(),
        ];

        foreach ($checks as $check => $passed) {
            $status = $passed ? '✅' : '❌';
            echo "  {$status} {$check}\n";
        }

        echo "\n";
        return array_reduce($checks, function($carry, $item) {
            return $carry && $item;
        }, true);
    }

    private function checkTestDatabase()
    {
        try {
            // Check if SQLite is available for testing
            return extension_loaded('sqlite3') || extension_loaded('pdo_sqlite');
        } catch (Exception $e) {
            return false;
        }
    }

    private function checkRequiredExtensions()
    {
        $required = ['json', 'mbstring', 'openssl', 'curl'];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                return false;
            }
        }

        return true;
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $runner = new TestRunner();

    $options = getopt('', ['suite:', 'coverage', 'validate', 'help']);

    if (isset($options['help'])) {
        echo "Medicine-AI Test Runner\n\n";
        echo "Usage: php run-tests.php [options]\n\n";
        echo "Options:\n";
        echo "  --suite=SUITE    Run specific test suite (Models, Services, Controllers, Middleware, Jobs)\n";
        echo "  --coverage       Generate code coverage report\n";
        echo "  --validate       Validate test environment\n";
        echo "  --help           Show this help message\n\n";
        exit(0);
    }

    if (isset($options['validate'])) {
        $valid = $runner->validateTestEnvironment();
        exit($valid ? 0 : 1);
    }

    if (isset($options['coverage'])) {
        $runner->runCoverage();
        exit(0);
    }

    $suite = $options['suite'] ?? null;
    $success = $runner->run($suite);

    exit($success ? 0 : 1);
}
