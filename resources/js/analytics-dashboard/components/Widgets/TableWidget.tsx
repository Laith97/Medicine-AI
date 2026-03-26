import React from 'react';
import { Widget } from '../../types/dashboard';

interface TableWidgetProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const TableWidget: React.FC<TableWidgetProps> = ({ widget, isEditMode, onUpdate }) => {
  const { title, config, data } = widget;

  // Get columns from config or data, with defaults
  const columns = config?.columns || data?.labels || [];
  // Get row data from data.datasets or data.rows
  const rows = data?.datasets?.[0]?.data || data?.rows || [];

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

  // Handle empty state
  if (columns.length === 0 || rows.length === 0) {
    return (
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
        <div className="flex flex-col items-center justify-center py-12 text-gray-500">
          <p className="text-sm">No data available for this table.</p>
          <p className="text-xs mt-1">Configure the widget to display data.</p>
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
              {columns.map((col: string, index: number) => (
                <th key={index} className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {col}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {rows.map((row: any, rowIndex: number) => (
              <tr key={rowIndex}>
                {Array.isArray(row) ? (
                  row.map((cell: any, cellIndex: number) => (
                    <td key={cellIndex} className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {cell}
                    </td>
                  ))
                ) : (
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {row}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default TableWidget;
