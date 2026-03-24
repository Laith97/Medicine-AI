/**
 * Real-time Appointment Queue Manager
 *
 * Manages the appointment queue interface with real-time updates,
 * drag-and-drop functionality, and status management.
 */

class RealtimeAppointmentQueue {
    constructor(options = {}) {
        this.options = {
            container: options.container || document.querySelector('.realtime-appointment-queue'),
            enableDragDrop: options.enableDragDrop !== false,
            showQueuePosition: options.showQueuePosition !== false,
            showEstimatedWait: options.showEstimatedWait !== false,
            userId: options.userId,
            userRole: options.userRole,
            doctorId: options.doctorId,
            appointmentApi: options.appointmentApi || '/api/appointments',
            statusUpdateApi: options.statusUpdateApi || '/api/appointments/status',
            csrfToken: options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content,
            ...options
        };

        this.realtimeClient = null;
        this.sortable = null;
        this.appointments = new Map();
        this.filters = {
            search: '',
            status: 'all',
            priority: 'all'
        };
        this.isInitialized = false;

        this.init();
    }

    /**
     * Initialize the queue
     */
    async init() {
        if (!this.options.container) {
            // console.error('RealtimeAppointmentQueue: Container not found');
            return;
        }

        try {
            await this.initializeRealtimeClient();
            this.setupEventListeners();
            this.setupFilters();
            this.setupDragAndDrop();
            this.setupStatusActions();

            // Load initial appointments
            await this.loadAppointments();

            this.isInitialized = true;
            // console.log('RealtimeAppointmentQueue initialized successfully');
        } catch (error) {
            // console.error('Failed to initialize RealtimeAppointmentQueue:', error);
        }
    }

    /**
     * Initialize the real-time client
     */
    async initializeRealtimeClient() {
        // Initialize the real-time appointment client
        this.realtimeClient = new RealtimeAppointmentClient({
            pusherKey: window.PUSHER_KEY,
            cluster: window.PUSHER_CLUSTER,
            debugMode: window.DEBUG_REALTIME || false,
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

        // Filter changes
        document.addEventListener('input', (event) => {
            if (event.target.matches('[data-filter]')) {
                this.handleFilterChange(event);
            }
        });

        document.addEventListener('change', (event) => {
            if (event.target.matches('[data-filter]')) {
                this.handleFilterChange(event);
            }
        });

        // Refresh button
        const refreshBtn = document.getElementById('refresh-queue');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshAppointments();
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            this.handleKeyboardShortcuts(event);
        });
    }

    /**
     * Set up filters
     */
    setupFilters() {
        const filters = this.options.container.querySelectorAll('[data-filter]');
        filters.forEach(filter => {
            filter.addEventListener('input', () => this.applyFilters());
            filter.addEventListener('change', () => this.applyFilters());
        });
    }

