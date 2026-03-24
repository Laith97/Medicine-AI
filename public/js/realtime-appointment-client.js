/**
 * Real-time Appointment Tracking Client
 *
 * Comprehensive JavaScript module for handling Pusher/WebSocket connections
 * for real-time appointment tracking and status updates.
 *
 * Features:
 * - Pusher connection management with auto-reconnection
 * - Real-time event listeners for appointment status changes
 * - Queue position tracking with visual indicators
 * - Multi-device synchronization support
 * - Sound and visual notifications
 * - Error handling and fallback mechanisms
 * - Subscription management
 * - Performance monitoring
 */

class RealtimeAppointmentClient {
    constructor(config = {}) {
        this.config = {
            // Default configuration
            pusherKey: config.pusherKey || window.PUSHER_KEY || '',
            cluster: config.cluster || window.PUSHER_CLUSTER || 'mt1',
            forceTLS: true,
            enableStats: true,
            enableReconnect: true,
            maxRetries: 5,
            reconnectInterval: 5000,
            heartbeatInterval: 30000,
            enableNotifications: true,
            enableSounds: true,
            debugMode: config.debugMode || false,
            ...config
        };

        this.pusher = null;
        this.channels = new Map();
        this.subscriptions = new Map();
        this.eventListeners = new Map();
        this.retryCount = 0;
        this.connectionState = 'disconnected';
        this.isInitialized = false;
        this.userId = null;
        this.userRole = null;
        this.appointmentCache = new Map();
        this.lastActivity = null;

        // Event types for better organization
        this.EVENTS = {
            APPOINTMENT_STATUS_CHANGED: 'appointment.status-changed',
            APPOINTMENT_CREATED: 'appointment.created',
            APPOINTMENT_UPDATED: 'appointment.updated',
            APPOINTMENT_DELETED: 'appointment.deleted',
            APPOINTMENT_LIST_UPDATED: 'appointments.updated',
            QUEUE_POSITION_UPDATED: 'queue.position-updated',
            NOTIFICATION_RECEIVED: 'notification.received'
        };

        this.log('RealtimeAppointmentClient initialized', this.config);
    }

    /**
     * Initialize the real-time client
     */
    async initialize(userId = null, userRole = null) {
        try {
            this.userId = userId || this.getUserIdFromContext();
            this.userRole = userRole || this.getUserRoleFromContext();

            if (!this.config.pusherKey) {
                throw new Error('Pusher key is required');
            }

            this.log('Initializing real-time client for user:', {
                userId: this.userId,
                role: this.userRole
            });

            await this.initializePusher();
            await this.setupEventListeners();
            await this.subscribeToRelevantChannels();
            await this.subscribeToUserChannels();

            this.isInitialized = true;
            this.updateConnectionState('connected');
            this.startHeartbeat();

            this.log('Real-time client initialized successfully');
            return true;

        } catch (error) {
            this.log('Failed to initialize real-time client:', error);
            this.updateConnectionState('error');
            throw error;
        }
    }

    /**
     * Initialize Pusher instance
     */
    async initializePusher() {
        try {
            if (typeof Pusher === 'undefined') {
                throw new Error('Pusher.js library is not loaded');
            }

            this.pusher = new Pusher(this.config.pusherKey, {
                cluster: this.config.cluster,
                forceTLS: this.config.forceTLS,
                enableStats: this.config.enableStats,
                enabledTransports: ['ws', 'wss'],
                disabledTransports: ['sockjs'],
                wsHost: this.config.pusherHost || undefined,
                wsPort: this.config.pusherPort || 443,
                wsPath: this.config.pusherPath || undefined,
                wssHost: this.config.pusherHost || undefined,
                wssPort: this.config.pusherPort || 443,
                wssPath: this.config.pusherPath || undefined
            });

            this.setupPusherEventHandlers();
            this.log('Pusher instance created successfully');

        } catch (error) {
            this.log('Error initializing Pusher:', error);
            throw error;
        }
    }

