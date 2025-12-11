import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import WidgetRenderer from '../Widgets/WidgetRenderer';
import { Widget } from '../../types/dashboard';

interface SortableWidgetProps {
  widget: Widget;
  isEditMode: boolean;
  onSelect: () => void;
  onUpdate: (updates: Partial<Widget>) => void;
  onDelete: () => void;
}

export const SortableWidget: React.FC<SortableWidgetProps> = ({
  widget,
  isEditMode,
  onSelect,
  onUpdate,
  onDelete,
}) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: widget.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`widget-container bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden ${
        isEditMode ? 'cursor-move' : ''
      }`}
      onClick={isEditMode ? onSelect : undefined}
      role="article"
      aria-label={`${widget.title} widget`}
      tabIndex={isEditMode ? 0 : -1}
    >
      {isEditMode && (
        <div className="flex items-center justify-between p-3 bg-gray-50 border-b border-gray-200">
          <div
            {...attributes}
            {...listeners}
            className="drag-handle flex items-center text-gray-400 hover:text-gray-600"
          >
            <i className="fas fa-grip-vertical mr-2"></i>
            <span className="text-sm font-medium">Drag to reorder</span>
          </div>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onDelete();
            }}
            className="text-red-500 hover:text-red-700 p-1"
            title="Delete widget"
          >
            <i className="fas fa-trash"></i>
          </button>
        </div>
      )}

      <div className="p-4">
        <WidgetRenderer
          widget={widget}
          isEditMode={isEditMode}
          onUpdate={onUpdate}
        />
      </div>
    </div>
  );
};
