import React from 'react';
import { createRoot } from 'react-dom/client';
import ClinicalDashboard from './ClinicalDashboard';
import AlertManagement from './AlertManagement';
import ConfigurationPanel from './ConfigurationPanel';

const ClinicalMonitoringApp = () => {
    try {
        const dashboardElement = document.getElementById('clinical-dashboard-root');
        const alertManagerElement = document.getElementById('alert-management-root');
        const configPanelElement = document.getElementById('clinical-config-root');

        if (dashboardElement) {
            const patientId = dashboardElement.getAttribute('data-patient-id');
            if (patientId) {
                const root = createRoot(dashboardElement);
                root.render(<ClinicalDashboard patientId={patientId} />);
            } else {
                console.warn('ClinicalDashboard element found but no data-patient-id attribute provided');
            }
        }

        if (alertManagerElement) {
            const root = createRoot(alertManagerElement);
            root.render(<AlertManagement />);
        }

        if (configPanelElement) {
            const root = createRoot(configPanelElement);
            root.render(<ConfigurationPanel />);
        }
    } catch (error) {
        console.error('Error initializing ClinicalMonitoringApp:', error);
    }
};

export default ClinicalMonitoringApp;