    /**
     * Setup Pusher event handlers
     */
    setupPusherEventHandlers() {
        // Connection state changes
        this.pusher.connection.bind('connected', () => {
            this.log('Pusher connected');
            this.updateConnectionState('connected');
            this.retryCount = 0;
        });

        this.pusher.connection.bind('disconnected', () => {
            this.log('Pusher disconnected');
            this.updateConnectionState('disconnected');
        });

        this.pusher.connection.bind('connecting', () => {
            this.log('Pusher connecting');
            this.updateConnectionState('connecting');
        });

        this.pusher.connection.bind('unavailable', () => {
            this.log('Pusher unavailable');
            this.updateConnectionState('unavailable');
        });

        this.pusher.connection.bind('failed', () => {
            this.log('Pusher connection failed');
            this.updateConnectionState('failed');
            this.handleConnectionFailure();
        });

        this.pusher.connection.bind('error', (error) => {
            this.log('Pusher connection error:', error);
            this.updateConnectionState('error');
            this.handleConnectionError(error);
        });

        // Global error handler
        this.pusher.connection.bind('message', (message) => {
            this.log('Pusher message received:', message);
        });
    }

    /**
     * Setup event listeners for appointment events
     */
    async setupEventListeners() {
        // Listen for appointment status changes
        this.addEventListener(this.EVENTS.APPOINTMENT_STATUS_CHANGED, (event) => {
            this.log('Appointment status changed:', event);
            this.handleAppointmentStatusChange(event);
        });

        // Listen for appointment creation
        this.addEventListener(this.EVENTS.APPOINTMENT_CREATED, (event) => {
            this.log('New appointment created:', event);
            this.handleAppointmentCreated(event);
        });

        // Listen for appointment updates
        this.addEventListener(this.EVENTS.APPOINTMENT_UPDATED, (event) => {
            this.log('Appointment updated:', event);
            this.handleAppointmentUpdated(event);
        });

        // Listen for appointment deletion
        this.addEventListener(this.EVENTS.APPOINTMENT_DELETED, (event) => {
            this.log('Appointment deleted:', event);
            this.handleAppointmentDeleted(event);
        });

        // Listen for queue position updates
        this.addEventListener(this.EVENTS.QUEUE_POSITION_UPDATED, (event) => {
            this.log('Queue position updated:', event);
            this.handleQueuePositionUpdate(event);
        });

        // Listen for notifications
        this.addEventListener(this.EVENTS.NOTIFICATION_RECEIVED, (event) => {
            this.log('Notification received:', event);
            this.handleNotificationReceived(event);
        });
    }

    /**
     * Subscribe to appointment-specific channels
     */
    async subscribeToAppointmentChannels() {
        const channels = [];

        // Public appointment channels
        if (this.userRole === 'doctor' && window.currentDoctorId) {
            channels.push(`doctor.${window.currentDoctorId}`);
        }

        // Date-specific appointment channels
        const today = new Date().toISOString().split('T')[0];
        channels.push(`appointments.${today}`);

        // Admin channels for all staff
        if (['admin', 'hospital_admin', 'manager'].includes(this.userRole)) {
            channels.push('admin.appointments');
            channels.push('admin');
        }

        // Subscribe to each channel
        for (const channelName of channels) {
            await this.subscribeToChannel(channelName);
        }

        this.log('Subscribed to appointment channels:', channels);
    }

    /**
     * Subscribe to user-specific channels
     */
    async subscribeToUserChannels() {
        if (!this.userId) return;

        const userChannels = [
            `user.${this.userId}`,
            `App.User.${this.userId}`,
            'clinic-staff' // General clinic staff channel
        ];

        // Role-specific channels
        if (this.userRole === 'doctor' && window.currentDoctorId) {
            userChannels.push(`doctor.${window.currentDoctorId}`);
        }

        for (const channelName of userChannels) {
            await this.subscribeToChannel(channelName);
        }

        this.log('Subscribed to user channels:', userChannels);
    }

