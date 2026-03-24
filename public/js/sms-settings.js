/**
 * SMS Settings JavaScript Module
 * Handles API calls and UI updates for SMS provider settings across all views
 */

const SmsSettings = {
    // Provider descriptions for help tooltips
    providerDescriptions: {
        'twilio': 'Twilio is a cloud communications platform offering SMS, voice, and messaging APIs with global reach.',
        'plivo': 'Plivo provides SMS and voice APIs with competitive pricing and global carrier connections.',
        'messagebird': 'MessageBird offers enterprise-grade SMS messaging with high deliverability worldwide.',
        'unifonic': 'Unifonic is a communications platform focused on the Middle East and emerging markets.',
        'smsgatewayhub': 'SMS Gateway Hub provides bulk SMS services with easy API integration.',
        'log': 'Log mode records all SMS activity without actually sending messages. Use for testing.'
    },

    /**
     * Initialize SMS settings form
     * @param {Object} options - Configuration options
     * @param {string} options.formId - ID of the form element
     * @param {string} options.saveUrl - API endpoint URL for saving
     * @param {string} options.revertUrl - API endpoint URL for reverting (optional)
     * @param {Function} options.onSuccess - Callback on successful save
     * @param {Function} options.onError - Callback on error
     */
    init: function(options) {
        const defaults = {
            formId: 'smsSettingsForm',
            saveUrl: null,
            revertUrl: null,
            onSuccess: null,
            onError: null,
            showProviderInfo: true,
            providerSelectId: 'sms_provider',
            providerInfoId: 'providerInfo',
            providerDescriptionId: 'providerDescription',
            messageContainerId: 'messageContainer',
            saveBtnId: 'saveBtn',
            revertBtnId: 'revertBtn'
        };

        this.options = { ...defaults, ...options };
        this.form = document.getElementById(this.options.formId);
        this.providerSelect = document.getElementById(this.options.providerSelectId);
        this.messageContainer = document.getElementById(this.options.messageContainerId);
        this.saveBtn = document.getElementById(this.options.saveBtnId);
        this.revertBtn = document.getElementById(this.options.revertBtnId);

        if (!this.form) {
            // console.error('SMS Settings: Form not found');
            return;
        }

        this.bindEvents();
    },

    bindEvents: function() {
        // Provider info on selection change
        if (this.options.showProviderInfo && this.providerSelect) {
            this.providerSelect.addEventListener('change', () => this.showProviderInfo());

            // Show initial provider info if selected
            if (this.providerSelect.value && this.providerDescriptions[this.providerSelect.value]) {
                this.showProviderInfo();
            }
        }

        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Revert button
        if (this.revertBtn) {
            this.revertBtn.addEventListener('click', () => this.handleRevert());
        }
    },

    showProviderInfo: function() {
        if (!this.options.showProviderInfo) return;

        const providerInfo = document.getElementById(this.options.providerInfoId);
        const providerDescription = document.getElementById(this.options.providerDescriptionId);
        const selectedProvider = this.providerSelect?.value;

        if (providerInfo && providerDescription && selectedProvider && this.providerDescriptions[selectedProvider]) {
            providerDescription.textContent = this.providerDescriptions[selectedProvider];
            providerInfo.classList.remove('d-none');
        } else if (providerInfo) {
            providerInfo.classList.add('d-none');
        }
    },

    handleSubmit: async function(e) {
        e.preventDefault();

        if (!this.options.saveUrl) {
            // console.error('SMS Settings: Save URL not configured');
            return;
        }

        const formData = new FormData(this.form);
        const provider = formData.get('sms_provider');

        this.setLoading(this.saveBtn, true);

        try {
            const response = await fetch(this.options.saveUrl, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ sms_provider: provider })
            });

            const data = await response.json();

            if (response.ok) {
                this.showMessage('success', data.message || 'Settings saved successfully');
                if (this.options.onSuccess) {
                    this.options.onSuccess(data);
                } else {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } else {
                this.showMessage('danger', data.message || 'Failed to save settings');
                if (this.options.onError) {
                    this.options.onError(data);
                }
            }
        } catch (error) {
            this.showMessage('danger', 'An error occurred. Please try again.');
            if (this.options.onError) {
                this.options.onError(error);
            }
        } finally {
            this.setLoading(this.saveBtn, false);
        }
    },

    handleRevert: async function() {
        if (!confirm('Are you sure you want to revert to inherited settings?')) {
            return;
        }

        if (!this.options.revertUrl) {
            // console.error('SMS Settings: Revert URL not configured');
            return;
        }

        this.setLoading(this.revertBtn, true);

        try {
            const response = await fetch(this.options.revertUrl, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ sms_provider: null })
            });

            const data = await response.json();

            if (response.ok) {
                this.showMessage('success', data.message || 'Settings reverted successfully');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                this.showMessage('danger', data.message || 'Failed to revert settings');
            }
        } catch (error) {
            this.showMessage('danger', 'An error occurred. Please try again.');
        } finally {
            this.setLoading(this.revertBtn, false);
        }
    },

    showMessage: function(type, message) {
        if (!this.messageContainer) return;

        this.messageContainer.className = 'mt-4 alert alert-' + type;
        this.messageContainer.innerHTML = message;
        this.messageContainer.classList.remove('d-none');

        // Scroll to message
        this.messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    setLoading: function(button, loading) {
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    },

    getCsrfToken: function() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
};

/**
 * Toggle doctors list in hospital expand/collapse
 * @param {number} hospitalId - The hospital ID
 */
function toggleDoctors(hospitalId) {
    const doctorsList = document.getElementById('doctors-' + hospitalId);
    if (!doctorsList) return;

    const expandBtn = doctorsList.previousElementSibling;

    if (doctorsList.classList.contains('d-none')) {
        doctorsList.classList.remove('d-none');
        if (expandBtn && expandBtn.classList.contains('expand-btn')) {
            expandBtn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Hide doctor overrides';
        }
    } else {
        doctorsList.classList.add('d-none');
        if (expandBtn && expandBtn.classList.contains('expand-btn')) {
            const match = expandBtn.textContent.match(/\d+/);
            const count = match ? match[0] : '0';
            expandBtn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Show ' + count + ' doctor(s) with custom overrides';
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SmsSettings;
}