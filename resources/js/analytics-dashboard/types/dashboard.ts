export interface Position {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface WidgetConfig {
  metric?: string;
  showTrend?: boolean;
  showTarget?: boolean;
  chartType?: 'line' | 'bar' | 'pie' | 'doughnut' | 'area' | 'd3-bar' | 'd3-line';
  dataSource?: string;
  refreshInterval?: number;
  content?: string;
  [key: string]: any;
}

export interface WidgetData {
  value?: number;
  change?: number;
  trend?: 'up' | 'down' | 'neutral';
  target?: number;
  labels?: string[];
  datasets?: any[];
  [key: string]: any;
}

export type WidgetType = 'kpi' | 'chart' | 'd3-chart' | 'table' | 'text' | 'image';

export interface Widget {
  id: string;
  type: WidgetType;
  title: string;
  position: Position;
  config: WidgetConfig;
  data?: WidgetData;
}

export interface DashboardConfig {
  id: string;
  name: string;
  layout: 'grid' | 'masonry';
  columns: number;
  widgets?: Widget[];
}

export interface DashboardTemplate {
  id: string;
  name: string;
  description: string;
  role: string;
  config: DashboardConfig;
}

export interface RealTimeUpdate {
  event: string;
  dashboard: string;
  component: string;
  data: WidgetData;
  timestamp: string;
}

export interface ApiResponse<T> {
  status: 'success' | 'error';
  data: T;
  message?: string;
  meta?: {
    last_updated: string;
    data_freshness: string;
    permissions: string[];
  };
}
