/**
 * Patient Status Display Manager
 *
 * Manages real-time appointment status updates for patients
 * with visual indicators, timeline updates, and notifications.
 */

class PatientStatusDisplay {
    constructor(options = {}) {
        this.options = {
            appointmentId: options.appointmentId,
            userId: options.userId,
            userRole: options.userRole || 'patient',
            pusherKey: options.pusherKey || window.PUSHER_KEY,
            cluster: options.cluster || window.PUSHER_CLUSTER,
            debugMode: options.debugMode || false,
            ...options
        };

        this.realtimeClient = null;
        this.currentStatus = null;
        this.statusHistory = [];
        this.isInitialized = false;
        this.lastUpdate = null;

        this.init();
    }

    /**
     * Initialize the patient status display
     */
    async init() {
        if (!this.options.appointmentId) {
            // console.error('PatientStatusDisplay: Appointment ID is required');
            return;
        }

        try {
            await this.initializeRealtimeClient();
            this.setupEventListeners();
            this.loadCurrentStatus();

            this.isInitialized = true;
            // console.log('PatientStatusDisplay initialized successfully');
        } catch (error) {
            // console.error('Failed to initialize PatientStatusDisplay:', error);
        }
    }

    /**
     * Initialize the real-time client
     */
    async initializeRealtimeClient() {
        this.realtimeClient = new RealtimeAppointmentClient({
            pusherKey: this.options.pusherKey,
            cluster: this.options.cluster,
            debugMode: this.options.debugMode,
            enableNotifications: true,
            enableSounds: true
        });

        // Set up event listeners for status changes
        this.realtimeClient.addEventListener('appointment.status-changed', (event) => {
            this.handleAppointmentStatusChange(event);
        });

        this.realtimeClient.addEventListener('appointment.updated', (event) => {
            this.handleAppointmentUpdate(event);
        });

        this.realtimeClient.addEventListener('appointment.queue.position-updated', (event) => {
            this.handleQueuePositionUpdate(event);
        });

        // Initialize the client
        await this.realtimeClient.initialize(this.options.userId, this.options.userRole);
    }

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Connection status changes
        window.addEventListener('realtimeConnectionStateChanged', (event) => {
            this.updateConnectionStatus(event.detail);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            this.handleKeyboardShortcuts(event);
        });

