import React from 'react';
import { Widget } from '../../types/dashboard';

interface KPICardProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const KPICard: React.FC<KPICardProps> = ({ widget, isEditMode, onUpdate }) => {
  const { title, data, config } = widget;
  const { value, change, trend, target } = data || {};
  const { showTrend = true, showTarget = true } = config;

  const getTrendIcon = (trend?: string) => {
    switch (trend) {
      case 'up':
        return 'fas fa-arrow-up text-green-500';
      case 'down':
        return 'fas fa-arrow-down text-red-500';
      default:
        return 'fas fa-minus text-gray-500';
    }
  };

  const getTrendColor = (trend?: string) => {
    switch (trend) {
      case 'up':
        return 'text-green-600';
      case 'down':
        return 'text-red-600';
      default:
        return 'text-gray-600';
    }
  };

  if (isEditMode) {
    return (
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Title
          </label>
          <input
            type="text"
            value={title}
            onChange={(e) => onUpdate({ title: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={showTrend}
              onChange={(e) => onUpdate({
                config: { ...config, showTrend: e.target.checked }
              })}
              className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span className="ml-2 text-sm text-gray-700">Show Trend</span>
          </label>
          <label className="flex items-center">
            <input
              type="checkbox"
              checked={showTarget}
              onChange={(e) => onUpdate({
                config: { ...config, showTarget: e.target.checked }
              })}
              className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span className="ml-2 text-sm text-gray-700">Show Target</span>
          </label>
        </div>
      </div>
    );
  }

  return (
    <div className="text-center" role="region" aria-labelledby={`kpi-${widget.id}-title`}>
      <h3 id={`kpi-${widget.id}-title`} className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>

      <div className="text-3xl font-bold text-gray-900 mb-2">
        {value !== undefined ? (
          typeof value === 'number' ? (
            config?.metric === 'revenue' ? `$${value.toLocaleString()}` :
            config?.metric === 'patient_satisfaction' ? `${value}/5` :
            value.toLocaleString()
          ) : value
        ) : (
          <span className="text-gray-400">--</span>
        )}
      </div>

      {showTrend && change !== undefined && (
        <div className={`flex items-center justify-center text-sm mb-2 ${getTrendColor(trend)}`}>
          <i className={`${getTrendIcon(trend)} mr-1`}></i>
          <span>
            {change > 0 ? '+' : ''}{change}%
          </span>
        </div>
      )}

      {showTarget && target !== undefined && (
        <div className="text-sm text-gray-500">
          Target: {typeof target === 'number' ? target.toLocaleString() : target}
        </div>
      )}
    </div>
  );
};

export default KPICard;
