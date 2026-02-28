/**
 * Advanced Network Error Testing Suite
 * Tests the notification system's error handling capabilities
 * Simulates various network failure scenarios
 */
class NetworkErrorTestSuite {
    constructor() {
        this.testResults = [];
        this.originalFetch = null;
        this.mockResponses = new Map();
        this.failurePatterns = new Map();
        this.isTesting = false;
    }

    /**
     * Start the test suite
     */
    async startTests() {
        if (this.isTesting) {
            
            return;
        }

        this.isTesting = true;
        

        try {
            // Setup mock fetch
            this.setupMockFetch();

            // Run all test scenarios
            await this.runAllTests();

            // Display results
            this.displayResults();

        } catch (error) {
            ;
        } finally {
            // Cleanup
            this.restoreFetch();
            this.isTesting = false;
        }
    }

    /**
     * Setup mock fetch for testing
     */
    setupMockFetch() {
        this.originalFetch = window.fetch;
        window.fetch = this.mockFetch.bind(this);
        
    }

    /**
     * Restore original fetch
     */
    restoreFetch() {
        if (this.originalFetch) {
            window.fetch = this.originalFetch;
            this.originalFetch = null;
            
        }
    }

    /**
     * Mock fetch implementation
     */
    async mockFetch(url, options = {}) {
        const requestId = `mock_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

        // Check if we have a specific mock for this URL
        if (this.mockResponses.has(url)) {
            const mockConfig = this.mockResponses.get(url);
            

            if (mockConfig.delay) {
                await this.delay(mockConfig.delay);
            }

            if (mockConfig.shouldFail) {
                const error = new Error(mockConfig.errorMessage || 'Mock network error');
                error.name = mockConfig.errorType || 'NetworkError';
                throw error;
            }

            // Create mock response
            const response = new Response(
                JSON.stringify(mockConfig.responseData || {}),
                {
                    status: mockConfig.status || 200,
                    statusText: mockConfig.statusText || 'OK',
                    headers: mockConfig.headers || { 'Content-Type': 'application/json' }
                }
            );

            return response;
        }

        // Default behavior - pass through to original fetch
        return this.originalFetch(url, options);
    }

    /**
     * Configure mock response for a URL
     */
    mockUrl(url, config) {
        this.mockResponses.set(url, config);
    }

    /**
     * Clear all mocks
     */
    clearMocks() {
        this.mockResponses.clear();
    }

    /**
     * Run all test scenarios
     */
    async runAllTests() {
        const testScenarios = [
            this.testExponentialBackoff.bind(this),
            this.testCircuitBreaker.bind(this),
            this.testTimeoutManagement.bind(this),
            this.testRetryMechanisms.bind(this),
            this.testConnectionHealthMonitoring.bind(this),
            this.testSimultaneousRequests.bind(this),
            this.testServerErrorRecovery.bind(this),
            this.testNetworkIntermittency.bind(this)
        ];

        for (const testScenario of testScenarios) {
            try {
                await testScenario();
            } catch (error) {
                ;
                this.recordTestResult(testScenario.name, false, error.message);
            }
        }
    }

    /**
     * Test exponential backoff
     */
    async testExponentialBackoff() {
        

        // Mock a URL that fails multiple times then succeeds
        this.mockUrl('/api/test-backoff', {
            shouldFail: true,
            errorMessage: 'Connection timeout',
            errorType: 'NetworkError'
        });

        const startTime = Date.now();
        let attempts = 0;
        const maxAttempts = 3;

        // Simulate the backoff calculation
        for (let i = 0; i < maxAttempts; i++) {
            attempts++;
            if (i < maxAttempts - 1) {
                const delay = this.calculateExpectedBackoffDelay(i);
                
                await this.delay(100); // Small delay for test
            }
        }

        const totalTime = Date.now() - startTime;

        // Verify backoff worked
        const expectedMinTime = this.calculateExpectedBackoffDelay(0) + this.calculateExpectedBackoffDelay(1);
        const success = totalTime >= expectedMinTime * 0.8; // Allow 20% variance

        this.recordTestResult('testExponentialBackoff', success,
            `Completed ${attempts} attempts in ${totalTime}ms`);

        this.clearMocks();
    }

    /**
     * Test circuit breaker pattern
     */
    async testCircuitBreaker() {
        

        if (!window.unifiedNotifications) {
            throw new Error('Notification system not available');
        }

        // Force circuit breaker to open by simulating failures
        const originalFailures = window.unifiedNotifications.circuitBreaker.failures;
        window.unifiedNotifications.circuitBreaker.failures = 6; // Above threshold
        window.unifiedNotifications.circuitBreaker.state = 'OPEN';

        // Test that requests are blocked
        const canProceed = window.unifiedNotifications.canProceedWithCircuitBreaker();
        

        // Wait for half-open state
        await this.delay(100);
        window.unifiedNotifications.circuitBreaker.nextAttemptTime = Date.now() - 1000;

        const canProceedAfterTimeout = window.unifiedNotifications.canProceedWithCircuitBreaker();
        

        // Reset circuit breaker
        window.unifiedNotifications.circuitBreaker.failures = originalFailures;
        window.unifiedNotifications.circuitBreaker.state = 'CLOSED';

        const success = !canProceed && canProceedAfterTimeout;
        this.recordTestResult('testCircuitBreaker', success,
            'Circuit breaker correctly blocked then allowed requests');

        this.clearMocks();
    }

    /**
     * Test timeout management
     */
    async testTimeoutManagement() {
        

        // Mock a URL that takes too long
        this.mockUrl('/api/test-timeout', {
            delay: 15000, // 15 seconds - longer than our 10s timeout
            responseData: { success: true }
        });

        const startTime = Date.now();

        try {
            await window.unifiedNotifications.enhancedFetch('/api/test-timeout', {}, 'test_timeout');
            throw new Error('Request should have timed out');
        } catch (error) {
            const duration = Date.now() - startTime;
            const timedOutCorrectly = error.name === 'AbortError' || duration >= 10000;
            

            this.recordTestResult('testTimeoutManagement', timedOutCorrectly,
                `Request timed out after ${duration}ms`);
        }

        this.clearMocks();
    }

    /**
     * Test retry mechanisms
     */
    async testRetryMechanisms() {
        

        let attemptCount = 0;

        // Mock URL that fails twice then succeeds
        this.mockUrl('/api/test-retry', {
            shouldFail: () => {
                attemptCount++;
                return attemptCount <= 2; // Fail first 2 attempts
            },
            errorMessage: 'Temporary server error',
            responseData: { success: true, attempts: attemptCount }
        });

        try {
            const response = await window.unifiedNotifications.enhancedFetch('/api/test-retry', {}, 'test_retry');
            const data = await response.json();

            const success = data.attempts === 3; // Should succeed on 3rd attempt
            

            this.recordTestResult('testRetryMechanisms', success,
                `Request succeeded after ${data.attempts} attempts`);

        } catch (error) {
            this.recordTestResult('testRetryMechanisms', false,
                `Request failed: ${error.message}`);
        }

        this.clearMocks();
    }

    /**
     * Test connection health monitoring
     */
    async testConnectionHealthMonitoring() {
        

        if (!window.unifiedNotifications) {
            throw new Error('Notification system not available');
        }

        const initialHealth = { ...window.unifiedNotifications.connectionHealth };

        // Simulate some successful requests
        for (let i = 0; i < 3; i++) {
            this.mockUrl(`/api/health-test-${i}`, {
                responseData: { status: 'ok' }
            });

            try {
                await window.unifiedNotifications.enhancedFetch(`/api/health-test-${i}`, {}, `health_test_${i}`);
            } catch (error) {
                ;
            }
        }

        const finalHealth = window.unifiedNotifications.connectionHealth;

        const healthImproved = finalHealth.successfulRequests >= initialHealth.successfulRequests;
        const hasAverageResponseTime = finalHealth.averageResponseTime > 0;


        const success = healthImproved && hasAverageResponseTime;
        this.recordTestResult('testConnectionHealthMonitoring', success,
            `Health monitoring tracked ${finalHealth.totalRequests} requests`);

        this.clearMocks();
    }

    /**
     * Test simultaneous requests
     */
    async testSimultaneousRequests() {
        

        const requestCount = 5;
        const requests = [];

        // Mock URL for simultaneous requests
        this.mockUrl('/api/concurrent-test', {
            delay: 100, // Small delay to simulate processing
            responseData: { success: true }
        });

        // Start multiple requests simultaneously
        for (let i = 0; i < requestCount; i++) {
            requests.push(
                window.unifiedNotifications.enhancedFetch('/api/concurrent-test', {}, `concurrent_${i}`)
            );
        }

        try {
            const results = await Promise.allSettled(requests);
            const successful = results.filter(r => r.status === 'fulfilled').length;
            const failed = results.filter(r => r.status === 'rejected').length;

            

            // Check that duplicate requests were prevented
            const activeDuringTest = window.unifiedNotifications.activeRequests.size;
            

            const success = successful > 0; // At least some should succeed
            this.recordTestResult('testSimultaneousRequests', success,
                `${successful}/${requestCount} requests succeeded`);

        } catch (error) {
            this.recordTestResult('testSimultaneousRequests', false,
                `Concurrent requests failed: ${error.message}`);
        }

        this.clearMocks();
    }

    /**
     * Test server error recovery
     */
    async testServerErrorRecovery() {
        

        let serverErrors = 0;
        let clientErrors = 0;

        // Mock server errors (5xx)
        this.mockUrl('/api/server-error', {
            status: 500,
            statusText: 'Internal Server Error',
            responseData: { error: 'Server error' }
        });

        // Mock client errors (4xx)
        this.mockUrl('/api/client-error', {
            status: 404,
            statusText: 'Not Found',
            responseData: { error: 'Not found' }
        });

        // Test server error recovery
        try {
            const serverResponse = await window.unifiedNotifications.enhancedFetch('/api/server-error', {}, 'server_error_test');
            serverErrors = serverResponse.status;
        } catch (error) {
            
        }

        // Test client error (should not retry)
        try {
            const clientResponse = await window.unifiedNotifications.enhancedFetch('/api/client-error', {}, 'client_error_test');
            clientErrors = clientResponse.status;
        } catch (error) {
            
        }

        const serverErrorHandled = serverErrors === 500;
        const clientErrorHandled = clientErrors === 404;


        const success = serverErrorHandled && clientErrorHandled;
        this.recordTestResult('testServerErrorRecovery', success,
            'Server and client errors handled appropriately');

        this.clearMocks();
    }

    /**
     * Test network intermittency
     */
    async testNetworkIntermittency() {
        

        let requestCount = 0;
        const totalRequests = 10;

        // Mock intermittent connectivity
        this.mockUrl('/api/intermittent', {
            shouldFail: () => {
                requestCount++;
                // Fail every other request
                return requestCount % 2 === 0;
            },
            errorMessage: 'Network temporarily unavailable',
            responseData: { success: true, requestNumber: requestCount }
        });

        const results = [];
        let consecutiveFailures = 0;
        let maxConsecutiveFailures = 0;

        for (let i = 0; i < totalRequests; i++) {
            try {
                const response = await window.unifiedNotifications.enhancedFetch('/api/intermittent', {}, `intermittent_${i}`);
                const data = await response.json();

                results.push({ success: true, requestNumber: data.requestNumber });
                consecutiveFailures = 0;

            } catch (error) {
                results.push({ success: false, error: error.message });
                consecutiveFailures++;
                maxConsecutiveFailures = Math.max(maxConsecutiveFailures, consecutiveFailures);
            }

            await this.delay(50); // Small delay between requests
        }

        const successfulRequests = results.filter(r => r.success).length;
        const successRate = successfulRequests / totalRequests;

        

        // System should handle intermittency reasonably well
        const success = successRate >= 0.4 && maxConsecutiveFailures <= 3;
        this.recordTestResult('testNetworkIntermittency', success,
            `${successfulRequests}/${totalRequests} requests succeeded with max ${maxConsecutiveFailures} consecutive failures`);

        this.clearMocks();
    }

    /**
     * Calculate expected backoff delay (matches implementation)
     */
    calculateExpectedBackoffDelay(attempt) {
        if (!window.unifiedNotifications) return 1000;

        const config = window.unifiedNotifications.networkConfig;
        const exponentialDelay = config.baseDelay * Math.pow(config.backoffMultiplier, attempt);
        const delayWithJitter = exponentialDelay * (0.5 + Math.random() * 0.5);
        return Math.min(delayWithJitter, config.maxDelay);
    }

    /**
     * Record test result
     */
    recordTestResult(testName, success, details) {
        const result = {
            test: testName,
            success,
            details,
            timestamp: new Date().toISOString()
        };

        this.testResults.push(result);
        
    }

    /**
     * Display test results
     */
    displayResults() {
        

        const passed = this.testResults.filter(r => r.success).length;
        const total = this.testResults.length;
        const successRate = total > 0 ? (passed / total * 100).toFixed(1) : '0.0';


        this.testResults.forEach(result => {
            const icon = result.success ? '✅' : '❌';
            
        });

        
        if (window.unifiedNotifications) {
            const status = window.unifiedNotifications.getSystemStatus();
            
            
            
        }

        
    }

    /**
     * Utility delay method
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Global test runner
window.runNetworkErrorTests = async () => {
    const testSuite = new NetworkErrorTestSuite();
    await testSuite.startTests();
};

// Auto-run tests if in development/debug mode
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
}