        // Refresh button if exists
        const refreshBtn = document.getElementById('refresh-status');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshStatus();
            });
        }
    }

    /**
     * Load current appointment status
     */
    async loadCurrentStatus() {
        try {
            const response = await fetch(`/api/appointments/${this.options.appointmentId}/status`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                this.updateStatusDisplay(result.data);
            }

        } catch (error) {
            // console.error('Error loading current status:', error);
        }
    }

    /**
     * Handle appointment status change from real-time events
     */
    handleAppointmentStatusChange(event) {
        const { data } = event;
        if (!data || data.appointment_id != this.options.appointmentId) return;

        // Add to history
        this.statusHistory.push({
            status: data.new_status,
            timestamp: new Date(),
            previousStatus: data.old_status,
            changedBy: data.changed_by
        });

        // Update the display
        this.updateStatusDisplay(data);
        this.updateTimeline(data);

        // Show notification
        this.showStatusChangeNotification(data);

        // Play sound
        this.playStatusChangeSound(data.new_status);

        this.lastUpdate = new Date();
    }

    /**
     * Handle general appointment updates
     */
    handleAppointmentUpdate(event) {
        const { data } = event;
        if (!data || data.appointment_id != this.options.appointmentId) return;

        // Update specific fields that changed
        if (data.estimated_wait_minutes !== undefined) {
            this.updateEstimatedWaitTime(data.estimated_wait_minutes);
        }

        if (data.queue_position !== undefined) {
            this.updateQueuePosition(data.queue_position);
        }
    }

    /**
     * Handle queue position updates
     */
    handleQueuePositionUpdate(event) {
        const { data } = event;
        if (!data || data.appointment_id != this.options.appointmentId) return;

        this.updateQueuePosition(data.position);
        this.showQueuePositionNotification(data);
    }

    /**
     * Update the status display
     */
    updateStatusDisplay(statusData) {
        this.currentStatus = statusData.new_status || statusData.status;

        // Update status icon
        const statusIcon = document.getElementById('status-icon');
        if (statusIcon) {
            // Remove existing status classes
            statusIcon.className = `status-icon status-${this.currentStatus.replace('_', '-')}`;

            // Update icon
            const iconElement = statusIcon.querySelector('i');
            if (iconElement) {
                iconElement.className = `fas fa-${this.getStatusIcon(this.currentStatus)}`;
            }
        }

        // Update status title
        const statusTitle = document.getElementById('status-title');
        if (statusTitle) {
            statusTitle.textContent = this.getStatusTitle(this.currentStatus);
        }

        // Update status description
        const statusDescription = document.getElementById('status-description');
        if (statusDescription) {
            statusDescription.textContent = this.getStatusDescription(this.currentStatus, statusData);
        }

        // Update next steps
        this.updateNextSteps(this.currentStatus, statusData);

        // Update estimated wait time
        if (statusData.estimated_wait_minutes !== undefined) {
            this.updateEstimatedWaitTime(statusData.estimated_wait_minutes);
        }

        // Add visual feedback
        this.addStatusChangeAnimation();
    }

    /**
     * Update the timeline based on current status
     */
    updateTimeline(statusData) {
        const timeline = document.getElementById('status-timeline');
        if (!timeline) return;

        const timelineItems = timeline.querySelectorAll('.timeline-item');

        timelineItems.forEach((item, index) => {
            const itemTitle = item.querySelector('.timeline-title')?.textContent.toLowerCase() || '';

            let itemStatus = 'pending';
            let iconClass = 'circle';

            // Determine item status based on current appointment status
            if (itemTitle.includes('requested')) {
                itemStatus = 'completed'; // Always completed
                iconClass = 'check';
            } else if (itemTitle.includes('confirmation') || itemTitle.includes('confirm')) {
                if (['confirmed', 'check_in', 'in_progress', 'completed'].includes(this.currentStatus)) {
                    itemStatus = 'completed';
                    iconClass = 'check';
                } else if (this.currentStatus === 'pending') {
                    itemStatus = 'active';
                    iconClass = 'spinner fa-spin';
                }
            } else if (itemTitle.includes('review') || itemTitle.includes('doctor')) {
                if (['in_progress', 'completed'].includes(this.currentStatus)) {
                    itemStatus = 'completed';
                    iconClass = 'check';
                } else if (['confirmed', 'check_in'].includes(this.currentStatus)) {
                    itemStatus = 'active';
                    iconClass = 'spinner fa-spin';
                }
            } else if (itemTitle.includes('progress') || itemTitle.includes('progress')) {
                if (this.currentStatus === 'completed') {
                    itemStatus = 'completed';
                    iconClass = 'check';
                } else if (this.currentStatus === 'in_progress') {
                    itemStatus = 'active';
                    iconClass = 'spinner fa-spin';
                }
            }

            // Update item classes
            item.className = `timeline-item ${itemStatus}`;

            // Update icon
            const icon = item.querySelector('.timeline-icon i');
            if (icon) {
                icon.className = `fas fa-${iconClass}`;
            }
        });
    }

    /**
     * Update next steps based on current status
     */
    updateNextSteps(status, statusData = null) {
        const nextStepsContainer = document.getElementById('next-steps');
        if (!nextStepsContainer) return;

        let steps = [];

        switch (status) {
            case 'pending':
                steps = [
                    {
                        icon: 'clock',
                        title: 'Waiting for Confirmation',
                        description: 'Your doctor will review and confirm your appointment.'
                    },
                    {
                        icon: 'bell',
                        title: 'Notification Setup',
                        description: 'You will receive notifications about any changes.'
                    }
                ];
                break;

            case 'confirmed':
                steps = [
                    {
                        icon: 'check-circle',
                        title: 'Appointment Confirmed',
                        description: 'Your appointment has been confirmed by the doctor.'
                    },
                    {
                        icon: 'calendar-alt',
                        title: 'Prepare for Visit',
                        description: 'Please arrive 15 minutes early and bring required documents.'
                    }
                ];
                break;

            case 'check_in':
                steps = [
                    {
                        icon: 'user-check',
                        title: 'Check-in Required',
                        description: 'Please check in at the reception desk when you arrive.'
                    },
                    {
                        icon: 'clock',
                        title: 'Waiting for Doctor',
                        description: 'The doctor will see you shortly after check-in.'
                    }
                ];
                break;

            case 'in_progress':
                steps = [
                    {
                        icon: 'stethoscope',
                        title: 'In Consultation',
                        description: 'Your appointment is currently in progress.'
                    },
                    {
                        icon: 'user-md',
                        title: 'Doctor Consultation',
                        description: 'The doctor is reviewing your case and providing care.'
                    }
                ];
                break;

            case 'completed':
                steps = [
                    {
                        icon: 'check-double',
                        title: 'Appointment Completed',
                        description: 'Your appointment has been completed successfully.'
                    },
                    {
                        icon: 'file-medical',
                        title: 'Follow-up Care',
                        description: 'You will receive any necessary follow-up instructions.'
                    }
                ];
                break;

            case 'cancelled':
                steps = [
                    {
                        icon: 'times-circle',
                        title: 'Appointment Cancelled',
                        description: 'Your appointment has been cancelled.'
                    },
                    {
                        icon: 'calendar-plus',
                        title: 'Reschedule Available',
                        description: 'You can schedule a new appointment if needed.'
                    }
                ];
                break;
        }

        // Generate HTML for steps
        const stepsHtml = steps.map(step => `
            <div class="step-item">
                <div class="step-icon">
                    <i class="fas fa-${step.icon}"></i>
                </div>
                <div class="step-content">
                    <h6>${step.title}</h6>
                    <p>${step.description}</p>
                </div>
            </div>
        `).join('');

        nextStepsContainer.innerHTML = stepsHtml;
    }

    /**
     * Update estimated wait time
     */
    updateEstimatedWaitTime(waitTimeMinutes) {
        const waitElements = document.querySelectorAll('.estimated-time, [data-wait-time]');

        waitElements.forEach(element => {
            const hours = Math.floor(waitTimeMinutes / 60);
            const minutes = waitTimeMinutes % 60;

            let timeText = '';
            if (hours > 0) {
                timeText = `${hours} hour(s) ${minutes} minutes`;
            } else {
                timeText = `${minutes} minutes`;
            }

            element.textContent = timeText;
            element.setAttribute('data-wait-time', waitTimeMinutes);
        });

        // Update the estimated time card if it exists
        const estimatedCard = document.querySelector('.estimated-time-card');
        if (estimatedCard && waitTimeMinutes > 0) {
            estimatedCard.style.display = 'flex';
        }
    }

    /**
     * Update queue position
     */
    updateQueuePosition(position) {
        const positionElements = document.querySelectorAll('[data-queue-position]');

        positionElements.forEach(element => {
            element.textContent = `#${position}`;
            element.setAttribute('data-queue-position', position);
        });
    }

    /**
     * Show status change notification
     */
    showStatusChangeNotification(statusData) {
        const notification = {
            type: 'patient_status_change',
            title: this.getStatusTitle(statusData.new_status),
            message: this.getStatusNotificationMessage(statusData.new_status),
            icon: this.getStatusIcon(statusData.new_status),
            iconColor: this.getStatusColor(statusData.new_status),
            duration: 8000,
            sound: this.getStatusSoundClass(statusData.new_status)
        };

        this.displayNotification(notification);
    }

    /**
     * Show queue position notification
     */
    showQueuePositionNotification(data) {
        const notification = {
            type: 'queue_position_update',
            title: 'Queue Position Updated',
            message: `Your position in the queue is now #${data.position}`,
            icon: 'list-ol',
            iconColor: '#667eea',
            duration: 5000
        };

        this.displayNotification(notification);
    }

    /**
     * Display notification
     */
    displayNotification(notification) {
        // Use the realtime client's notification system
        if (this.realtimeClient) {
            this.realtimeClient.displayNotification(notification);
        } else {
            // Fallback to browser notification
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(notification.title, {
                    body: notification.message,
                    icon: '/images/notification-icon.png'
                });
            }
        }
    }

    /**
     * Play status change sound
     */
    playStatusChangeSound(status) {
        if (this.realtimeClient) {
            this.realtimeClient.playStatusChangeSound(status);
        }
    }

    /**
     * Add visual animation for status change
     */
    addStatusChangeAnimation() {
        const statusDisplay = document.querySelector('.patient-status-display');
        if (!statusDisplay) return;

        // Add animation class
        statusDisplay.classList.add('status-updating');

        // Remove after animation
        setTimeout(() => {
            statusDisplay.classList.remove('status-updating');
        }, 1000);
    }

    /**
     * Update connection status indicator
     */
    updateConnectionStatus(statusDetail) {
        const indicator = document.getElementById('realtime-updates');
        if (!indicator) return;

        const dot = indicator.querySelector('.realtime-dot');

        // Update dot color based on connection state
        switch (statusDetail.newState) {
            case 'connected':
                dot.style.background = '#28a745';
                break;
            case 'connecting':
            case 'reconnecting':
                dot.style.background = '#ffc107';
                break;
            case 'disconnected':
            case 'failed':
            case 'error':
                dot.style.background = '#dc3545';
                break;
        }
    }

    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(event) {
        // Only handle shortcuts when not typing in input fields
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
            return;
        }

        switch (event.key) {
            case 'r':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.refreshStatus();
                }
                break;
            case 'Escape':
                this.clearNotifications();
                break;
        }
    }

    /**
     * Refresh current status
     */
    async refreshStatus() {
        await this.loadCurrentStatus();
        this.showNotification('Status refreshed', 'info');
    }

    /**
     * Clear notifications
     */
    clearNotifications() {
        const notifications = document.querySelectorAll('.realtime-notification');
        notifications.forEach(notification => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = {
            type: 'patient_notification',
            title: 'Status Update',
            message: message,
            icon: type === 'success' ? 'check-circle' :
                  type === 'error' ? 'exclamation-circle' : 'info-circle',
            iconColor: type === 'success' ? '#28a745' :
                      type === 'error' ? '#dc3545' : '#17a2b8',
            duration: 3000
        };

        this.displayNotification(notification);
    }

    // Utility methods
    getStatusTitle(status) {
        const titles = {
            'pending': 'Appointment Pending',
            'confirmed': 'Appointment Confirmed',
            'check_in': 'Please Check In',
            'in_progress': 'In Progress',
            'completed': 'Completed',
            'cancelled': 'Cancelled',
            'no_show': 'No Show'
        };
        return titles[status] || 'Status Unknown';
    }

    getStatusDescription(status, statusData = null) {
        const descriptions = {
            'pending': 'Your appointment is currently being processed. You will be notified of any updates.',
            'confirmed': 'Your appointment has been confirmed. Please arrive 15 minutes early.',
            'check_in': 'Please check in at the reception desk when you arrive.',
            'in_progress': 'Your appointment is currently in progress with the doctor.',
            'completed': 'Your appointment has been completed successfully.',
            'cancelled': 'Your appointment has been cancelled.',
            'no_show': 'You were marked as no-show for this appointment.'
        };
        return descriptions[status] || 'Status updated';
    }

    getStatusNotificationMessage(status) {
        const messages = {
            'pending': 'Your appointment is being processed.',
            'confirmed': 'Great news! Your appointment has been confirmed.',
            'check_in': 'Please proceed to check in for your appointment.',
            'in_progress': 'Your appointment is now in progress.',
            'completed': 'Your appointment has been completed.',
            'cancelled': 'Your appointment has been cancelled.',
            'no_show': 'You were marked as no-show for this appointment.'
        };
        return messages[status] || 'Your appointment status has been updated.';
    }

    getStatusIcon(status) {
        const icons = {
            'pending': 'clock',
            'confirmed': 'check-circle',
            'check_in': 'user-check',
            'in_progress': 'stethoscope',
            'completed': 'check-double',
            'cancelled': 'times-circle',
            'no_show': 'user-times'
        };
        return icons[status] || 'calendar';
    }

    getStatusColor(status) {
        const colors = {
            'pending': '#ffc107',
            'confirmed': '#28a745',
            'check_in': '#007bff',
            'in_progress': '#17a2b8',
            'completed': '#28a745',
            'cancelled': '#dc3545',
            'no_show': '#6c757d'
        };
        return colors[status] || '#6c757d';
    }

    getStatusSoundClass(status) {
        const soundClasses = {
            'confirmed': 'success',
            'cancelled': 'error',
            'completed': 'success',
            'no_show': 'warning',
            'check_in': 'info',
            'in_progress': 'warning',
            'pending': 'info'
        };
        return soundClasses[status] || 'general';
    }

    /**
     * Get current status
     */
    getCurrentStatus() {
        return this.currentStatus;
    }

    /**
     * Get status history
     */
    getStatusHistory() {
        return this.statusHistory;
    }

    /**
     * Check if initialized
     */
    isReady() {
        return this.isInitialized;
    }

    /**
     * Get last update time
     */
    getLastUpdate() {
        return this.lastUpdate;
    }

    /**
     * Clean up resources
     */
    destroy() {
        if (this.realtimeClient) {
            this.realtimeClient.unsubscribeFromAll();
            this.realtimeClient = null;
        }

        this.isInitialized = false;
        this.statusHistory = [];
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PatientStatusDisplay;
}

// Make available globally
window.PatientStatusDisplay = PatientStatusDisplay;
