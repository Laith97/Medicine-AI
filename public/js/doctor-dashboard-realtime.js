/**
 * Doctor Dashboard Real-time Manager
 *
 * Manages real-time updates for doctor dashboard including
 * stats, appointments, notifications, and performance metrics.
 */

class DoctorDashboardRealtime {
    constructor(options = {}) {
        this.options = {
            doctorId: options.doctorId,
            userId: options.userId,
            userRole: options.userRole || 'doctor',
            container: options.container || document.querySelector('.doctor-dashboard-realtime'),
            appointmentApi: options.appointmentApi || '/api/appointments',
            statsApi: options.statsApi || '/api/doctor/stats',
            csrfToken: options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content,
            pusherKey: options.pusherKey || window.PUSHER_KEY,
            cluster: options.cluster || window.PUSHER_CLUSTER,
            debugMode: options.debugMode || false,
            ...options
        };

        this.realtimeClient = null;
        this.currentStats = {};
        this.appointments = new Map();
        this.notifications = [];
        this.refreshInterval = null;
        this.isInitialized = false;
        this.lastUpdate = null;

        // Notification types for categorization
        this.NOTIFICATION_TYPES = {
            APPOINTMENT_STATUS: 'appointment_status',
            NEW_APPOINTMENT: 'new_appointment',
            QUEUE_UPDATE: 'queue_update',
            SYSTEM_ALERT: 'system_alert',
            PERFORMANCE: 'performance'
        };

        this.init();
    }

