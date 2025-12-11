# Advanced Analytics Dashboards - API Endpoint Specifications

## Overview
This document defines the REST API endpoints for the Advanced Analytics Dashboards feature. All endpoints follow RESTful conventions and include proper authentication, authorization, and error handling.

## Authentication & Authorization

### Authentication
- **Type**: Bearer Token (JWT)
- **Header**: `Authorization: Bearer {token}`
- **Token Expiry**: 8 hours
- **Refresh Token**: Available for session extension

### Authorization
- **Middleware**: `auth:sanctum`, `analytics.access`
- **Role-based**: Permissions checked per endpoint
- **Data Filtering**: Automatic row-level security application

## Base URL
```
https://api.medicine-ai.com/v1/analytics
```

## Dashboard Endpoints

### Executive Dashboard

#### Get Executive Dashboard Data
```http
GET /analytics/dashboard/executive
```

**Parameters:**
- `date_range` (optional): `7d`, `30d`, `90d`, `1y`, `custom`
- `start_date` (optional): ISO 8601 date string
- `end_date` (optional): ISO 8601 date string
- `hospital_id` (optional): Filter by hospital (admin only)

**Response:**
```json
{
  "status": "success",
  "data": {
    "summary": {
      "revenue": {
        "value": 125430,
        "change": 12.5,
        "trend": "up",
        "target": 130000
      },
      "patient_satisfaction": {
        "value": 4.8,
        "change": 0.3,
        "trend": "up",
        "target": 4.9
      },
      "operational_efficiency": {
        "value": 94.2,
        "change": -2.1,
        "trend": "down",
        "target": 95.0
      },
      "clinical_outcomes": {
        "value": 87.3,
        "change": 5.2,
        "trend": "up",
        "target": 90.0
      }
    },
    "charts": {
      "revenue_trend": {
        "labels": ["Jan", "Feb", "Mar", "Apr", "May"],
        "data": [95000, 105000, 115000, 120000, 125430]
      },
      "patient_satisfaction_distribution": {
        "labels": ["1★", "2★", "3★", "4★", "5★"],
        "data": [5, 15, 25, 35, 20]
      }
    },
    "alerts": [
      {
        "id": "alert_123",
        "type": "warning",
        "message": "Patient satisfaction below target",
        "metric": "patient_satisfaction",
        "threshold": 4.9,
        "current_value": 4.8
      }
    ]
  },
  "meta": {
    "last_updated": "2024-01-15T10:30:00Z",
    "data_freshness": "realtime",
    "permissions": ["read", "export"]
  }
}
```

**Permissions Required:** `analytics.dashboard.executive.read`

### Revenue Dashboard

#### Get Revenue Analytics
```http
GET /analytics/revenue/overview
```

**Parameters:**
- `period`: `daily`, `weekly`, `monthly`, `quarterly`
- `group_by`: `plan_type`, `payment_method`, `hospital`
- `include_forecast`: `true`/`false`

**Response:**
```json
{
  "status": "success",
  "data": {
    "kpis": {
      "mrr": {"value": 125430, "change": 12.5},
      "arpu": {"value": 89.50, "change": 8.2},
      "churn_rate": {"value": 3.2, "change": -0.5},
      "clv": {"value": 2340, "change": 15.3}
    },
    "breakdown": {
      "by_plan": [
        {"plan": "Premium", "revenue": 75000, "percentage": 60},
        {"plan": "Standard", "revenue": 37500, "percentage": 30},
        {"plan": "Basic", "revenue": 12930, "percentage": 10}
      ],
      "by_payment_method": [
        {"method": "Credit Card", "revenue": 87500, "percentage": 70},
        {"method": "Insurance", "revenue": 25000, "percentage": 20},
        {"method": "ACH", "revenue": 12930, "percentage": 10}
      ]
    },
    "forecast": {
      "next_month": 138000,
      "confidence": 85,
      "trend": "up"
    }
  }
}
```

**Permissions Required:** `analytics.revenue.read`

#### Get Revenue Trends
```http
GET /analytics/revenue/trends
```

**Parameters:**
- `metric`: `revenue`, `arpu`, `churn`, `new_customers`
- `period`: `daily`, `weekly`, `monthly`
- `date_range`: Date range specification

### Patient Experience Dashboard

