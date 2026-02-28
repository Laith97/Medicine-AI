import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableWidget } from './SortableWidget';
import { Widget } from '../../types/dashboard';

interface DashboardGridProps {
  widgets: Widget[];
  isEditMode: boolean;
  onWidgetSelect: (widget: Widget) => void;
  onWidgetUpdate: (widgetId: string, updates: Partial<Widget>) => void;
  onWidgetDelete: (widgetId: string) => void;
}

const DashboardGrid: React.FC<DashboardGridProps> = ({
  widgets,
  isEditMode,
  onWidgetSelect,
  onWidgetUpdate,
  onWidgetDelete,
}) => {
  const { setNodeRef } = useDroppable({
    id: 'dashboard-grid',
  });

  return (
    <div
      ref={setNodeRef}
      className="dashboard-grid grid gap-4 sm:gap-6 p-4 sm:p-6 min-h-screen"
      style={{
        gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
      }}
    >
      {widgets.map((widget) => (
        <SortableWidget
          key={widget.id}
          widget={widget}
          isEditMode={isEditMode}
          onSelect={() => onWidgetSelect(widget)}
          onUpdate={(updates) => onWidgetUpdate(widget.id, updates)}
          onDelete={() => onWidgetDelete(widget.id)}
        />
      ))}

      {widgets.length === 0 && (
        <div className="col-span-full flex items-center justify-center py-12">
          <div className="text-center">
            <i className="fas fa-chart-line text-6xl text-gray-300 mb-4"></i>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No widgets yet</h3>
            <p className="text-gray-500 mb-4">
              {isEditMode
                ? 'Click "Add Widget" to start building your dashboard'
                : 'Enable edit mode to add widgets'
              }
            </p>
            {isEditMode && (
              <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i className="fas fa-plus mr-2"></i>
                Add Your First Widget
              </button>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default DashboardGrid;
