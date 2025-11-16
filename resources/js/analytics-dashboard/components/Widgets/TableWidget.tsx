import React from 'react';
import { Widget } from '../../types/dashboard';

interface TableWidgetProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const TableWidget: React.FC<TableWidgetProps> = ({ widget, isEditMode, onUpdate }) => {
  const { title } = widget;

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
      </div>
    );
  }

  return (
    <div>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Column 1
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Column 2
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Column 3
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            <tr>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Data 1</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Data 2</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Data 3</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default TableWidget;