    /**
     * Subscribe to a specific channel
     */
    async subscribeToChannel(channelName) {
        try {
            if (this.channels.has(channelName)) {
                this.log('Already subscribed to channel:', channelName);
                return;
            }

            const channel = this.pusher.subscribe(channelName);

            // Setup channel event handlers
            this.setupChannelEventHandlers(channel, channelName);

            this.channels.set(channelName, channel);
            this.subscriptions.set(channelName, {
                subscribedAt: new Date(),
                isActive: true
            });

            this.log('Subscribed to channel:', channelName);

        } catch (error) {
            this.log(`Failed to subscribe to channel ${channelName}:`, error);
            throw error;
        }
    }

    /**
     * Setup event handlers for a channel
     */
    setupChannelEventHandlers(channel, channelName) {
        // Bind all appointment-related events
        Object.values(this.EVENTS).forEach(eventType => {
            channel.bind(eventType, (event) => {
                this.handleChannelEvent(channelName, eventType, event);
            });
        });

        // Handle channel-specific events
        channel.bind('pusher:subscription_succeeded', () => {
            this.log(`Successfully subscribed to channel: ${channelName}`);
        });

        channel.bind('pusher:subscription_error', (status) => {
            this.log(`Subscription error for channel ${channelName}:`, status);
        });

        channel.bind('pusher:member_added', (member) => {
            this.log(`Member added to channel ${channelName}:`, member);
        });

        channel.bind('pusher:member_removed', (member) => {
            this.log(`Member removed from channel ${channelName}:`, member);
        });
    }

    /**
     * Handle events from subscribed channels
     */
    handleChannelEvent(channelName, eventType, eventData) {
        this.log(`Event received from ${channelName}:`, eventType, eventData);

        // Update last activity timestamp
        this.lastActivity = new Date();

        // Trigger registered listeners
        this.triggerEventListeners(eventType, eventData);

        // Cache appointment data for quick access
        if (eventData.data && eventData.data.appointment_id) {
            this.appointmentCache.set(eventData.data.appointment_id, eventData);
        }

        // Send acknowledgment if required
        this.sendAcknowledgment(channelName, eventType, eventData);
    }

    /**
     * Handle appointment status change events
     */
    handleAppointmentStatusChange(event) {
        const { data } = event;
        if (!data) return;

        // Update appointment status in UI
        this.updateAppointmentStatusUI(data.appointment_id, data.new_status, data);

        // Show notification if enabled
        if (this.config.enableNotifications) {
            this.showStatusChangeNotification(data);
        }

        // Play sound if enabled
        if (this.config.enableSounds) {
            this.playStatusChangeSound(data.new_status);
        }

        // Trigger custom callbacks
        this.triggerCallback('onAppointmentStatusChanged', data);

        // Update queue position if applicable
        if (data.queue_position !== undefined) {
            this.updateQueuePositionUI(data.appointment_id, data.queue_position);
        }
    }

    /**
     * Handle appointment creation events
     */
    handleAppointmentCreated(event) {
        const { data } = event;
        if (!data) return;

        // Add new appointment to UI
        this.addNewAppointmentToUI(data.appointment);

        // Show notification
        if (this.config.enableNotifications) {
            this.showAppointmentCreatedNotification(data);
        }

        // Play sound
        if (this.config.enableSounds) {
            this.playNotificationSound('appointment_created');
        }

        // Trigger custom callbacks
        this.triggerCallback('onAppointmentCreated', data);
    }

    /**
     * Handle appointment update events
     */
    handleAppointmentUpdated(event) {
        const { data } = event;
        if (!data) return;

        // Update appointment data in UI
        this.updateAppointmentDataUI(data.appointment, data.changed_attributes || []);

        // Trigger custom callbacks
        this.triggerCallback('onAppointmentUpdated', data);
    }

    /**
     * Handle appointment deletion events
     */
    handleAppointmentDeleted(event) {
        const { data } = event;
        if (!data) return;

        // Remove appointment from UI
        this.removeAppointmentFromUI(data.appointment_id);

        // Trigger custom callbacks
        this.triggerCallback('onAppointmentDeleted', data);
    }

    /**
     * Handle queue position update events
     */
    handleQueuePositionUpdate(event) {
        const { data } = event;
        if (!data) return;

        // Update queue position UI
        this.updateQueuePositionUI(data.appointment_id, data.position);

        // Show queue notification if position changed significantly
        if (data.position_changed) {
            this.showQueuePositionNotification(data);
        }

        // Trigger custom callbacks
        this.triggerCallback('onQueuePositionUpdated', data);
    }

