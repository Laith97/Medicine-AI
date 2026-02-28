// Mobile Responsiveness Test for Toast Notifications
// This file provides test functions to verify toast positioning across different screen sizes

class NotificationTester {
    constructor() {
        this.testResults = [];
    }

    // Test toast positioning at different screen sizes
    async testResponsivePositioning() {
        // Starting mobile responsiveness tests...

        const testSizes = [
            { width: 1920, height: 1080, name: 'Desktop' },
            { width: 768, height: 1024, name: 'Tablet' },
            { width: 576, height: 800, name: 'Mobile Large' },
            { width: 375, height: 667, name: 'Mobile Medium' },
            { width: 320, height: 568, name: 'Mobile Small' }
        ];

        for (const size of testSizes) {
            await this.testScreenSize(size);
        }

        this.displayResults();
    }

    async testScreenSize(size) {
        // Testing screen size

        // Simulate screen size change
        Object.defineProperty(window, 'innerWidth', {
            writable: true,
            configurable: true,
            value: size.width
        });

        Object.defineProperty(window, 'innerHeight', {
            writable: true,
            configurable: true,
            value: size.height
        });

        // Trigger resize event
        window.dispatchEvent(new Event('resize'));

        // Wait for positioning to update
        await new Promise(resolve => setTimeout(resolve, 300));

        // Create a test toast
        const testNotification = {
            id: `test-${size.name.toLowerCase()}-${Date.now()}`,
            title: `Test - ${size.name}`,
            message: `Testing toast positioning on ${size.name} screen (${size.width}px width)`,
            type: 'info'
        };

        // Show test toast
        if (window.enhancedNotificationSystem) {
            window.enhancedNotificationSystem.showToastNotification(testNotification);

            // Wait for toast to appear
            await new Promise(resolve => setTimeout(resolve, 500));

            // Check positioning
            const toast = document.querySelector('.enhanced-notification-toast:last-child');
            if (toast) {
                const rect = toast.getBoundingClientRect();
                const computedStyle = window.getComputedStyle(toast);

                const result = {
                    screenSize: size.name,
                    width: size.width,
                    toastRect: rect,
                    computedPosition: {
                        top: computedStyle.top,
                        bottom: computedStyle.bottom,
                        left: computedStyle.left,
                        right: computedStyle.right,
                        transform: computedStyle.transform,
                        maxWidth: computedStyle.maxWidth
                    },
                    isVisible: rect.width > 0 && rect.height > 0,
                    isProperlyPositioned: this.validatePositioning(size, rect, computedStyle)
                };

                this.testResults.push(result);
                // Test completed
            } else {
                // Failed to create toast for size
            }
        } else {
            // Enhanced notification system not available
        }

        // Wait before next test
        await new Promise(resolve => setTimeout(resolve, 1000));
    }

    validatePositioning(size, rect, computedStyle) {
        if (size.width <= 576) {
            // Small mobile: should be bottom positioned
            return computedStyle.bottom !== 'auto' && computedStyle.bottom !== '';
        } else if (size.width <= 768) {
            // Mobile/tablet: should be bottom positioned
            return computedStyle.bottom !== 'auto' && computedStyle.bottom !== '';
        } else {
            // Desktop: should be top-right positioned
            return computedStyle.top !== 'auto' && computedStyle.top !== '' &&
                   computedStyle.right !== 'auto' && computedStyle.right !== '';
        }
    }

    displayResults() {
        // Test Results Summary:
        // Results table would be displayed here

        const passedTests = this.testResults.filter(r => r.isVisible && r.isProperlyPositioned).length;
        const totalTests = this.testResults.length;

        // Test Results: tests passed

        if (passedTests === totalTests) {
            // All mobile responsiveness tests passed!
        } else {
            // Some tests failed. Check the results above for details.
        }
    }

    // Manual test functions for development
    testDesktop() {
        this.testScreenSize({ width: 1920, height: 1080, name: 'Desktop Manual' });
    }

    testTablet() {
        this.testScreenSize({ width: 768, height: 1024, name: 'Tablet Manual' });
    }

    testMobile() {
        this.testScreenSize({ width: 375, height: 667, name: 'Mobile Manual' });
    }

    testSmallMobile() {
        this.testScreenSize({ width: 320, height: 568, name: 'Small Mobile Manual' });
    }
}

// Make tester available globally for manual testing
window.NotificationTester = NotificationTester;
window.notificationTester = new NotificationTester();

// Add convenience functions to window
window.testToastResponsiveness = () => window.notificationTester.testResponsivePositioning();
window.testDesktopToasts = () => window.notificationTester.testDesktop();
window.testTabletToasts = () => window.notificationTester.testTablet();
window.testMobileToasts = () => window.notificationTester.testMobile();
window.testSmallMobileToasts = () => window.notificationTester.testSmallMobile();

// Toast Notification Tester loaded!
// Available test functions:
//  - testToastResponsiveness() - Run all tests
//  - testDesktopToasts() - Test desktop positioning
//  - testTabletToasts() - Test tablet positioning
//  - testMobileToasts() - Test mobile positioning
//  - testSmallMobileToasts() - Test small mobile positioning
