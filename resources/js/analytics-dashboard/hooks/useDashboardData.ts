import { useState, useEffect } from 'react';
import axios from 'axios';
import { ApiResponse, WidgetData } from '../types/dashboard';

interface DashboardData {
  summary?: {
    revenue?: WidgetData;
    patient_satisfaction?: WidgetData;
    operational_efficiency?: WidgetData;
    clinical_outcomes?: WidgetData;
  };
  charts?: {
    revenue_trend?: WidgetData;
    patient_satisfaction_distribution?: WidgetData;
    appointments_trend?: WidgetData;
  };
  alerts?: Array<{
    id: string;
    type: string;
    message: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
  }>;
}

export const useDashboardData = (dashboardId: string) => {
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await axios.get<ApiResponse<DashboardData>>(
          `/api/analytics/dashboard/${dashboardId}`,
          {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            },
          }
        );

        if (response.data.status === 'success') {
          setData(response.data.data);
        } else {
          setError(response.data.message || 'Failed to load dashboard data');
        }
      } catch (err) {
        if (axios.isAxiosError(err)) {
          setError(err.response?.data?.message || 'Network error occurred');
        } else {
          setError('An unexpected error occurred');
        }
      } finally {
        setLoading(false);
      }
    };

    if (dashboardId) {
      fetchDashboardData();
    }
  }, [dashboardId]);

  const refetch = () => {
    setLoading(true);
    setError(null);
    // Re-trigger the effect by updating a dependency
    setData(null);
  };

  return { data, loading, error, refetch };
};