#### Get Patient Satisfaction Metrics
```http
GET /analytics/patients/satisfaction
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "overall": {
      "nps": 72,
      "satisfaction_score": 4.8,
      "response_rate": 85.5
    },
    "by_department": [
      {
        "department": "Cardiology",
        "satisfaction": 4.9,
        "response_count": 245
      },
      {
        "department": "Orthopedics",
        "satisfaction": 4.7,
        "response_count": 189
      }
    ],
    "trends": {
      "labels": ["Week 1", "Week 2", "Week 3", "Week 4"],
      "data": [4.6, 4.7, 4.8, 4.8]
    }
  }
}
```

#### Get Appointment Analytics
```http
GET /analytics/appointments/analytics
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "show_up_rate": 89.4,
    "average_wait_time": 12.3,
    "cancellation_rate": 8.2,
    "reschedule_rate": 5.1,
    "by_time_slot": [
      {"slot": "9:00 AM", "utilization": 95, "satisfaction": 4.8},
      {"slot": "10:00 AM", "utilization": 88, "satisfaction": 4.7}
    ]
  }
}
```

### Operations Dashboard

#### Get Operational Metrics
```http
GET /analytics/operations/metrics
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "provider_utilization": 94.2,
    "average_appointment_duration": 28,
    "patient_throughput": 42,
    "administrative_burden": 23,
    "room_utilization": [
      {"room": "Exam Room 1", "utilization": 87},
      {"room": "Exam Room 2", "utilization": 92}
    ]
  }
}
```

#### Get Resource Utilization
```http
GET /analytics/operations/resources
```

**Parameters:**
- `resource_type`: `providers`, `rooms`, `equipment`
- `time_range`: Time period for analysis

### Clinical Dashboard

#### Get Clinical Outcomes
```http
GET /analytics/clinical/outcomes
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "recovery_rate": 87.3,
    "readmission_rate": 4.1,
    "treatment_success": 91.7,
    "preventive_care_compliance": 76.4,
    "by_condition": [
      {
        "condition": "Hypertension",
        "success_rate": 89.5,
        "patient_count": 1250
      },
      {
        "condition": "Diabetes",
        "success_rate": 85.2,
        "patient_count": 890
      }
    ]
  }
}
```

#### Get Quality Measures
```http
GET /analytics/clinical/quality
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "guideline_adherence": 92.3,
    "diagnostic_accuracy": 94.1,
    "safety_indicators": {
      "adverse_events": 0.8,
      "infection_rate": 0.3,
      "complication_rate": 2.1
    }
  }
}
```

## Real-Time Data Endpoints

### WebSocket Connection
```javascript
// Client-side connection
const socket = io('https://api.medicine-ai.com/analytics', {
  auth: {
    token: 'jwt_token_here'
  }
});

// Subscribe to dashboard updates
socket.emit('subscribe_dashboard', {
  dashboard_id: 'executive',
  components: ['revenue_card', 'satisfaction_chart']
});

// Listen for updates
socket.on('dashboard_update', (data) => {
  console.log('Real-time update:', data);
});
```

### Real-Time KPI Stream
```http
GET /analytics/realtime/kpi-stream
```

**WebSocket Events:**
```json
{
  "event": "kpi_update",
  "dashboard": "executive",
  "component": "revenue_card",
  "data": {
    "value": 126000,
    "change": 13.2,
    "timestamp": "2024-01-15T10:31:00Z"
  }
}
```

## Export Endpoints

### Export Dashboard Data
```http
POST /analytics/export/dashboard
```

**Request Body:**
```json
{
  "dashboard": "executive",
  "format": "pdf",
  "date_range": {
    "start": "2024-01-01",
    "end": "2024-01-15"
  },
  "include_charts": true,
  "include_raw_data": false
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "export_id": "export_12345",
    "status": "processing",
    "estimated_completion": "2024-01-15T10:32:00Z",
    "download_url": "/analytics/export/download/export_12345"
  }
}
```

### Download Export
```http
GET /analytics/export/download/{export_id}
```

**Response:** Binary file download

## Alert Management Endpoints

### Get Alerts
```http
GET /analytics/alerts
```

**Parameters:**
- `status`: `active`, `acknowledged`, `resolved`
- `severity`: `low`, `medium`, `high`, `critical`
- `type`: Alert type filter

**Response:**
```json
{
  "status": "success",
  "data": {
    "alerts": [
      {
        "id": "alert_123",
        "type": "kpi_threshold",
        "severity": "high",
        "message": "Patient satisfaction dropped below 4.5",
        "metric": "patient_satisfaction",
        "threshold": 4.5,
        "current_value": 4.3,
        "created_at": "2024-01-15T10:00:00Z",
        "status": "active"
      }
    ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 45
    }
  }
}
```

### Acknowledge Alert
```http
POST /analytics/alerts/{alert_id}/acknowledge
```

