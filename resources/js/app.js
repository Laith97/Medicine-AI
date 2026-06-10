import './bootstrap';
// Use the UNIFIED notification system (single source of truth)
import './unified-notifications';

// DISABLED: All other notification systems to prevent duplicates
// import './fixed-notifications';
// import './notifications-unified';
// import './working-notifications';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global error handling for production debugging
window.addEventListener('error', (e) => {
    console.error('Global JavaScript error:', e.error);
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
});

Alpine.start();

// Mount Clinical Monitoring React App
import ClinicalMonitoringApp from './components/ClinicalMonitoringApp';
document.addEventListener('DOMContentLoaded', () => {
    ClinicalMonitoringApp();
});