    /**
     * Initialize the doctor dashboard real-time manager
     */
    async init() {
        if (!this.options.container) {
            // console.error('DoctorDashboardRealtime: Container not found');
            return;
        }

        try {
            await this.initializeRealtimeClient();
            this.setupEventListeners();
            this.setupPeriodicRefresh();
            this.loadInitialData();

            this.isInitialized = true;
            // console.log('DoctorDashboardRealtime initialized successfully');
        } catch (error) {
            // console.error('Failed to initialize DoctorDashboardRealtime:', error);
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

        // Set up event listeners
        this.realtimeClient.addEventListener('appointment.status-changed', (event) => {
            this.handleAppointmentStatusChange(event);
        });

        this.realtimeClient.addEventListener('appointment.created', (event) => {
            this.handleNewAppointment(event);
        });

        this.realtimeClient.addEventListener('appointment.updated', (event) => {
            this.handleAppointmentUpdate(event);
        });

        this.realtimeClient.addEventListener('appointment.deleted', (event) => {
            this.handleAppointmentDeletion(event);
        });

        this.realtimeClient.addEventListener('queue.position-updated', (event) => {
            this.handleQueuePositionUpdate(event);
        });

        this.realtimeClient.addEventListener('notification.received', (event) => {
            this.handleNotificationReceived(event);
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

        // Refresh button
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshDashboard();
            });
        }

        // Clear notifications button
        const clearBtn = document.getElementById('clear-notifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearNotifications();
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            this.handleKeyboardShortcuts(event);
        });
    }

    /**
     * Set up periodic refresh for stats and performance metrics
     */
    setupPeriodicRefresh() {
        // Refresh stats every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.updateStats();
        }, 30000);
    }

    /**
     * Load initial dashboard data
     */
    async loadInitialData() {
        await Promise.all([
            this.loadAppointments(),
            this.updateStats(),
            this.loadPerformanceMetrics()
        ]);

        this.lastUpdate = new Date();
        this.updateLastUpdateTime();
    }

    /**
     * Load appointments data
     */
    async loadAppointments() {
        try {
            const response = await fetch(this.options.appointmentApi);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                this.updateAppointmentsList(result.data);
            }

        } catch (error) {
            // console.error('Error loading appointments:', error);
        }
    }

    /**
     * Update statistics
     */
    async updateStats() {
        try {
            const response = await fetch(this.options.statsApi);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                this.updateStatsDisplay(result.data);
            }

        } catch (error) {
            // console.error('Error updating stats:', error);
        }
    }

    /**
     * Load performance metrics
     */
    async loadPerformanceMetrics() {
        // Simulate loading performance metrics
        // In a real implementation, this would fetch from an API
        const metrics = {
            completed: this.getCompletedCount(),
            onTimeRate: this.calculateOnTimeRate(),
            avgDuration: this.calculateAverageDuration(),
            queueEfficiency: this.calculateQueueEfficiency()
        };

        this.updatePerformanceMetrics(metrics);
    }

    /**
     * Update appointments list display
     */
    updateAppointmentsList(appointments) {
        const container = document.getElementById('today-appointments-list');
        if (!container) return;

        this.appointments.clear();

        const appointmentsHtml = appointments.map(appointment => {
            this.appointments.set(appointment.id, appointment);
            return this.generateAppointmentHtml(appointment);
        }).join('');

        container.innerHTML = appointmentsHtml;

        // Update badges
        this.updateBadges(appointments);
    }

    /**
     * Update statistics display
     */
    updateStatsDisplay(newStats) {
        const statsToUpdate = ['today_appointments', 'pending_appointments', 'queue_position', 'avg_wait_time'];

        statsToUpdate.forEach(stat => {
            const element = document.querySelector(`[data-stat="${stat}"]`);
            if (!element) return;

            const currentValue = this.currentStats[stat] || 0;
            const newValue = newStats[stat] || 0;

            if (currentValue !== newValue) {
                // Add update animation
                const card = element.closest('.stats-card');
                if (card) {
                    card.classList.add('realtime-updating');
                    setTimeout(() => {
                        card.classList.remove('realtime-updating');
                    }, 1000);
                }

                // Update value
                element.textContent = this.formatStatValue(stat, newValue);

                // Show change indicator
                this.showStatChange(stat, currentValue, newValue);

                this.currentStats[stat] = newValue;
            }
        });
    }

    /**
     * Show stat change indicator
     */
    showStatChange(stat, oldValue, newValue) {
        const changeElement = document.getElementById(`${stat.replace('_', '-')}-change`);
        if (!changeElement) return;

        const change = newValue - oldValue;
        if (change === 0) return;

        const icon = change > 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        const text = change > 0 ? `+${change}` : `${change}`;

        changeElement.querySelector('i').className = `fas ${icon}`;
        changeElement.querySelector('span').textContent = text;
        changeElement.style.display = 'block';

        // Hide after animation
        setTimeout(() => {
            changeElement.style.display = 'none';
        }, 3000);
    }

    /**
     * Update performance metrics display
     */
    updatePerformanceMetrics(metrics) {
        const metricsContainer = document.querySelector('.performance-metrics');
        if (!metricsContainer) return;

        const metricItems = metricsContainer.querySelectorAll('.metric-item');

        metricItems.forEach(item => {
            const label = item.querySelector('.text-muted')?.textContent.toLowerCase();
            const valueElement = item.querySelector('.fw-medium');

            if (!valueElement) return;

            if (label?.includes('completed:')) {
                valueElement.textContent = metrics.completed;
            } else if (label?.includes('on time rate:')) {
                valueElement.textContent = `${metrics.onTimeRate}%`;
                valueElement.className = `fw-medium ${metrics.onTimeRate >= 90 ? 'text-success' : 'text-warning'}`;
            } else if (label?.includes('avg duration:')) {
                valueElement.textContent = `${metrics.avgDuration}m`;
            } else if (label?.includes('queue efficiency:')) {
                valueElement.textContent = `${metrics.queueEfficiency}%`;
                valueElement.className = `fw-medium ${metrics.queueEfficiency >= 90 ? 'text-success' : 'text-warning'}`;
            }
        });
    }

    /**
     * Update badges throughout the dashboard
     */
    updateBadges(appointments) {
        // Update total appointments badge
        const totalBadge = document.getElementById('total-appointments-badge');
        if (totalBadge) {
            totalBadge.textContent = appointments.length;
        }

        // Update queue badge
        const queueCount = appointments.filter(apt =>
            ['waiting', 'ready', 'check_in'].includes(apt.status)
        ).length;

        const queueBadge = document.getElementById('queue-badge');
        if (queueBadge) {
            queueBadge.textContent = queueCount;
        }

        // Update active patients count
        const activeCount = document.getElementById('active-patients-count');
        if (activeCount) {
            activeCount.textContent = queueCount;
        }
    }

    /**
     * Handle appointment status change
     */
    handleAppointmentStatusChange(event) {
        const { data } = event;
        if (!data) return;

        const appointmentId = data.appointment_id;
        const oldStatus = data.old_status;
        const newStatus = data.new_status;

        // Update appointment in the list
        this.updateAppointmentStatus(appointmentId, newStatus);

        // Update stats
        this.updateStatsAfterStatusChange(oldStatus, newStatus);

        // Show notification
        this.addNotification({
            type: this.NOTIFICATION_TYPES.APPOINTMENT_STATUS,
            title: 'Appointment Status Updated',
            message: `Appointment #${appointmentId} changed from ${oldStatus} to ${newStatus}`,
            appointmentId: appointmentId,
            timestamp: new Date()
        });

        // Update queue status if relevant
        if (['waiting', 'ready', 'check_in'].includes(newStatus) ||
            ['waiting', 'ready', 'check_in'].includes(oldStatus)) {
            this.updateQueueStatus();
        }

        this.lastUpdate = new Date();
        this.updateLastUpdateTime();
    }

    /**
     * Handle new appointment
     */
    handleNewAppointment(event) {
        const { data } = event;
        if (!data?.appointment) return;

        const appointment = data.appointment;

        // Add to appointments map
        this.appointments.set(appointment.id, appointment);

        // Add to UI
        this.addAppointmentToList(appointment);

        // Update stats
        this.updateStatsAfterNewAppointment();

        // Show notification
        this.addNotification({
            type: this.NOTIFICATION_TYPES.NEW_APPOINTMENT,
            title: 'New Appointment',
            message: `New appointment scheduled with ${appointment.patient?.name || appointment.patient_name}`,
            appointmentId: appointment.id,
            timestamp: new Date()
        });

        // Update queue
        this.updateQueueStatus();

        this.lastUpdate = new Date();
        this.updateLastUpdateTime();
    }

    /**
     * Handle appointment update
     */
    handleAppointmentUpdate(event) {
        const { data } = event;
        if (!data) return;

        if (data.estimated_wait_minutes !== undefined) {
            this.updateWaitTime(data.appointment_id, data.estimated_wait_minutes);
        }

        if (data.queue_position !== undefined) {
            this.updateQueuePosition(data.appointment_id, data.queue_position);
        }
    }

    /**
     * Handle appointment deletion
     */
    handleAppointmentDeletion(event) {
        const { data } = event;
        if (!data) return;

        const appointmentId = data.appointment_id;

        // Remove from appointments map
        this.appointments.delete(appointmentId);

        // Remove from UI
        this.removeAppointmentFromList(appointmentId);

        // Update stats
        this.updateStatsAfterDeletion();

        this.lastUpdate = new Date();
        this.updateLastUpdateTime();
    }

    /**
     * Handle queue position update
     */
    handleQueuePositionUpdate(event) {
        const { data } = event;
        if (!data) return;

        this.updateQueuePosition(data.appointment_id, data.position);

        // Show notification if position changed significantly
        if (data.position_changed) {
            this.addNotification({
                type: this.NOTIFICATION_TYPES.QUEUE_UPDATE,
                title: 'Queue Position Updated',
                message: `Position updated to #${data.position}`,
                appointmentId: data.appointment_id,
                timestamp: new Date()
            });
        }
    }

    /**
     * Handle notification received
     */
    handleNotificationReceived(event) {
        const { data } = event;
        if (!data) return;

        this.addNotification({
            type: this.NOTIFICATION_TYPES.SYSTEM_ALERT,
            title: data.title || 'System Notification',
            message: data.message || 'You have a new notification',
            timestamp: new Date()
        });
    }

    /**
     * Add notification to the panel
     */
    addNotification(notification) {
        this.notifications.unshift(notification);

        // Keep only last 20 notifications
        if (this.notifications.length > 20) {
            this.notifications = this.notifications.slice(0, 20);
        }

        this.updateNotificationsPanel();
    }

    /**
     * Update notifications panel
     */
    updateNotificationsPanel() {
        const panel = document.getElementById('realtime-notifications-panel');
        if (!panel) return;

        if (this.notifications.length === 0) {
            panel.innerHTML = `
                <div class="text-muted text-center py-3">
                    <i class="fas fa-bell-slash"></i>
                    <p class="mb-0">No notifications yet</p>
                </div>
            `;
            return;
        }

        const notificationsHtml = this.notifications.map(notification => {
            const timeAgo = this.getTimeAgo(notification.timestamp);
            const icon = this.getNotificationIcon(notification.type);

            return `
                <div class="notification-item unread">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-${icon} me-2 mt-1 text-${this.getNotificationColor(notification.type)}"></i>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">${notification.title}</div>
                            <div class="text-muted small">${notification.message}</div>
                            <div class="notification-time">${timeAgo}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        panel.innerHTML = notificationsHtml;

        // Show live badge
        const liveBadge = document.getElementById('realtime-updates-badge');
        if (liveBadge) {
            liveBadge.style.display = 'inline-block';
            setTimeout(() => {
                liveBadge.style.display = 'none';
            }, 3000);
        }
    }

    /**
     * Clear all notifications
     */
    clearNotifications() {
        this.notifications = [];
        this.updateNotificationsPanel();
    }

    /**
     * Update appointment status in the list
     */
    updateAppointmentStatus(appointmentId, newStatus) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!appointmentElement) return;

        // Update data attribute
        appointmentElement.setAttribute('data-status', newStatus);

        // Update status badge
        const statusBadge = appointmentElement.querySelector('.badge');
        if (statusBadge) {
            // Remove existing status classes
            statusBadge.className = statusBadge.className.replace(/status-\w+/g, '');
            statusBadge.className = statusBadge.className.replace(/bg-\w+/g, '');

            // Add new status classes
            const statusClass = `status-${newStatus.replace('_', '-')}`;
            const bgClass = newStatus === 'confirmed' ? 'bg-success' :
                           newStatus === 'pending' ? 'bg-warning' : 'bg-secondary';
            statusBadge.classList.add(statusClass, bgClass);

            // Update text
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        }

        // Add animation
        appointmentElement.classList.add('updated');
        setTimeout(() => {
            appointmentElement.classList.remove('updated');
        }, 1000);

        // Update internal state
        const appointment = this.appointments.get(appointmentId);
        if (appointment) {
            appointment.status = newStatus;
        }
    }

    /**
     * Add appointment to the list
     */
    addAppointmentToList(appointment) {
        const container = document.getElementById('today-appointments-list');
        if (!container) return;

        // Remove empty state if present
        const emptyState = container.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const appointmentHtml = this.generateAppointmentHtml(appointment);
        container.insertAdjacentHTML('afterbegin', appointmentHtml);
    }

    /**
     * Remove appointment from the list
     */
    removeAppointmentFromList(appointmentId) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (appointmentElement) {
            appointmentElement.remove();
        }
    }

    /**
     * Update wait time display
     */
    updateWaitTime(appointmentId, waitTimeMinutes) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!appointmentElement) return;

        const waitElement = appointmentElement.querySelector('.estimated-wait');
        if (waitElement) {
            const hours = Math.floor(waitTimeMinutes / 60);
            const minutes = waitTimeMinutes % 60;

            let timeText = '';
            if (hours > 0) {
                timeText = `${hours}h ${minutes}m`;
            } else {
                timeText = `${minutes}m`;
            }

            waitElement.textContent = timeText;
            waitElement.setAttribute('data-wait-time', waitTimeMinutes);
        }
    }

    /**
     * Update queue position display
     */
    updateQueuePosition(appointmentId, position) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!appointmentElement) return;

        const queueElement = appointmentElement.querySelector('.fa-list-ol')?.parentElement;
        if (queueElement) {
            queueElement.innerHTML = `<i class="fas fa-list-ol me-1"></i>Position #${position}`;
        }
    }

    /**
     * Update connection status indicator
     */
    updateConnectionStatus(statusDetail) {
        const indicator = document.querySelector('.realtime-connection-indicator');
        if (!indicator) return;

        const statusText = indicator.querySelector('.connection-status-text');
        const statsText = document.getElementById('connection-stats');

        // Remove existing status classes
        indicator.className = indicator.className.replace(/connection-\w+/g, '');

        // Add new status class
        indicator.classList.add(`connection-${statusDetail.newState}`);

        // Update text
        if (statusText) {
            statusText.textContent = this.getConnectionStatusText(statusDetail.newState);
        }

        // Update stats
        if (statsText && statusDetail.newState === 'connected') {
            const channelCount = this.realtimeClient?.channels?.size || 0;
            statsText.textContent = `${channelCount} channels`;
        }
    }

    /**
     * Update last update time
     */
    updateLastUpdateTime() {
        const timeElement = document.getElementById('last-update-time');
        if (timeElement && this.lastUpdate) {
            timeElement.textContent = `Updated ${this.getTimeAgo(this.lastUpdate)}`;
        }
    }

    /**
     * Refresh dashboard
     */
    async refreshDashboard() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;
        }

        try {
            await this.loadInitialData();
            this.showNotification('Dashboard refreshed', 'success');
        } catch (error) {
            // console.error('Error refreshing dashboard:', error);
            this.showNotification('Failed to refresh dashboard', 'error');
        } finally {
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshBtn.disabled = false;
            }
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (this.realtimeClient) {
            this.realtimeClient.displayNotification({
                type: 'dashboard_notification',
                title: 'Dashboard Update',
                message: message,
                icon: type === 'success' ? 'check-circle' :
                      type === 'error' ? 'exclamation-circle' : 'info-circle',
                iconColor: type === 'success' ? '#28a745' :
                          type === 'error' ? '#dc3545' : '#17a2b8',
                duration: 3000
            });
        }
    }

    // Helper methods
    generateAppointmentHtml(appointment) {
        const status = appointment.status || 'pending';
        const appointmentDate = new Date(appointment.appointment_date);

        return `
            <div class="d-flex align-items-center p-3 border rounded mb-3 appointment-card-realtime"
                 data-appointment-id="${appointment.id}"
                 data-status="${status}"
                 data-updated-at="${new Date(appointment.updated_at).getTime()}">

                <div class="realtime-indicator online"></div>

                <div class="me-3" style="min-width: 80px;">
                    <div class="fw-medium">${appointmentDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    <small class="text-muted">${appointment.duration || 30}min</small>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <h6 class="mb-0 me-2">${appointment.patient?.name || appointment.patient_name || 'Unknown Patient'}</h6>
                        <span class="badge status-${status.replace('_', '-')} bg-${status === 'confirmed' ? 'success' : (status === 'pending' ? 'warning' : 'secondary')}">
                            ${status.charAt(0).toUpperCase() + status.slice(1)}
                        </span>
                    </div>
                    <p class="text-muted small mb-1">${(appointment.reason || '').substring(0, 60)}</p>
                    <div class="text-muted small">
                        <i class="fas fa-${appointment.appointment_type === 'video_call' ? 'video' : (appointment.appointment_type === 'phone_call' ? 'phone' : 'hospital')} me-1"></i>
                        ${appointment.appointment_type ? appointment.appointment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'General'}
                        ${appointment.queue_position ? `<span class="ms-2"><i class="fas fa-list-ol me-1"></i>Position #${appointment.queue_position}</span>` : ''}
                    </div>
                </div>

                ${appointment.estimated_wait_minutes ? `
                    <div class="me-3 text-center">
                        <div class="estimated-wait" data-wait-time="${appointment.estimated_wait_minutes}">
                            ${appointment.estimated_wait_minutes >= 60 ?
                                `${Math.floor(appointment.estimated_wait_minutes / 60)}h ${appointment.estimated_wait_minutes % 60}m` :
                                `${appointment.estimated_wait_minutes}m`
                            }
                        </div>
                        <small class="text-muted">Est. Wait</small>
                    </div>
                ` : ''}

                <div>
                    <a href="/doctor/appointments/${appointment.id}" class="btn btn-sm btn-primary-custom">
                        View Details
                    </a>
                </div>
            </div>
        `;
    }

    formatStatValue(stat, value) {
        switch (stat) {
            case 'avg_wait_time':
                return `${value}m`;
            case 'queue_position':
                return `#${value}`;
            default:
                return value;
        }
    }

    updateStatsAfterStatusChange(oldStatus, newStatus) {
        // Update pending count
        if (oldStatus === 'pending') {
            this.currentStats.pending_appointments = Math.max(0, (this.currentStats.pending_appointments || 0) - 1);
        }
        if (newStatus === 'pending') {
            this.currentStats.pending_appointments = (this.currentStats.pending_appointments || 0) + 1;
        }

        // Update today's appointments count
        if (newStatus === 'completed') {
            this.currentStats.today_appointments = (this.currentStats.today_appointments || 0) + 1;
        }

        this.updateStatsDisplay(this.currentStats);
    }

    updateStatsAfterNewAppointment() {
        this.currentStats.today_appointments = (this.currentStats.today_appointments || 0) + 1;
        this.updateStatsDisplay(this.currentStats);
    }

    updateStatsAfterDeletion() {
        this.currentStats.today_appointments = Math.max(0, (this.currentStats.today_appointments || 0) - 1);
        this.updateStatsDisplay(this.currentStats);
    }

    updateQueueStatus() {
        const queueElements = document.querySelectorAll('#realtime-queue-status .d-flex');
        const queueCount = Array.from(this.appointments.values())
            .filter(apt => ['waiting', 'ready', 'check_in'].includes(apt.status)).length;

        const queueBadge = document.getElementById('queue-badge');
        const activeCount = document.getElementById('active-patients-count');

        if (queueBadge) queueBadge.textContent = queueCount;
        if (activeCount) activeCount.textContent = queueCount;
    }

    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInMinutes = Math.floor((now - time) / 60000);

        if (diffInMinutes < 1) return 'just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        return `${Math.floor(diffInMinutes / 1440)}d ago`;
    }

    getNotificationIcon(type) {
        const icons = {
            [this.NOTIFICATION_TYPES.APPOINTMENT_STATUS]: 'calendar-check',
            [this.NOTIFICATION_TYPES.NEW_APPOINTMENT]: 'plus-circle',
            [this.NOTIFICATION_TYPES.QUEUE_UPDATE]: 'list-ol',
            [this.NOTIFICATION_TYPES.SYSTEM_ALERT]: 'bell',
            [this.NOTIFICATION_TYPES.PERFORMANCE]: 'chart-line'
        };
        return icons[type] || 'info-circle';
    }

    getNotificationColor(type) {
        const colors = {
            [this.NOTIFICATION_TYPES.APPOINTMENT_STATUS]: 'primary',
            [this.NOTIFICATION_TYPES.NEW_APPOINTMENT]: 'success',
            [this.NOTIFICATION_TYPES.QUEUE_UPDATE]: 'info',
            [this.NOTIFICATION_TYPES.SYSTEM_ALERT]: 'warning',
            [this.NOTIFICATION_TYPES.PERFORMANCE]: 'secondary'
        };
        return colors[type] || 'muted';
    }

    getConnectionStatusText(state) {
        const texts = {
            'connected': 'Connected',
            'connecting': 'Connecting...',
            'disconnected': 'Disconnected',
            'reconnecting': 'Reconnecting...',
            'failed': 'Connection Failed'
        };
        return texts[state] || 'Unknown';
    }

    // Performance calculation methods (mock implementations)
    getCompletedCount() {
        return Array.from(this.appointments.values())
            .filter(apt => apt.status === 'completed').length;
    }

    calculateOnTimeRate() {
        // Mock calculation - in real implementation would check actual times
        return Math.floor(Math.random() * 20) + 80;
    }

    calculateAverageDuration() {
        const durations = Array.from(this.appointments.values())
            .map(apt => apt.duration || 30)
            .filter(d => d > 0);
        return durations.length > 0 ? Math.round(durations.reduce((a, b) => a + b, 0) / durations.length) : 30;
    }

    calculateQueueEfficiency() {
        // Mock calculation
        return Math.floor(Math.random() * 15) + 85;
    }

    handleKeyboardShortcuts(event) {
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') return;

        switch (event.key) {
            case 'r':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.refreshDashboard();
                }
                break;
        }
    }

    /**
     * Clean up resources
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }

        if (this.realtimeClient) {
            this.realtimeClient.unsubscribeFromAll();
            this.realtimeClient = null;
        }

        this.appointments.clear();
        this.notifications = [];
        this.isInitialized = false;
    }

    /**
     * Check if initialized
     */
    isReady() {
        return this.isInitialized;
    }

    /**
     * Get current stats
     */
    getCurrentStats() {
        return this.currentStats;
    }

    /**
     * Get appointments count
     */
    getAppointmentsCount() {
        return this.appointments.size;
    }

    /**
     * Get notifications count
     */
    getNotificationsCount() {
        return this.notifications.length;
    }

    /**
     * Get last update time
     */
    getLastUpdate() {
        return this.lastUpdate;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DoctorDashboardRealtime;
}

// Make available globally
window.DoctorDashboardRealtime = DoctorDashboardRealtime;
