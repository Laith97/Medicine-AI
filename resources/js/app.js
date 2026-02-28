import './bootstrap';
import './notifications-fixed';
import './offline-notifications';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global error handling for production debugging
window.addEventListener('error', (e) => {
    console.error('Global JavaScript error:', e.error);
    // Could send to error tracking service here
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
    // Could send to error tracking service here
});

Alpine.start();

// Mount Clinical Monitoring React App
import ClinicalMonitoringApp from './components/ClinicalMonitoringApp';
document.addEventListener('DOMContentLoaded', () => {
    ClinicalMonitoringApp();
});
