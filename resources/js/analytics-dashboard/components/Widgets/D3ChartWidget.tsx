import React, { useEffect, useRef } from 'react';
import * as d3 from 'd3';
import { Widget } from '../../types/dashboard';

interface D3ChartWidgetProps {
  widget: Widget;
  isEditMode: boolean;
  onUpdate: (updates: Partial<Widget>) => void;
}

const D3ChartWidget: React.FC<D3ChartWidgetProps> = ({ widget, isEditMode, onUpdate }) => {
  const svgRef = useRef<SVGSVGElement>(null);
  const { title, data, config } = widget;
  const { chartType = 'bar', dataSource } = config;
  const { labels, datasets } = data || {};

  useEffect(() => {
    if (!svgRef.current || isEditMode) return;

    const svg = d3.select(svgRef.current);
    svg.selectAll('*').remove(); // Clear previous content

    const margin = { top: 20, right: 30, bottom: 40, left: 40 };
    const width = 400 - margin.left - margin.right;
    const height = 300 - margin.top - margin.bottom;

    const g = svg
      .attr('width', width + margin.left + margin.right)
      .attr('height', height + margin.top + margin.bottom)
      .append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Sample data
    const sampleData = labels?.map((label, i) => ({
      label,
      value: datasets?.[0]?.data?.[i] || Math.random() * 100,
    })) || [
      { label: 'A', value: 30 },
      { label: 'B', value: 80 },
      { label: 'C', value: 45 },
      { label: 'D', value: 60 },
      { label: 'E', value: 20 },
    ];

    if (chartType === 'bar') {
      // Bar chart
      const x = d3.scaleBand()
        .domain(sampleData.map(d => d.label))
        .range([0, width])
        .padding(0.1);

      const y = d3.scaleLinear()
        .domain([0, d3.max(sampleData, d => d.value)!])
        .range([height, 0]);

      g.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x));

      g.append('g')
        .call(d3.axisLeft(y));

      g.selectAll('.bar')
        .data(sampleData)
        .enter()
        .append('rect')
        .attr('class', 'bar')
        .attr('x', d => x(d.label)!)
        .attr('y', d => y(d.value))
        .attr('width', x.bandwidth())
        .attr('height', d => height - y(d.value))
        .attr('fill', '#3B82F6');

    } else if (chartType === 'line') {
      // Line chart
      const x = d3.scalePoint()
        .domain(sampleData.map(d => d.label))
        .range([0, width]);

      const y = d3.scaleLinear()
        .domain([0, d3.max(sampleData, d => d.value)!])
        .range([height, 0]);

      const line = d3.line<{ label: string; value: number }>()
        .x(d => x(d.label)!)
        .y(d => y(d.value))
        .curve(d3.curveMonotoneX);

      g.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x));

      g.append('g')
        .call(d3.axisLeft(y));

      g.append('path')
        .datum(sampleData)
        .attr('fill', 'none')
        .attr('stroke', '#3B82F6')
        .attr('stroke-width', 2)
        .attr('d', line);
    }

  }, [data, chartType, isEditMode]);

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
            Chart Type
          </label>
          <select
            value={chartType}
            onChange={(e) => onUpdate({
              config: { ...config, chartType: e.target.value as 'd3-bar' | 'd3-line' }
            })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="d3-bar">D3 Bar Chart</option>
            <option value="d3-line">D3 Line Chart</option>
          </select>
        </div>
      </div>
    );
  }

  return (
    <div>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div className="chart-container">
        <svg ref={svgRef}></svg>
      </div>
    </div>
  );
};

export default D3ChartWidget;
