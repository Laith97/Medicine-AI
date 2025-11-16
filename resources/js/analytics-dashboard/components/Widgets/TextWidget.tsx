import React from 'react';
import { Widget } from '../../types/dashboard';

interface TextWidgetProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const TextWidget: React.FC<TextWidgetProps> = ({ widget, isEditMode, onUpdate }) => {
  const { title, config } = widget;
  const { content = 'Enter your text here...' } = config;

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

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Content
          </label>
          <textarea
            value={content}
            onChange={(e) => onUpdate({
              config: { ...config, content: e.target.value }
            })}
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your text content..."
          />
        </div>
      </div>
    );
  }

  return (
    <div>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div className="prose prose-sm max-w-none">
        <p className="text-gray-700 whitespace-pre-wrap">{content}</p>
      </div>
    </div>
  );
};

export default TextWidget;
