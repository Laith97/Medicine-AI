import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

class ClinicalMonitoringService {
    constructor() {
        this.axios = window.axios || axios;
        this.echo = window.Echo;
        
        if (!this.echo) {
            console.warn('Echo not found on window, initializing fallback Echo');
            const pusherKey = typeof import.meta !== 'undefined' && import.meta.env ? import.meta.env.VITE_PUSHER_APP_KEY : window.VITE_PUSHER_APP_KEY;
            const pusherCluster = typeof import.meta !== 'undefined' && import.meta.env ? import.meta.env.VITE_PUSHER_APP_CLUSTER : window.VITE_PUSHER_APP_CLUSTER;

            if (pusherKey) {
                this.echo = new Echo({
                    broadcaster: 'pusher',
                    key: pusherKey,
                    cluster: pusherCluster || 'mt1',
                    forceTLS: true,
                    encrypted: true
                });
            }
        }
    }

    async getAlerts() {
        try {
            const response = await this.axios.get('/api/monitoring/alerts');
            return response.data;
        } catch (error) {
            console.error('Error fetching clinical alerts:', error);
            return [];
        }
    }

    async acknowledgeAlert(id) {
        try {
            const response = await this.axios.post(`/api/monitoring/alerts/${id}/acknowledge`);
            return response.data;
        } catch (error) {
            console.error('Error acknowledging alert:', error);
            throw error;
        }
    }

    async escalateAlert(id) {
        try {
            const response = await this.axios.post(`/api/monitoring/alerts/${id}/escalate`);
            return response.data;
        } catch (error) {
            console.error('Error escalating alert:', error);
            throw error;
        }
    }

    subscribeToAlerts(callback) {
        if (!this.echo) {
            console.error('Echo instance not available for alerts subscription');
            return;
        }

        try {
            this.echo.private('clinical-alerts')
                .listen('.clinical.alert.triggered', (e) => {
                    callback(e.alert);
                });
        } catch (error) {
            console.error('Error subscribing to clinical alerts:', error);
        }
    }

    subscribeToPatientData(patientId, callback) {
        if (!this.echo || !patientId) return;

        try {
            this.echo.private(`App.User.${patientId}`)
                .listen('.clinical.alert.triggered', (e) => {
                    callback(e.alert);
                });
        } catch (error) {
            console.error(`Error subscribing to patient ${patientId} data:`, error);
        }
    }

    async getRules() {
        try {
            const response = await this.axios.get('/api/monitoring/rules');
            return response.data;
        } catch (error) {
            console.error('Error fetching clinical rules:', error);
            return [];
        }
    }

    async updateRule(id, ruleData) {
        try {
            const response = await this.axios.put(`/api/monitoring/rules/${id}`, ruleData);
            return response.data;
        } catch (error) {
            console.error('Error updating clinical rule:', error);
            throw error;
        }
    }
}

export default new ClinicalMonitoringService();
