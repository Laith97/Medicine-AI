import React from 'react';
import KPICard from './KPICard';
import ChartWidget from './ChartWidget';
import D3ChartWidget from './D3ChartWidget';
import TableWidget from './TableWidget';
import TextWidget from './TextWidget';
import { Widget } from '../../types/dashboard';

interface WidgetRendererProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const WidgetRenderer: React.FC<WidgetRendererProps> = ({
  widget,
  isEditMode,
  onUpdate,
}) => {
  switch (widget.type) {
    case 'kpi':
      return (
        <KPICard
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      );
    case 'chart':
      return (
        <ChartWidget
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      );
    case 'd3-chart':
      return (
        <D3ChartWidget
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      );
    case 'table':
      return (
        <TableWidget
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      );
    case 'text':
      return (
        <TextWidget
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      );
    default:
      return (
        <div className="p-4 text-center text-gray-500">
          <i className="fas fa-exclamation-triangle text-2xl mb-2"></i>
          <p>Unknown widget type: {widget.type}</p>
        </div>
      );
  }
};

export default WidgetRenderer;
