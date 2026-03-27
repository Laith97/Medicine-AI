import React from 'react';
import { WidgetType } from '../../types/dashboard';

interface DashboardHeaderProps {
  title: string;
  isEditMode: boolean;
  onToggleEditMode: () => void;
  onAddWidget: (type: WidgetType) => void;
}

const DashboardHeader: React.FC<DashboardHeaderProps> = ({
  title,
  isEditMode,
  onToggleEditMode,
  onAddWidget,
}) => {
  const widgetTypes: { type: WidgetType; label: string; icon: string }[] = [
    { type: 'kpi', label: 'KPI Card', icon: 'fas fa-chart-line' },
    { type: 'chart', label: 'Chart.js Chart', icon: 'fas fa-chart-bar' },
    { type: 'd3-chart', label: 'D3 Chart', icon: 'fas fa-project-diagram' },
    { type: 'table', label: 'Table', icon: 'fas fa-table' },
    { type: 'text', label: 'Text', icon: 'fas fa-font' },
  ];

  return (
    <header className="bg-white shadow-sm border-b border-gray-200 px-4 sm:px-6 py-4">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-4 sm:space-y-0">
        <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
          <h1 className="text-xl sm:text-2xl font-bold text-gray-900">{title}</h1>
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            Live Data
          </span>
        </div>

        <div className="flex flex-wrap items-center gap-2 sm:gap-3">
          {isEditMode && (
            <div className="relative group">
              <button
                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                onClick={() => {}}
              >
                <i className="fas fa-plus mr-2"></i>
                Add Widget
              </button>
              <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10 hidden group-hover:block">
                <div className="py-1">
                  {widgetTypes.map(({ type, label, icon }) => (
                    <button
                      key={type}
                      onClick={() => onAddWidget(type)}
                      className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      <i className={`${icon} mr-2`}></i>
                      {label}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          )}

          <button
            onClick={onToggleEditMode}
            aria-label={isEditMode ? 'Exit edit mode' : 'Enter edit mode'}
            aria-pressed={isEditMode}
            className={`inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
              isEditMode
                ? 'border-blue-500 text-blue-700 bg-blue-50 hover:bg-blue-100'
                : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
            }`}
          >
            <i className={`fas ${isEditMode ? 'fa-check' : 'fa-edit'} mr-2`}></i>
            {isEditMode ? 'Done Editing' : 'Edit Dashboard'}
          </button>

          <button className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i className="fas fa-download mr-2"></i>
            Export
          </button>

          <button className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i className="fas fa-share mr-2"></i>
            Share
          </button>
        </div>
      </div>

      {isEditMode && (
        <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
          <p className="text-sm text-blue-800">
            <i className="fas fa-info-circle mr-2"></i>
            Edit mode is active. Drag widgets to reorder them, click on widgets to configure, or use the Add Widget button to add new components.
          </p>
        </div>
      )}
    </header>
  );
};

export default DashboardHeader;
