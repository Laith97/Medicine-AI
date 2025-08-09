/**
 * Notification Test Runner
 * Comprehensive testing utility for the notification system
 */
class NotificationTestRunner {
    constructor() {
        this.tests = [];
        this.results = [];
        this.init();
    }

    init() {
        // Wait for DOM and systems to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupTests());
        } else {
            setTimeout(() => this.setupTests(), 1000);
        }
    }

    setupTests() {
        console.log('🧪 Setting up notification system tests...');

        // Register tests
        this.addTest('Echo Connection', () => this.testEchoConnection());
        this.addTest('Sound System', () => this.testSoundSystem());
        this.addTest('Toast Display', () => this.testToastDisplay());
        this.addTest('Dropdown Update', () => this.testDropdownUpdate());
        this.addTest('Real-time Listener', () => this.testRealTimeListener());

        // Add test runner UI if in debug mode
        this.createTestUI();
    }

    addTest(name, testFunction) {
        this.tests.push({ name, testFunction });
    }

    async runTest(testIndex) {
        const test = this.tests[testIndex];
        if (!test) return;

        console.log(`🔬 Running test: ${test.name}`);
        try {
            const result = await test.testFunction();
            const testResult = {
                name: test.name,
                success: result.success,
                message: result.message,
                details: result.details || null
            };

            this.results[testIndex] = testResult;
            this.updateTestUI(testIndex, testResult);

            console.log(`${result.success ? '✅' : '❌'} ${test.name}: ${result.message}`);
            return testResult;
        } catch (error) {
            const testResult = {
                name: test.name,
                success: false,
                message: `Error: ${error.message}`,
                details: error
            };

            this.results[testIndex] = testResult;
            this.updateTestUI(testIndex, testResult);

            console.error(`❌ ${test.name} failed:`, error);
            return testResult;
        }
    }

    async runAllTests() {
        console.log('🚀 Running all notification system tests...');
        for (let i = 0; i < this.tests.length; i++) {
            await this.runTest(i);
            // Small delay between tests
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        console.log('✅ All tests completed');
        return this.results;
    }

    // Individual test functions
    testEchoConnection() {
        return new Promise((resolve) => {
            if (typeof window.Echo === 'undefined') {
                resolve({ success: false, message: 'Echo is not loaded' });
                return;
            }

            if (!window.Echo.connector) {
                resolve({ success: false, message: 'Echo connector not available' });
                return;
            }

            // Try to get user ID
            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!userId) {
                resolve({ success: false, message: 'User ID not found' });
                return;
            }

            resolve({
                success: true,
                message: 'Echo connection ready',
                details: { userId }
            });
        });
    }

    testSoundSystem() {
        return new Promise((resolve) => {
            // Check for notification sound
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                resolve({
                    success: true,
                    message: 'NotificationSound system available',
                    details: 'Using window.notificationSound'
                });
                return;
            }

            // Check for unified system sound
            if (window.unifiedNotifications && window.unifiedNotifications.sound) {
                resolve({
                    success: true,
                    message: 'Unified notification sound available',
                    details: 'Using unifiedNotifications.sound'
                });
                return;
            }

            // Test basic audio
            try {
                const audio = new Audio('/sounds/notification.mp3');
                resolve({
                    success: true,
                    message: 'Basic audio system available',
                    details: 'Fallback to Audio constructor'
                });
            } catch (error) {
                resolve({
                    success: false,
                    message: 'No sound system available',
                    details: error.message
                });
            }
        });
    }

    testToastDisplay() {
        return new Promise((resolve) => {
            if (!window.unifiedNotifications) {
                resolve({ success: false, message: 'Unified notification system not available' });
                return;
            }

            try {
                // Test toast creation
                const testNotification = {
                    id: 'test-toast-' + Date.now(),
                    type: 'test',
                    title: 'Test Toast',
                    message: 'Testing toast display functionality',
                    created_at: new Date().toISOString()
                };

                window.unifiedNotifications.showToastNotification(testNotification);

                // Check if toast was created
                setTimeout(() => {
                    const toast = document.querySelector('.notification-toast');
                    if (toast) {
                        // Remove test toast
                        toast.closest('.notification-toast-container')?.remove();
                        resolve({ success: true, message: 'Toast display working' });
                    } else {
                        resolve({ success: false, message: 'Toast not created' });
                    }
                }, 100);
            } catch (error) {
                resolve({ success: false, message: 'Toast error: ' + error.message });
            }
        });
    }

    testDropdownUpdate() {
        return new Promise((resolve) => {
            const dropdown = document.querySelector('.notifications-dropdown');
            if (!dropdown) {
                resolve({ success: false, message: 'Notification dropdown not found in DOM' });
                return;
            }

            const notificationList = dropdown.querySelector('.notification-list, #notification-list');
            if (!notificationList) {
                resolve({ success: false, message: 'Notification list not found in dropdown' });
                return;
            }

            resolve({
                success: true,
                message: 'Dropdown elements available',
                details: {
                    dropdown: true,
                    list: true,
                    currentItems: notificationList.children.length
                }
            });
        });
    }

    testRealTimeListener() {
        return new Promise((resolve) => {
            if (!window.unifiedNotifications || !window.unifiedNotifications.isInitialized) {
                resolve({ success: false, message: 'Unified notification system not initialized' });
                return;
            }

            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!userId) {
                resolve({ success: false, message: 'User ID not available for channel subscription' });
                return;
            }

            if (!window.Echo || !window.Echo.connector) {
                resolve({ success: false, message: 'Echo not ready for real-time listening' });
                return;
            }

            resolve({
                success: true,
                message: 'Real-time listener ready',
                details: {
                    userId,
                    channel: `App.User.${userId}`,
                    echoReady: true
                }
            });
        });
    }

    createTestUI() {
        // Only create UI in debug mode
        if (!document.querySelector('meta[name="app-debug"]') && !window.location.search.includes('debug')) {
            return;
        }

        const testUI = document.createElement('div');
        testUI.id = 'notification-test-runner';
        testUI.style.cssText = `
            position: fixed;
            top: 10px;
            left: 10px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10000;
            font-family: monospace;
            font-size: 12px;
            max-width: 350px;
        `;

        testUI.innerHTML = `
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 10px;">
                <strong>🧪 Notification Tests</strong>
                <button id="close-test-runner" style="margin-left: auto; background: none; border: none; cursor: pointer;">✕</button>
            </div>
            <button id="run-all-tests" style="width: 100%; padding: 5px; margin-bottom: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Run All Tests</button>
            <div id="test-results"></div>
        `;

        document.body.appendChild(testUI);

        // Event listeners
        document.getElementById('close-test-runner').addEventListener('click', () => {
            testUI.remove();
        });

        document.getElementById('run-all-tests').addEventListener('click', () => {
            this.runAllTests();
        });
    }

    updateTestUI(testIndex, result) {
        const resultsDiv = document.getElementById('test-results');
        if (!resultsDiv) return;

        const testDiv = document.createElement('div');
        testDiv.style.cssText = `
            padding: 5px;
            margin: 2px 0;
            border-left: 3px solid ${result.success ? '#28a745' : '#dc3545'};
            background: ${result.success ? '#d4edda' : '#f8d7da'};
        `;

        testDiv.innerHTML = `
            <div><strong>${result.success ? '✅' : '❌'} ${result.name}</strong></div>
            <div style="font-size: 11px; color: #666;">${result.message}</div>
        `;

        // Replace existing test result or add new one
        const existingTest = resultsDiv.querySelector(`[data-test-index="${testIndex}"]`);
        if (existingTest) {
            existingTest.replaceWith(testDiv);
        } else {
            testDiv.setAttribute('data-test-index', testIndex);
            resultsDiv.appendChild(testDiv);
        }
    }
}

// Initialize test runner
window.NotificationTestRunner = NotificationTestRunner;

// Auto-start if in debug mode
if (document.querySelector('meta[name="app-debug"]') || window.location.search.includes('test-notifications')) {
    window.notificationTestRunner = new NotificationTestRunner();
}