    /**
     * Set up drag and drop functionality
     */
    setupDragAndDrop() {
        if (!this.options.enableDragDrop) return;

        const container = document.getElementById('appointments-list');
        if (!container) return;

        if (typeof Sortable !== 'undefined') {
            this.sortable = new Sortable(container, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: (evt) => {
                    this.onDragStart(evt);
                },
                onEnd: (evt) => {
                    this.onDragEnd(evt);
                },
                onChange: (evt) => {
                    this.onDragChange(evt);
                }
            });
        }
    }

    /**
     * Set up status action handlers
     */
    setupStatusActions() {
        document.addEventListener('click', (event) => {
            if (event.target.closest('.status-action')) {
                event.preventDefault();
                this.handleStatusAction(event);
            }
        });
    }

    /**
     * Handle filter changes
     */
    handleFilterChange(event) {
        const filter = event.target.getAttribute('data-filter');
        const value = event.target.value;

        this.filters[filter] = value;
        this.applyFilters();
    }

    /**
     * Apply filters to the appointment list
     */
    applyFilters() {
        const cards = this.options.container.querySelectorAll('.appointment-card');

        cards.forEach(card => {
            const patientName = card.querySelector('.patient-name')?.textContent.toLowerCase() || '';
            const status = card.getAttribute('data-status') || '';
            const priority = card.getAttribute('data-priority') || '';

            const matchesSearch = !this.filters.search ||
                                patientName.includes(this.filters.search.toLowerCase());
            const matchesStatus = this.filters.status === 'all' || status === this.filters.status;
            const matchesPriority = this.filters.priority === 'all' || priority === this.filters.priority;

            const shouldShow = matchesSearch && matchesStatus && matchesPriority;
            card.style.display = shouldShow ? '' : 'none';

            // Update visibility count
            this.updateFilterCounts();
        });
    }

    /**
     * Update filter counts
     */
    updateFilterCounts() {
        const visibleCards = Array.from(this.options.container.querySelectorAll('.appointment-card'))
            .filter(card => card.style.display !== 'none');

        // Update queue statistics
        this.updateQueueStats(visibleCards);
    }

    /**
     * Update queue statistics
     */
    updateQueueStats(cards = null) {
        if (!cards) {
            cards = Array.from(this.options.container.querySelectorAll('.appointment-card'))
                .filter(card => card.style.display !== 'none');
        }

        const stats = {
            total: cards.length,
            waiting: cards.filter(card => card.getAttribute('data-status') === 'waiting').length,
            inProgress: cards.filter(card => card.getAttribute('data-status') === 'in_progress').length,
            avgWait: this.calculateAverageWait(cards)
        };

        // Update UI
        const totalElement = document.getElementById('queue-total');
        const waitingElement = document.getElementById('queue-waiting');
        const inProgressElement = document.getElementById('queue-in-progress');
        const avgWaitElement = document.getElementById('queue-avg-wait');

        if (totalElement) totalElement.textContent = stats.total;
        if (waitingElement) waitingElement.textContent = stats.waiting;
        if (inProgressElement) inProgressElement.textContent = stats.inProgress;
        if (avgWaitElement) avgWaitElement.textContent = `${stats.avgWait}m`;
    }

    /**
     * Calculate average wait time
     */
    calculateAverageWait(cards) {
        const waitTimes = cards
            .map(card => card.querySelector('.estimated-wait')?.getAttribute('data-wait-time'))
            .filter(wait => wait && !isNaN(wait))
            .map(wait => parseInt(wait));

        if (waitTimes.length === 0) return 0;

        const sum = waitTimes.reduce((a, b) => a + b, 0);
        return Math.round(sum / waitTimes.length);
    }

    /**
     * Handle status action button clicks
     */
    handleStatusAction(event) {
        const button = event.target.closest('.status-action');
        const action = button.getAttribute('data-action');
        const appointmentId = button.getAttribute('data-appointment-id');

        if (!appointmentId || !action) return;

        this.showStatusUpdateModal(appointmentId, action);
    }

    /**
     * Show status update modal
     */
    showStatusUpdateModal(appointmentId, action) {
        const modal = document.getElementById('statusUpdateModal');
        const content = document.getElementById('status-update-content');
        const confirmBtn = document.getElementById('confirm-status-update');

        if (!modal || !content) return;

        // Generate modal content based on action
        content.innerHTML = this.generateStatusUpdateContent(appointmentId, action);

        // Set up confirm handler
        confirmBtn.onclick = () => {
            this.updateAppointmentStatus(appointmentId, action);
            bootstrap.Modal.getInstance(modal)?.hide();
        };

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    /**
     * Generate status update modal content
     */
    generateStatusUpdateContent(appointmentId, action) {
        const appointment = this.appointments.get(appointmentId);
        const patientName = appointment?.patient?.name || 'Unknown Patient';

        const actionMessages = {
            'start': `Start appointment with ${patientName}?`,
            'complete': `Mark appointment with ${patientName} as completed?`,
            'no_show': `Mark ${patientName} as no-show?`,
            'call_patient': `Send notification to ${patientName}?`,
            'reschedule': `Reschedule appointment with ${patientName}?`,
            'cancel': `Cancel appointment with ${patientName}?`
        };

        const messages = actionMessages[action] || `Update appointment status?`;

        return `
            <div class="status-update-content">
                <div class="text-center mb-3">
                    <i class="fas fa-question-circle fa-2x text-warning"></i>
                </div>
                <p class="text-center">${messages}</p>
                <div class="form-group">
                    <label>Notes (optional)</label>
                    <textarea class="form-control"
                              id="status-update-notes"
                              placeholder="Add any notes about this status change..."
                              rows="3"></textarea>
                </div>
            </div>
        `;
    }

    /**
     * Update appointment status
     */
    async updateAppointmentStatus(appointmentId, newStatus) {
        try {
            const notes = document.getElementById('status-update-notes')?.value || '';

            const response = await fetch(`${this.options.statusUpdateApi}/${appointmentId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken
                },
                body: JSON.stringify({
                    status: newStatus,
                    notes: notes,
                    updated_by: this.options.userId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.showNotification('Status updated successfully', 'success');
                // The real-time update will handle the UI update
            } else {
                throw new Error(result.message || 'Failed to update status');
            }

        } catch (error) {
            // console.error('Error updating appointment status:', error);
            this.showNotification('Failed to update status', 'error');
        }
    }

    /**
     * Handle appointment status change from real-time events
     */
    handleAppointmentStatusChange(event) {
        const { data } = event;
        if (!data) return;

        const appointmentId = data.appointment_id;
        const newStatus = data.new_status;

        // Update the appointment card
        this.updateAppointmentCardStatus(appointmentId, newStatus, data);

        // Update queue statistics
        this.updateQueueStats();

        // Show notification
        this.showStatusChangeNotification(data);
    }

    /**
     * Update appointment card status
     */
    updateAppointmentCardStatus(appointmentId, newStatus, eventData = null) {
        const card = this.options.container.querySelector(`[data-appointment-id="${appointmentId}"]`);
        if (!card) return;

        // Update status
        card.setAttribute('data-status', newStatus);

        // Update status badge
        const statusBadge = card.querySelector('.status-badge');
        if (statusBadge) {
            // Remove existing status classes
            statusBadge.className = statusBadge.className.replace(/status-\w+/g, '');
            statusBadge.className = statusBadge.className.replace(/bg-\w+/g, '');

            // Add new status classes
            const statusClass = `status-${newStatus.replace('_', '-')}`;
            const bgClass = this.getStatusBackgroundClass(newStatus);
            statusBadge.classList.add(statusClass, bgClass);

            // Update content
            statusBadge.innerHTML = `
                <i class="fas fa-${this.getStatusIcon(newStatus)}"></i>
                ${this.getStatusDisplayName(newStatus)}
            `;
        }

        // Update action buttons
        this.updateActionButtons(card, newStatus);

        // Add animation
        card.classList.add('status-updating');
        setTimeout(() => {
            card.classList.remove('status-updating');
        }, 1000);

        // Update estimated wait time if provided
        if (eventData?.estimated_wait_minutes !== undefined) {
            this.updateWaitTime(card, eventData.estimated_wait_minutes);
        }
    }

    /**
     * Update action buttons based on status
     */
    updateActionButtons(card, status) {
        const actionsContainer = card.querySelector('.appointment-actions');
        if (!actionsContainer) return;

        const appointmentId = card.getAttribute('data-appointment-id');

        let buttonsHtml = '';

        switch (status) {
            case 'check_in':
                buttonsHtml = `
                    <button class="btn btn-sm btn-primary status-action" data-action="start" data-appointment-id="${appointmentId}">
                        <i class="fas fa-play"></i> Start
                    </button>
                    <button class="btn btn-sm btn-danger status-action" data-action="no_show" data-appointment-id="${appointmentId}">
                        <i class="fas fa-user-times"></i> No Show
                    </button>
                `;
                break;

            case 'in_progress':
                buttonsHtml = `
                    <button class="btn btn-sm btn-success status-action" data-action="complete" data-appointment-id="${appointmentId}">
                        <i class="fas fa-check"></i> Complete
                    </button>
                    <button class="btn btn-sm btn-danger status-action" data-action="no_show" data-appointment-id="${appointmentId}">
                        <i class="fas fa-user-times"></i> No Show
                    </button>
                `;
                break;

            case 'ready':
                buttonsHtml = `
                    <button class="btn btn-sm btn-info status-action" data-action="call_patient" data-appointment-id="${appointmentId}">
                        <i class="fas fa-bell"></i> Call Patient
                    </button>
                `;
                break;

            case 'completed':
            case 'cancelled':
            case 'no_show':
                // No action buttons for terminal states
                buttonsHtml = '';
                break;
        }

        // Add common buttons
        buttonsHtml += `
            <button class="btn btn-sm btn-outline-warning status-action" data-action="reschedule" data-appointment-id="${appointmentId}">
                <i class="fas fa-calendar-alt"></i> Reschedule
            </button>
            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/appointments/${appointmentId}">
                        <i class="fas fa-eye me-2"></i>View Details
                    </a></li>
                    <li><a class="dropdown-item" href="/appointments/${appointmentId}/edit">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="window.RealtimeAppointmentQueue.showStatusUpdateModal('${appointmentId}', 'cancel')">
                        <i class="fas fa-times me-2"></i>Cancel Appointment
                    </a></li>
                </ul>
            </div>
        `;

        actionsContainer.innerHTML = buttonsHtml;
    }

    /**
     * Update wait time display
     */
    updateWaitTime(card, waitTimeMinutes) {
        const waitElement = card.querySelector('.estimated-wait');
        if (!waitElement) return;

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

        // Update wait time class
        waitElement.className = waitElement.className.replace(/wait-\w+/g, '');
        const waitClass = waitTimeMinutes <= 15 ? 'wait-short' :
                         waitTimeMinutes <= 45 ? 'wait-medium' : 'wait-long';
        waitElement.classList.add(waitClass);
    }

    /**
     * Handle new appointment
     */
    handleNewAppointment(event) {
        const { data } = event;
        if (!data?.appointment) return;

        this.addAppointmentToQueue(data.appointment);
        this.updateQueueStats();
    }

    /**
     * Add appointment to queue
     */
    addAppointmentToQueue(appointmentData) {
        const container = document.getElementById('appointments-list');
        if (!container) return;

        // Remove empty state if present
        const emptyState = container.querySelector('.empty-queue');
        if (emptyState) {
            emptyState.remove();
        }

        // Generate appointment card HTML
        const cardHtml = this.generateAppointmentCardHtml(appointmentData);

        // Add to container
        container.insertAdjacentHTML('afterbegin', cardHtml);

        // Add to internal map
        this.appointments.set(appointmentData.id, appointmentData);

        // Animate the new card
        const newCard = container.firstElementChild;
        newCard.classList.add('appointment-adding');
        setTimeout(() => {
            newCard.classList.remove('appointment-adding');
        }, 500);
    }

    /**
     * Generate appointment card HTML
     */
    generateAppointmentCardHtml(appointmentData) {
        const status = appointmentData.status || 'pending';
        const patientName = appointmentData.patient?.name || appointmentData.patient_name || 'Unknown Patient';
        const appointmentTime = new Date(appointmentData.appointment_date);

        return `
            <div class="appointment-card appointment-card-realtime"
                 data-appointment-id="${appointmentData.id}"
                 data-status="${status}"
                 data-queue-position="${appointmentData.queue_position || ''}"
                 data-priority="${appointmentData.priority || 'normal'}"
                 data-appointment-time="${appointmentTime.getTime()}"
                 data-draggable="${this.options.enableDragDrop ? 'true' : 'false'}">

                <div class="realtime-indicator online"></div>

                <div class="appointment-header">
                    <div class="appointment-time-info">
                        <div class="appointment-time">${appointmentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        <div class="appointment-duration">${appointmentData.duration || 30}min</div>
                    </div>
                    <div class="appointment-status-info">
                        <span class="status-badge status-${status.replace('_', '-')} bg-${this.getStatusBackgroundClass(status)}">
                            <i class="fas fa-${this.getStatusIcon(status)}"></i>
                            ${this.getStatusDisplayName(status)}
                        </span>
                    </div>
                </div>

                <div class="appointment-patient-info">
                    <div class="patient-name">${patientName}</div>
                    <div class="appointment-type">
                        <i class="fas fa-${this.getAppointmentTypeIcon(appointmentData.appointment_type)}"></i>
                        ${this.getAppointmentTypeDisplay(appointmentData.appointment_type)}
                    </div>
                </div>

                <div class="appointment-meta">
                    ${appointmentData.queue_position ? `
                        <div class="queue-position" data-position="${appointmentData.queue_position}">
                            #${appointmentData.queue_position}
                        </div>
                    ` : ''}
                    ${appointmentData.estimated_wait_minutes ? `
                        <div class="estimated-wait" data-wait-time="${appointmentData.estimated_wait_minutes}">
                            ${this.formatWaitTime(appointmentData.estimated_wait_minutes)}
                        </div>
                    ` : ''}
                </div>

                <div class="appointment-actions">
                    ${this.generateActionButtons(appointmentData.id, status)}
                </div>
            </div>
        `;
    }

    /**
     * Generate action buttons HTML
     */
    generateActionButtons(appointmentId, status) {
        let buttons = '';

        switch (status) {
            case 'check_in':
                buttons = `
                    <button class="btn btn-sm btn-primary status-action" data-action="start" data-appointment-id="${appointmentId}">
                        <i class="fas fa-play"></i> Start
                    </button>
                `;
                break;
            case 'in_progress':
                buttons = `
                    <button class="btn btn-sm btn-success status-action" data-action="complete" data-appointment-id="${appointmentId}">
                        <i class="fas fa-check"></i> Complete
                    </button>
                `;
                break;
        }

        buttons += `
            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/appointments/${appointmentId}">
                        <i class="fas fa-eye me-2"></i>View Details
                    </a></li>
                </ul>
            </div>
        `;

        return buttons;
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
                    this.refreshAppointments();
                }
                break;
            case 'Escape':
                this.clearFilters();
                break;
        }
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        const filters = this.options.container.querySelectorAll('[data-filter]');
        filters.forEach(filter => {
            if (filter.tagName === 'SELECT') {
                filter.value = 'all';
            } else {
                filter.value = '';
            }
        });

        this.filters = {
            search: '',
            status: 'all',
            priority: 'all'
        };

        this.applyFilters();
    }

    /**
     * Refresh appointments
     */
    async refreshAppointments() {
        const refreshBtn = document.getElementById('refresh-queue');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;
        }

        try {
            await this.loadAppointments();
            this.showNotification('Queue refreshed', 'success');
        } catch (error) {
            // console.error('Error refreshing appointments:', error);
            this.showNotification('Failed to refresh queue', 'error');
        } finally {
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshBtn.disabled = false;
            }
        }
    }

    /**
     * Load appointments from API
     */
    async loadAppointments() {
        try {
            const response = await fetch(this.options.appointmentApi);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                this.appointments.clear();
                result.data.forEach(appointment => {
                    this.appointments.set(appointment.id, appointment);
                });

                this.renderAppointments(result.data);
                this.updateQueueStats();
            }

        } catch (error) {
            // console.error('Error loading appointments:', error);
            throw error;
        }
    }

    /**
     * Render appointments in the container
     */
    renderAppointments(appointments) {
        const container = document.getElementById('appointments-list');
        if (!container) return;

        if (appointments.length === 0) {
            container.innerHTML = `
                <div class="empty-queue">
                    <i class="fas fa-calendar-times"></i>
                    <h6>No appointments in queue</h6>
                    <p class="text-muted">Appointments will appear here when they're ready</p>
                </div>
            `;
            return;
        }

        const cardsHtml = appointments.map(appointment =>
            this.generateAppointmentCardHtml(appointment)
        ).join('');

        container.innerHTML = cardsHtml;
    }

    /**
     * Update connection status indicator
     */
    updateConnectionStatus(statusDetail) {
        const indicator = this.options.container.querySelector('.realtime-connection-indicator');
        if (!indicator) return;

        const statusText = indicator.querySelector('.connection-status-text');
        const statusIcon = indicator.querySelector('.connection-status-icon');

        // Remove existing status classes
        indicator.className = indicator.className.replace(/connection-\w+/g, '');

        // Add new status class
        indicator.classList.add(`connection-${statusDetail.newState}`);

        // Update text and icon
        if (statusText) {
            statusText.textContent = this.getConnectionStatusText(statusDetail.newState);
        }

        if (statusIcon) {
            statusIcon.className = `connection-status-icon fa fa-${this.getConnectionStatusIcon(statusDetail.newState)}`;
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Use the realtime client's notification system
        if (this.realtimeClient) {
            this.realtimeClient.displayNotification({
                type: 'queue_notification',
                title: 'Queue Update',
                message: message,
                icon: type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle'),
                iconColor: type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#17a2b8'),
                duration: 3000
            });
        }
    }

    /**
     * Show status change notification
     */
    showStatusChangeNotification(data) {
        if (this.realtimeClient) {
            this.realtimeClient.showStatusChangeNotification(data);
        }
    }

    /**
     * Drag and drop handlers
     */
    onDragStart(evt) {
        evt.item.classList.add('dragging');
    }

    onDragEnd(evt) {
        evt.item.classList.remove('dragging');
        this.updateAppointmentOrder();
    }

    onDragChange(evt) {
        // Handle real-time position updates during drag
        this.updateQueuePositions();
    }

    /**
     * Update appointment order after drag and drop
     */
    async updateAppointmentOrder() {
        const cards = this.options.container.querySelectorAll('.appointment-card');
        const order = Array.from(cards).map(card => parseInt(card.getAttribute('data-appointment-id')));

        try {
            const response = await fetch('/api/appointments/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken
                },
                body: JSON.stringify({
                    order: order,
                    doctor_id: this.options.doctorId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

        } catch (error) {
            // console.error('Error updating appointment order:', error);
            this.showNotification('Failed to update appointment order', 'error');
        }
    }

    /**
     * Update queue positions
     */
    updateQueuePositions() {
        const cards = this.options.container.querySelectorAll('.appointment-card');
        cards.forEach((card, index) => {
            const positionElement = card.querySelector('.queue-position');
            if (positionElement) {
                const newPosition = index + 1;
                positionElement.textContent = `#${newPosition}`;
                positionElement.setAttribute('data-position', newPosition);
                card.setAttribute('data-queue-position', newPosition);
            }
        });
    }

    // Utility methods
    getStatusBackgroundClass(status) {
        const classes = {
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
        return classes[status] || 'secondary';
    }

    getStatusIcon(status) {
        const icons = {
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
        return icons[status] || 'calendar';
    }

    getStatusDisplayName(status) {
        return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    getAppointmentTypeIcon(type) {
        const icons = {
            'video_call': 'video',
            'phone_call': 'phone',
            'in_person': 'hospital',
            'consultation': 'stethoscope'
        };
        return icons[type] || 'calendar';
    }

    getAppointmentTypeDisplay(type) {
        return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    formatWaitTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;

        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
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

    getConnectionStatusIcon(state) {
        const icons = {
            'connected': 'wifi',
            'connecting': 'spinner fa-spin',
            'disconnected': 'wifi-slash',
            'reconnecting': 'redo fa-spin',
            'failed': 'exclamation-triangle'
        };
        return icons[state] || 'question';
    }

    /**
     * Clean up resources
     */
    destroy() {
        if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
        }

        if (this.realtimeClient) {
            this.realtimeClient.unsubscribeFromAll();
            this.realtimeClient = null;
        }

        this.appointments.clear();
        this.isInitialized = false;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealtimeAppointmentQueue;
}

// Make available globally
window.RealtimeAppointmentQueue = RealtimeAppointmentQueue;