    /**
     * Handle notification received events
     */
    handleNotificationReceived(event) {
        const { data } = event;
        if (!data) return;

        // Show notification
        this.showNotification(data);

        // Play sound
        if (this.config.enableSounds) {
            this.playNotificationSound(data.type || 'general');
        }

        // Trigger custom callbacks
        this.triggerCallback('onNotificationReceived', data);
    }

    /**
     * Update appointment status in UI
     */
    updateAppointmentStatusUI(appointmentId, newStatus, eventData) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`) ||
                                 document.querySelector(`#appointment-${appointmentId}`) ||
                                 document.querySelector(`.appointment-card[data-id="${appointmentId}"]`);

        if (!appointmentElement) {
            this.log('Appointment element not found for update:', appointmentId);
            return;
        }

        // Update status badge
        const statusBadge = appointmentElement.querySelector('.status-badge') ||
                           appointmentElement.querySelector('.badge') ||
                           appointmentElement.querySelector('[data-status]');

        if (statusBadge) {
            // Remove existing status classes
            statusBadge.className = statusBadge.className.replace(/status-\w+/g, '');
            statusBadge.className = statusBadge.className.replace(/bg-\w+/g, '');

            // Add new status classes
            const statusClass = `status-${newStatus.replace('_', '-')}`;
            const bgClass = this.getStatusBackgroundClass(newStatus);
            statusBadge.classList.add(statusClass, bgClass);

            // Update text
            const statusText = newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            statusBadge.textContent = statusText;
        }

        // Update data attribute
        appointmentElement.setAttribute('data-status', newStatus);

        // Update icons if present
        const statusIcon = appointmentElement.querySelector('.status-icon') ||
                          appointmentElement.querySelector('[data-status-icon]');
        if (statusIcon) {
            const iconClass = this.getStatusIconClass(newStatus);
            statusIcon.className = statusIcon.className.replace(/fa-\w+/g, `fa-${iconClass}`);
        }

        // Add visual feedback
        this.addStatusChangeAnimation(appointmentElement, newStatus);