**Request Body:**
```json
{
  "comment": "Investigating the cause of satisfaction drop"
}
```

### Configure Alert Rules
```http
POST /analytics/alerts/rules
```

**Request Body:**
```json
{
  "name": "Patient Satisfaction Alert",
  "metric": "patient_satisfaction",
  "condition": "less_than",
  "threshold": 4.5,
  "severity": "high",
  "notification_channels": ["email", "dashboard"],
  "cooldown_minutes": 60
}
```

## Configuration Endpoints

### Get Dashboard Configuration
```http
GET /analytics/dashboard/{dashboard_id}/config
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "dashboard": {
      "id": "executive",
      "name": "Executive Dashboard",
      "components": [
        {
          "id": "revenue_card",
          "type": "kpi_card",
          "position": {"x": 0, "y": 0, "width": 3, "height": 2},
          "config": {
            "metric": "revenue",
            "show_trend": true,
            "show_target": true
          }
        }
      ]
    }
  }
}
```

### Update Dashboard Configuration
```http
PUT /analytics/dashboard/{dashboard_id}/config
```

**Request Body:**
```json
{
  "components": [
    {
      "id": "revenue_card",
      "position": {"x": 0, "y": 0, "width": 4, "height": 2}
    }
  ]
}
```

## Administrative Endpoints

### Get System Health
```http
GET /analytics/admin/health
```

**Permissions Required:** `admin`

**Response:**
```json
{
  "status": "success",
  "data": {
    "overall_health": "healthy",
    "components": {
      "database": {"status": "healthy", "latency": 12},
      "cache": {"status": "healthy", "hit_rate": 94.5},
      "streaming": {"status": "healthy", "throughput": 1250},
      "api": {"status": "healthy", "response_time": 45}
    },
    "metrics": {
      "active_users": 1250,
      "queries_per_second": 450,
      "data_freshness": "2s"
    }
  }
}
```

### Clear Analytics Cache
```http
POST /analytics/admin/cache/clear
```

**Permissions Required:** `admin`

### Get Usage Statistics
```http
GET /analytics/admin/usage
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "daily_active_users": 1250,
    "total_queries": 45000,
    "average_response_time": 45,
    "error_rate": 0.02,
    "most_popular_dashboards": [
      {"dashboard": "executive", "views": 8500},
      {"dashboard": "patient_experience", "views": 6200}
    ]
  }
}
```

## Error Handling

### Standard Error Response
```json
{
  "status": "error",
  "message": "Insufficient permissions to access revenue dashboard",
  "code": "INSUFFICIENT_PERMISSIONS",
  "details": {
    "required_permission": "analytics.revenue.read",
    "user_permissions": ["analytics.dashboard.executive.read"]
  }
}
```

### Common Error Codes
- `INSUFFICIENT_PERMISSIONS`: User lacks required permissions
- `INVALID_PARAMETERS`: Request parameters are invalid
- `DATA_NOT_FOUND`: Requested data does not exist
- `RATE_LIMIT_EXCEEDED`: API rate limit exceeded
- `SERVICE_UNAVAILABLE`: Analytics service temporarily unavailable

## Rate Limiting

### Rate Limits by Endpoint Type
- **Dashboard Data**: 100 requests per minute per user
- **Real-time Subscriptions**: 10 concurrent connections per user
- **Export Requests**: 5 requests per hour per user
- **Administrative**: 50 requests per minute per admin user

### Rate Limit Headers
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
X-RateLimit-Retry-After: 60
```

## Versioning

### API Versioning Strategy
- **URL Path Versioning**: `/v1/analytics/...`
- **Backward Compatibility**: Maintain for 2 major versions
- **Deprecation Notices**: 3 months advance notice
- **Sunset Policy**: 6 months after deprecation

### Version Headers
```
Accept-Version: v1
X-API-Version: v1.2.3
```

## Caching Strategy

### Response Caching
- **Dashboard Data**: 30 seconds cache
- **Static Configuration**: 1 hour cache
- **Historical Data**: 5 minutes cache
- **Real-time Data**: No cache

### Cache Headers
```
Cache-Control: private, max-age=30
ETag: "abc123"
Last-Modified: Wed, 15 Jan 2024 10:30:00 GMT
```

## Monitoring and Logging

### Request Logging
- All API requests logged with user ID, timestamp, endpoint, response time
- Error responses logged with stack traces
- Performance metrics collected for optimization

### Analytics Tracking
- Dashboard usage patterns tracked
- Feature adoption metrics collected
- User behavior analytics for UX improvement
