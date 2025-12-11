import React, { useState, useEffect } from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  rectSortingStrategy,
} from '@dnd-kit/sortable';
import {
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import DashboardHeader from './DashboardHeader';
import DashboardGrid from './DashboardGrid';
import WidgetConfigPanel from './WidgetConfigPanel';
import { Widget, DashboardConfig, WidgetType } from '../../types/dashboard';
import { useDashboardData } from '../../hooks/useDashboardData';
import { useRealTimeUpdates } from '../../hooks/useRealTimeUpdates';
import { getDashboardTemplate, createDashboardFromTemplate } from '../../utils/dashboardTemplates';

const Dashboard: React.FC = () => {
  const [widgets, setWidgets] = useState<Widget[]>([]);
  const [isEditMode, setIsEditMode] = useState(false);
  const [selectedWidget, setSelectedWidget] = useState<Widget | null>(null);
  const [dashboardConfig, setDashboardConfig] = useState<DashboardConfig>({
    id: 'executive',
    name: 'Executive Dashboard',
    layout: 'grid',
    columns: 4,
  });

  const { data, loading, error } = useDashboardData(dashboardConfig.id);
  const { connect, disconnect } = useRealTimeUpdates(dashboardConfig.id, (update) => {
    // Handle real-time updates
    setWidgets(prevWidgets =>
      prevWidgets.map(widget =>
        widget.id === update.component ? { ...widget, data: update.data } : widget
      )
    );
  });

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  useEffect(() => {
    // Get user role from localStorage or default to executive
    const userRole = localStorage.getItem('user_role') || 'executive';

    // Load dashboard template based on user role
    const template = getDashboardTemplate(userRole);
    let templateWidgets: Widget[] = [];

    if (template) {
      templateWidgets = createDashboardFromTemplate(template);
      setDashboardConfig(template.config);
    } else {
      // Fallback to default widgets if no template found
      templateWidgets = [
        {
          id: 'revenue-card',
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
          id: 'patient-satisfaction',
          type: 'kpi',
          title: 'Patient Satisfaction',
          position: { x: 3, y: 0, width: 1, height: 1 },
          config: {
            metric: 'patient_satisfaction',
            showTrend: true,
          },
        },
      ];
    }

    // Update widgets with real-time data
    const widgetsWithData = templateWidgets.map(widget => ({
      ...widget,
      data: getWidgetData(widget, data),
    }));

    setWidgets(widgetsWithData);
  }, [data]);

  const getWidgetData = (widget: Widget, dashboardData: any) => {
    // Map widget config to actual data from API response
    switch (widget.config.metric) {
      case 'revenue':
        return dashboardData?.summary?.revenue;
      case 'patient_satisfaction':
        return dashboardData?.summary?.patient_satisfaction;
      case 'operational_efficiency':
        return dashboardData?.summary?.operational_efficiency;
      case 'clinical_outcomes':
        return dashboardData?.summary?.clinical_outcomes;
      default:
        if (widget.config.dataSource === 'appointments') {
          return dashboardData?.charts?.appointments_trend;
        }
        return widget.data;
    }
  };

  useEffect(() => {
    connect();
    return () => disconnect();
  }, [connect, disconnect]);

  // Load saved dashboard configuration on mount
  useEffect(() => {
    loadDashboardConfig();
  }, []);

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (active.id !== over?.id) {
      setWidgets((items) => {
        const oldIndex = items.findIndex((item) => item.id === active.id);
        const newIndex = items.findIndex((item) => item.id === over?.id);

        return arrayMove(items, oldIndex, newIndex);
      });
    }
  };

  const handleWidgetUpdate = (widgetId: string, updates: Partial<Widget>) => {
    setWidgets(prevWidgets =>
      prevWidgets.map(widget =>
        widget.id === widgetId ? { ...widget, ...updates } : widget
      )
    );
  };

  // Save dashboard configuration to localStorage
  const saveDashboardConfig = () => {
    const config = {
      widgets,
      dashboardConfig,
      lastSaved: new Date().toISOString(),
    };
    localStorage.setItem(`dashboard_${dashboardConfig.id}`, JSON.stringify(config));
  };

  // Load dashboard configuration from localStorage
  const loadDashboardConfig = () => {
    const saved = localStorage.getItem(`dashboard_${dashboardConfig.id}`);
    if (saved) {
      try {
        const config = JSON.parse(saved);
        setWidgets(config.widgets || []);
        setDashboardConfig(config.dashboardConfig || dashboardConfig);
      } catch (error) {
        console.error('Failed to load dashboard configuration:', error);
      }
    }
  };

  // Auto-save dashboard configuration when widgets change
  useEffect(() => {
    if (widgets.length > 0) {
      saveDashboardConfig();
    }
  }, [widgets, dashboardConfig]);

  const handleAddWidget = (type: WidgetType) => {
    const newWidget: Widget = {
      id: `widget-${Date.now()}`,
      type,
      title: `New ${type} Widget`,
      position: { x: 0, y: 0, width: 1, height: 1 },
      config: {},
    };
    setWidgets(prev => [...prev, newWidget]);
  };

  const handleDeleteWidget = (widgetId: string) => {
    setWidgets(prev => prev.filter(widget => widget.id !== widgetId));
    if (selectedWidget?.id === widgetId) {
      setSelectedWidget(null);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-red-600 text-center">
          <h2 className="text-xl font-semibold mb-2">Error Loading Dashboard</h2>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="analytics-dashboard min-h-screen bg-gray-50">
      <DashboardHeader
        title={dashboardConfig.name}
        isEditMode={isEditMode}
        onToggleEditMode={() => setIsEditMode(!isEditMode)}
        onAddWidget={handleAddWidget}
      />

      <DndContext
        sensors={sensors}
        collisionDetection={closestCenter}
        onDragEnd={handleDragEnd}
      >
        <SortableContext items={widgets.map(w => w.id)} strategy={rectSortingStrategy}>
          <DashboardGrid
            widgets={widgets}
            isEditMode={isEditMode}
            onWidgetSelect={setSelectedWidget}
            onWidgetUpdate={handleWidgetUpdate}
            onWidgetDelete={handleDeleteWidget}
          />
        </SortableContext>
      </DndContext>

      {selectedWidget && isEditMode && (
        <WidgetConfigPanel
          widget={selectedWidget}
          onUpdate={(updates) => handleWidgetUpdate(selectedWidget.id, updates)}
          onClose={() => setSelectedWidget(null)}
        />
      )}
    </div>
  );
};

export default Dashboard;