        // Update estimated wait time if available
        if (eventData.estimated_wait_minutes !== undefined) {
            this.updateEstimatedWaitTimeUI(appointmentId, eventData.estimated_wait_minutes);
        }
    }

    /**
     * Add visual animation for status change
     */
    addStatusChangeAnimation(element, newStatus) {
        // Remove existing animation classes
        element.classList.remove('status-changing', 'status-updating');

        // Add animation class
        element.classList.add('status-changing');

        // Add status-specific class
        element.classList.add(`status-${newStatus.replace('_', '-')}-changed`);

        // Trigger CSS animation
        setTimeout(() => {
            element.classList.remove('status-changing');
        }, 500);

        // Add pulse effect for important status changes
        if (['confirmed', 'completed', 'cancelled'].includes(newStatus)) {
            element.classList.add('pulse-animation');
            setTimeout(() => {
                element.classList.remove('pulse-animation');
            }, 1000);
        }
    }

    /**
     * Show status change notification
     */
    showStatusChangeNotification(data) {
        const notification = {
            type: 'appointment_status',
            title: data.title || 'Appointment Status Updated',
            message: data.message || 'Appointment status has been updated',
            icon: this.getStatusIconClass(data.new_status),
            iconColor: this.getStatusColor(data.new_status),
            appointmentId: data.appointment_id,
            actionUrl: data.link || `/appointments/${data.appointment_id}`,
            actionText: 'View Details',
            duration: 5000,
            sound: this.getStatusSoundClass(data.new_status)
        };

        this.displayNotification(notification);
    }

    /**
     * Display notification with toast or modal
     */
    displayNotification(notification) {
        // Try to use existing toast system
        if (window.Toast && typeof window.Toast.success === 'function') {
            window.Toast.success(notification.message, notification.title);
            return;
        }

        // Fallback to custom notification
        this.showCustomNotification(notification);
    }

    /**
     * Show custom notification
     */
    showCustomNotification(notification) {
        const notificationElement = document.createElement('div');
        notificationElement.className = `realtime-notification notification-${notification.type}`;
        notificationElement.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-${notification.icon}" style="color: ${notification.iconColor}"></i>
                </div>
                <div class="notification-body">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                </div>
                <div class="notification-actions">
                    ${notification.actionUrl ? `
                        <a href="${notification.actionUrl}" class="notification-action">
                            ${notification.actionText}
                        </a>
                    ` : ''}
                    <button class="notification-close">&times;</button>
                </div>
            </div>
        `;

        // Add to notification container
        let container = document.getElementById('realtime-notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'realtime-notifications';
            container.className = 'realtime-notifications-container';
            document.body.appendChild(container);
        }

        container.appendChild(notificationElement);

        // Animate in
        setTimeout(() => {
            notificationElement.classList.add('show');
        }, 10);

        // Auto-remove after duration
        setTimeout(() => {
            this.removeNotification(notificationElement);
        }, notification.duration || 5000);

        // Add close handler
        const closeBtn = notificationElement.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            this.removeNotification(notificationElement);
        });
    }

    /**
     * Remove notification
     */
    removeNotification(notificationElement) {
        notificationElement.classList.remove('show');
        setTimeout(() => {
            if (notificationElement.parentNode) {
                notificationElement.parentNode.removeChild(notificationElement);
            }
        }, 300);
    }

    /**
     * Play status change sound
     */
    playStatusChangeSound(status) {
        if (!this.config.enableSounds) return;

        const soundClass = this.getStatusSoundClass(status);
        this.playNotificationSound(soundClass);
    }

    /**
     * Play notification sound
     */
    playNotificationSound(soundType) {
        if (!this.config.enableSounds || !this.config.sounds) return;

        const audio = new Audio(this.config.sounds[soundType]);
        audio.volume = this.config.soundVolume || 0.5;
        audio.play().catch(error => {
            this.log('Failed to play notification sound:', error);
        });
    }

    /**
     * Add new appointment to UI
     */
    addNewAppointmentToUI(appointmentData) {
        const appointmentHtml = this.generateAppointmentCardHtml(appointmentData);

        // Find the appropriate container
        const container = document.querySelector('#appointments-list') ||
                         document.querySelector('.appointments-container') ||
                         document.querySelector('.appointment-list');

        if (container) {
            container.insertAdjacentHTML('afterbegin', appointmentHtml);

            // Animate the new appointment
            const newCard = container.firstElementChild;
            newCard.classList.add('appointment-adding');
            setTimeout(() => {
                newCard.classList.remove('appointment-adding');
            }, 500);
        }
    }

    /**
     * Update appointment data in UI
     */
    updateAppointmentDataUI(appointmentData, changedAttributes = []) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentData.id}"]`) ||
                                 document.querySelector(`#appointment-${appointmentData.id}`);

        if (!appointmentElement) return;

        // Update each changed attribute
        changedAttributes.forEach(attr => {
            switch (attr) {
                case 'appointment_date':
                    const timeElement = appointmentElement.querySelector('.appointment-time');
                    if (timeElement) {
                        const date = new Date(appointmentData.appointment_date);
                        timeElement.textContent = date.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                    break;

                case 'patient_name':
                    const patientElement = appointmentElement.querySelector('.appointment-patient');
                    if (patientElement) {
                        patientElement.textContent = appointmentData.patient?.name || appointmentData.patient_name;
                    }
                    break;

                case 'appointment_type':
                    const typeElement = appointmentElement.querySelector('.appointment-type');
                    if (typeElement) {
                        typeElement.textContent = appointmentData.appointment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                    break;

                case 'reason':
                    const reasonElement = appointmentElement.querySelector('.appointment-reason');
                    if (reasonElement) {
                        reasonElement.textContent = appointmentData.reason;
                    }
                    break;
            }
        });
    }

    /**
     * Remove appointment from UI
     */
    removeAppointmentFromUI(appointmentId) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`) ||
                                 document.querySelector(`#appointment-${appointmentId}`);

        if (!appointmentElement) return;

        // Add removing animation
        appointmentElement.classList.add('appointment-removing');

        // Remove after animation
        setTimeout(() => {
            if (appointmentElement.parentNode) {
                appointmentElement.parentNode.removeChild(appointmentElement);
            }
        }, 300);
    }

    /**
     * Update queue position UI
     */
    updateQueuePositionUI(appointmentId, newPosition) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!appointmentElement) return;

        // Update queue position display
        const positionElement = appointmentElement.querySelector('.queue-position') ||
                               appointmentElement.querySelector('.appointment-position');

        if (positionElement) {
            positionElement.textContent = `#${newPosition}`;
            positionElement.setAttribute('data-position', newPosition);
        }

        // Add position change animation
        appointmentElement.classList.add('position-changing');
        setTimeout(() => {
            appointmentElement.classList.remove('position-changing');
        }, 1000);
    }

    /**
     * Update estimated wait time UI
     */
    updateEstimatedWaitTimeUI(appointmentId, waitTimeMinutes) {
        const appointmentElement = document.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!appointmentElement) return;

        const waitTimeElement = appointmentElement.querySelector('.estimated-wait') ||
                               appointmentElement.querySelector('.wait-time');

        if (waitTimeElement) {
            const hours = Math.floor(waitTimeMinutes / 60);
            const minutes = waitTimeMinutes % 60;

            let timeText = '';
            if (hours > 0) {
                timeText = `${hours}h ${minutes}m`;
            } else {
                timeText = `${minutes}m`;
            }

            waitTimeElement.textContent = timeText;
            waitTimeElement.setAttribute('data-wait-time', waitTimeMinutes);
        }
    }

    /**
     * Generate appointment card HTML
     */
    generateAppointmentCardHtml(appointmentData) {
        const status = appointmentData.status || 'pending';
        const appointmentDate = new Date(appointmentData.appointment_date);

        return `
            <div class="appointment-card" data-appointment-id="${appointmentData.id}" data-status="${status}">
                <div class="realtime-indicator online"></div>
                <div class="appointment-header">
                    <div class="appointment-time">${appointmentDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    <div class="appointment-patient">${appointmentData.patient?.name || appointmentData.patient_name || 'Unknown Patient'}</div>
                </div>
                <div class="appointment-details">
                    <span class="badge status-${status.replace('_', '-')} bg-${this.getStatusBackgroundClass(status)}">
                        ${status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                    ${appointmentData.queue_position ? `
                        <div class="queue-position">#${appointmentData.queue_position}</div>
                    ` : ''}
                    ${appointmentData.estimated_wait_minutes ? `
                        <div class="estimated-wait">${this.formatWaitTime(appointmentData.estimated_wait_minutes)}</div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Format wait time for display
     */
    formatWaitTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;

        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    }

    /**
     * Get status background class
     */
    getStatusBackgroundClass(status) {
        const statusClasses = {
            'pending': 'warning',
            'confirmed': 'success',
            'cancelled': 'danger',
            'completed': 'info',
            'no_show': 'secondary',
            'check_in': 'primary',
            'in_progress': 'warning',
            'waiting': 'light',
            'ready': 'success'
        };
        return statusClasses[status] || 'secondary';
    }

    /**
     * Get status icon class
     */
    getStatusIconClass(status) {
        const iconClasses = {
            'pending': 'clock',
            'confirmed': 'check-circle',
            'cancelled': 'times-circle',
            'completed': 'check-double',
            'no_show': 'user-times',
            'check_in': 'user-check',
            'in_progress': 'spinner',
            'waiting': 'hourglass-half',
            'ready': 'hand-paper'
        };
        return iconClasses[status] || 'calendar';
    }

    /**
     * Get status color
     */
    getStatusColor(status) {
        const colors = {
            'pending': '#ffc107',
            'confirmed': '#28a745',
            'cancelled': '#dc3545',
            'completed': '#17a2b8',
            'no_show': '#6c757d',
            'check_in': '#007bff',
            'in_progress': '#ffc107',
            'waiting': '#17a2b8',
            'ready': '#28a745'
        };
        return colors[status] || '#6c757d';
    }

    /**
     * Get status sound class
     */
    getStatusSoundClass(status) {
        const soundClasses = {
            'confirmed': 'success',
            'cancelled': 'error',
            'completed': 'success',
            'no_show': 'warning',
            'check_in': 'info',
            'in_progress': 'warning',
            'waiting': 'info',
            'ready': 'alert'
        };
        return soundClasses[status] || 'general';
    }

    /**
     * Add event listener
     */
    addEventListener(eventType, callback) {
        if (!this.eventListeners.has(eventType)) {
            this.eventListeners.set(eventType, new Set());
        }
        this.eventListeners.get(eventType).add(callback);
    }

    /**
     * Remove event listener
     */
    removeEventListener(eventType, callback) {
        if (this.eventListeners.has(eventType)) {
            this.eventListeners.get(eventType).delete(callback);
        }
    }

    /**
     * Trigger event listeners
     */
    triggerEventListeners(eventType, data) {
        if (this.eventListeners.has(eventType)) {
            this.eventListeners.get(eventType).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    this.log('Error in event listener:', error);
                }
            });
        }
    }

    /**
     * Trigger custom callback
     */
    triggerCallback(callbackName, data) {
        if (typeof window[callbackName] === 'function') {
            try {
                window[callbackName](data);
            } catch (error) {
                this.log(`Error in callback ${callbackName}:`, error);
            }
        }
    }

    /**
     * Send acknowledgment for received events
     */
    sendAcknowledgment(channelName, eventType, eventData) {
        // Implementation depends on backend requirements
        // This could send a POST request to acknowledge receipt
        if (this.config.acknowledgmentEndpoint) {
            fetch(this.config.acknowledgmentEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    channel: channelName,
                    event_type: eventType,
                    event_id: eventData.event_id,
                    acknowledged_at: new Date().toISOString()
                })
            }).catch(error => {
                this.log('Failed to send acknowledgment:', error);
            });
        }
    }

    /**
     * Handle connection failure
     */
    handleConnectionFailure() {
        if (this.retryCount >= this.config.maxRetries) {
            this.log('Max retries reached, giving up reconnection attempts');
            this.updateConnectionState('failed');
            return;
        }

        this.retryCount++;
        this.updateConnectionState('reconnecting');

        const retryDelay = this.config.reconnectInterval * Math.pow(2, this.retryCount - 1);
        this.log(`Retrying connection in ${retryDelay}ms (attempt ${this.retryCount})`);

        setTimeout(() => {
            try {
                this.pusher.connect();
            } catch (error) {
                this.log('Reconnection failed:', error);
                this.handleConnectionFailure();
            }
        }, retryDelay);
    }

    /**
     * Handle connection error
     */
    handleConnectionError(error) {
        this.log('Connection error occurred:', error);

        // Could implement circuit breaker pattern here
        // For now, just log the error
    }

    /**
     * Start heartbeat to keep connection alive
     */
    startHeartbeat() {
        if (!this.config.enableStats) return;

        this.heartbeatInterval = setInterval(() => {
            if (this.connectionState === 'connected') {
                // Send heartbeat or check connection health
                this.log('Heartbeat check');
                this.updateActivityTimestamp();
            }
        }, this.config.heartbeatInterval);
    }

    /**
     * Stop heartbeat
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    /**
     * Update activity timestamp
     */
    updateActivityTimestamp() {
        this.lastActivity = new Date();

        // Send activity update to server
        if (this.config.activityEndpoint && this.userId) {
            fetch(this.config.activityEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    user_id: this.userId,
                    last_activity: this.lastActivity.toISOString(),
                    page_url: window.location.href
                })
            }).catch(error => {
                this.log('Failed to update activity:', error);
            });
        }
    }

    /**
     * Update connection state
     */
    updateConnectionState(newState) {
        const previousState = this.connectionState;
        this.connectionState = newState;

        // Update UI indicators
        this.updateConnectionStateUI(newState, previousState);

        // Trigger state change listeners
        this.triggerEventListeners('connectionStateChanged', {
            previousState,
            newState,
            timestamp: new Date()
        });

        this.log(`Connection state changed: ${previousState} -> ${newState}`);
    }

    /**
     * Update connection state UI indicators
     */
    updateConnectionStateUI(newState, previousState) {
        const indicator = document.querySelector('.realtime-connection-indicator');
        if (indicator) {
            // Remove existing state classes
            indicator.className = indicator.className.replace(/connection-\w+/g, '');

            // Add new state class
            indicator.classList.add(`connection-${newState}`);

            // Update text
            const statusText = indicator.querySelector('.connection-status-text');
            if (statusText) {
                statusText.textContent = this.getConnectionStateText(newState);
            }

            // Update icon
            const icon = indicator.querySelector('.connection-status-icon i');
            if (icon) {
                icon.className = `fas fa-${this.getConnectionStateIcon(newState)}`;
            }
        }

        // Dispatch global event for other components
        window.dispatchEvent(new CustomEvent('realtimeConnectionStateChanged', {
            detail: { newState, previousState }
        }));
    }

    /**
     * Get connection state text
     */
    getConnectionStateText(state) {
        const stateTexts = {
            'connected': 'Connected',
            'connecting': 'Connecting...',
            'disconnected': 'Disconnected',
            'reconnecting': 'Reconnecting...',
            'failed': 'Connection Failed',
            'unavailable': 'Service Unavailable',
            'error': 'Error'
        };
        return stateTexts[state] || 'Unknown';
    }

    /**
     * Get connection state icon
     */
    getConnectionStateIcon(state) {
        const stateIcons = {
            'connected': 'wifi',
            'connecting': 'spinner fa-spin',
            'disconnected': 'wifi-slash',
            'reconnecting': 'redo fa-spin',
            'failed': 'exclamation-triangle',
            'unavailable': 'ban',
            'error': 'exclamation-circle'
        };
        return stateIcons[state] || 'question';
    }

    /**
     * Get user ID from context
     */
    getUserIdFromContext() {
        return window.currentUserId ||
               document.querySelector('meta[name="user-id"]')?.content ||
               null;
    }

    /**
     * Get user role from context
     */
    getUserRoleFromContext() {
        return window.currentUserRole ||
               document.querySelector('meta[name="user-role"]')?.content ||
               null;
    }

    /**
     * Subscribe to relevant channels based on user context
     */
    async subscribeToRelevantChannels() {
        await this.subscribeToAppointmentChannels();
    }

    /**
     * Unsubscribe from all channels
     */
    async unsubscribeFromAll() {
        this.log('Unsubscribing from all channels');

        // Stop heartbeat
        this.stopHeartbeat();

        // Unsubscribe from all channels
        for (const [channelName, channel] of this.channels) {
            try {
                this.pusher.unsubscribe(channelName);
            } catch (error) {
                this.log(`Error unsubscribing from ${channelName}:`, error);
            }
        }

        // Clear maps
        this.channels.clear();
        this.subscriptions.clear();

        // Disconnect Pusher
        if (this.pusher) {
            this.pusher.disconnect();
            this.pusher = null;
        }

        this.updateConnectionState('disconnected');
        this.isInitialized = false;
    }

    /**
     * Get connection status
     */
    getConnectionStatus() {
        return {
            state: this.connectionState,
            isInitialized: this.isInitialized,
            channels: Array.from(this.channels.keys()),
            subscriptions: Array.from(this.subscriptions.keys()),
            lastActivity: this.lastActivity,
            retryCount: this.retryCount,
            userId: this.userId,
            userRole: this.userRole
        };
    }

    /**
     * Get cached appointment data
     */
    getCachedAppointment(appointmentId) {
        return this.appointmentCache.get(appointmentId);
    }

    /**
     * Clear appointment cache
     */
    clearAppointmentCache() {
        this.appointmentCache.clear();
    }

    /**
     * Debug logging
     */
    log(message, ...args) {
        if (this.config.debugMode || window.DEBUG_REALTIME) {
            // console.log(`[RealtimeAppointmentClient] ${message}`, ...args);
        }
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealtimeAppointmentClient;
}

// Make available globally
window.RealtimeAppointmentClient = RealtimeAppointmentClient;
