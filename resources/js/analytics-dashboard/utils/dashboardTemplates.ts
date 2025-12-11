import { DashboardTemplate, Widget } from '../types/dashboard';

export const dashboardTemplates: Record<string, DashboardTemplate> = {
  executive: {
    id: 'executive',
    name: 'Executive Dashboard',
    description: 'High-level overview of key business metrics and KPIs',
    role: 'executive',
    config: {
      id: 'executive',
      name: 'Executive Dashboard',
      layout: 'grid',
      columns: 4,
      widgets: [
        {
          id: 'revenue-kpi',
          type: 'kpi',
          title: 'Revenue',
          position: { x: 0, y: 0, width: 1, height: 1 },
          config: {
            metric: 'revenue',
            showTrend: true,
            showTarget: true,
          },
        },
        {
          id: 'appointments-chart',
          type: 'chart',
          title: 'Monthly Appointments',
          position: { x: 1, y: 0, width: 2, height: 2 },
          config: {
            chartType: 'line',
            dataSource: 'appointments',
          },
        },
        {
          id: 'satisfaction-kpi',
          type: 'kpi',
          title: 'Patient Satisfaction',
          position: { x: 3, y: 0, width: 1, height: 1 },
          config: {
            metric: 'patient_satisfaction',
            showTrend: true,
          },
        },
        {
          id: 'efficiency-kpi',
          type: 'kpi',
          title: 'Operational Efficiency',
          position: { x: 0, y: 1, width: 1, height: 1 },
          config: {
            metric: 'operational_efficiency',
            showTrend: true,
            showTarget: true,
          },
        },
        {
          id: 'clinical-outcomes-kpi',
          type: 'kpi',
          title: 'Clinical Outcomes',
          position: { x: 3, y: 1, width: 1, height: 1 },
          config: {
            metric: 'clinical_outcomes',
            showTrend: true,
            showTarget: true,
          },
        },
      ],
    },
  },

  doctor: {
    id: 'doctor',
    name: 'Doctor Dashboard',
    description: 'Patient care and appointment management metrics',
    role: 'doctor',
    config: {
      id: 'doctor',
      name: 'Doctor Dashboard',
      layout: 'grid',
      columns: 4,
      widgets: [
        {
          id: 'today-appointments',
          type: 'kpi',
          title: 'Today\'s Appointments',
          position: { x: 0, y: 0, width: 1, height: 1 },
          config: {
            metric: 'today_appointments',
          },
        },
        {
          id: 'patient-wait-times',
          type: 'chart',
          title: 'Patient Wait Times',
          position: { x: 1, y: 0, width: 2, height: 2 },
          config: {
            chartType: 'bar',
            dataSource: 'wait_times',
          },
        },
        {
          id: 'satisfaction-score',
          type: 'kpi',
          title: 'Avg Satisfaction',
          position: { x: 3, y: 0, width: 1, height: 1 },
          config: {
            metric: 'satisfaction_score',
            showTrend: true,
          },
        },
        {
          id: 'upcoming-appointments',
          type: 'table',
          title: 'Upcoming Appointments',
          position: { x: 0, y: 1, width: 2, height: 2 },
          config: {
            dataSource: 'upcoming_appointments',
          },
        },
        {
          id: 'treatment-outcomes',
          type: 'chart',
          title: 'Treatment Success Rates',
          position: { x: 2, y: 1, width: 2, height: 2 },
          config: {
            chartType: 'doughnut',
            dataSource: 'treatment_outcomes',
          },
        },
      ],
    },
  },

  admin: {
    id: 'admin',
    name: 'Hospital Admin Dashboard',
    description: 'Comprehensive hospital operations and financial metrics',
    role: 'hospital_admin',
    config: {
      id: 'admin',
      name: 'Hospital Admin Dashboard',
      layout: 'grid',
      columns: 4,
      widgets: [
        {
          id: 'total-revenue',
          type: 'kpi',
          title: 'Total Revenue',
          position: { x: 0, y: 0, width: 1, height: 1 },
          config: {
            metric: 'total_revenue',
            showTrend: true,
            showTarget: true,
          },
        },
        {
          id: 'revenue-trend',
          type: 'd3-chart',
          title: 'Revenue Trend',
          position: { x: 1, y: 0, width: 2, height: 2 },
          config: {
            chartType: 'd3-line',
            dataSource: 'revenue_trend',
          },
        },
        {
          id: 'patient-volume',
          type: 'kpi',
          title: 'Patient Volume',
          position: { x: 3, y: 0, width: 1, height: 1 },
          config: {
            metric: 'patient_volume',
            showTrend: true,
          },
        },
        {
          id: 'staff-utilization',
          type: 'chart',
          title: 'Staff Utilization',
          position: { x: 0, y: 1, width: 2, height: 2 },
          config: {
            chartType: 'bar',
            dataSource: 'staff_utilization',
          },
        },
        {
          id: 'financial-summary',
          type: 'table',
          title: 'Financial Summary',
          position: { x: 2, y: 1, width: 2, height: 2 },
          config: {
            dataSource: 'financial_summary',
          },
        },
      ],
    },
  },
};

export const getDashboardTemplate = (role: string): DashboardTemplate | null => {
  // Map user roles to template keys
  const roleMapping: Record<string, string> = {
    'executive': 'executive',
    'ceo': 'executive',
    'cfo': 'executive',
    'doctor': 'doctor',
    'physician': 'doctor',
    'hospital_admin': 'admin',
    'administrator': 'admin',
    'admin': 'admin',
  };

  const templateKey = roleMapping[role.toLowerCase()] || 'executive';
  return dashboardTemplates[templateKey] || null;
};

export const getAvailableTemplates = (): DashboardTemplate[] => {
  return Object.values(dashboardTemplates);
};

export const createDashboardFromTemplate = (template: DashboardTemplate): Widget[] => {
  return template.config.widgets || [];
};
