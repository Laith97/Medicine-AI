# Advanced Analytics Dashboards - Real-Time Streaming Architecture

## Overview
This document defines the real-time data streaming architecture for the Advanced Analytics Dashboards feature. The architecture enables sub-second data updates for live dashboards while maintaining data consistency and system reliability.

## Architecture Principles

### Design Goals
- **Sub-second Latency**: Data updates within 1-2 seconds of source events
- **Scalability**: Handle 10,000+ concurrent dashboard users
- **Reliability**: 99.9% uptime with automatic failover
- **Consistency**: Eventual consistency with conflict resolution
- **Security**: End-to-end encryption and access controls

### Technology Stack
- **Message Broker**: Apache Kafka for event streaming
- **Stream Processing**: Apache Flink for real-time analytics
- **In-Memory Database**: Redis for caching and session state
- **WebSocket Server**: Socket.IO for real-time dashboard updates
- **Change Data Capture**: Debezium for database event capture

## System Components

### Event Sources
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Application   │    │   Database      │    │   External APIs │
│   Events        │    │   CDC Events    │    │   Webhooks      │
│                 │    │                 │    │                 │
│ • User Actions  │    │ • Table Changes │    │ • Payment Events│
│ • API Calls     │    │ • Insert/Update │    │ • IoT Sensors   │
│ • Form Submissions│  │ • Delete Ops    │    │ • Lab Results   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌────────────────────┐
                    │  Event Ingestion   │
                    │     Layer          │
                    └────────────────────┘
```

### Event Ingestion Layer

#### Apache Kafka Cluster
```yaml
# Kafka Configuration
kafka:
  brokers: 3
  partitions: 12
  replication-factor: 3
  retention-hours: 168
  topics:
    - analytics.events
    - user.activity
    - clinical.updates
    - financial.transactions
    - system.metrics
```

#### Event Schema Definition
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "event_id": {
      "type": "string",
      "format": "uuid"
    },
    "event_type": {
      "type": "string",
      "enum": ["appointment.created", "appointment.completed", "payment.received", "user.login", "clinical.update"]
    },
    "timestamp": {
      "type": "string",
      "format": "date-time"
    },
    "source": {
      "type": "string",
      "enum": ["api", "cdc", "webhook", "system"]
    },
    "data": {
      "type": "object"
    },
    "metadata": {
      "type": "object",
      "properties": {
        "user_id": {"type": "integer"},
        "hospital_id": {"type": "integer"},
        "correlation_id": {"type": "string"}
      }
    }
  },
  "required": ["event_id", "event_type", "timestamp", "source"]
}
```

### Stream Processing Layer

#### Apache Flink Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    Flink Job Cluster                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐  │
│  │  Source         │  │  Processing     │  │  Sink       │  │
│  │  Functions      │  │  Operators      │  │  Functions  │  │
│  │                 │  │                 │  │             │  │
│  │ • Kafka Source  │  │ • Filtering     │  │ • Redis     │  │
│  │ • CDC Source    │  │ • Aggregation   │  │ • Database  │  │
│  │ • API Source    │  │ • Enrichment    │  │ • WebSocket │  │
│  └─────────────────┘  └─────────────────┘  └─────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

#### Processing Pipelines

##### Real-Time KPI Pipeline
```java
// Flink Job for Real-Time KPI Calculation
DataStream<Event> events = env.addSource(new KafkaSource());

DataStream<KPI> kpis = events
    .filter(event -> event.getType().startsWith("appointment.") ||
                     event.getType().startsWith("payment."))
    .keyBy(event -> event.getMetadata().getHospitalId())
    .window(TumblingProcessingTimeWindows.of(Time.minutes(1)))
    .aggregate(new KPIAggregator())
    .map(new KPINormalizer());

kpis.addSink(new RedisSink());
kpis.addSink(new WebSocketSink());
```

##### User Activity Pipeline
```java
// Real-Time User Activity Tracking
DataStream<UserEvent> userEvents = env.addSource(new KafkaSource());

DataStream<UserActivity> activities = userEvents
    .filter(event -> event.getType().startsWith("user."))
    .keyBy(event -> event.getMetadata().getUserId())
    .window(SlidingProcessingTimeWindows.of(Time.minutes(5), Time.minutes(1)))
    .aggregate(new UserActivityAggregator());

activities.addSink(new RedisSink("user_activity"));
```

### Caching and State Management

#### Redis Cluster Configuration
```yaml
redis:
  cluster:
    masters: 3
    replicas: 2
    total_nodes: 9
  databases:
    - name: realtime_kpis
      ttl: 3600
    - name: user_sessions
      ttl: 86400
    - name: dashboard_cache
      ttl: 300
  persistence:
    rdb: enabled
    aof: enabled
```

#### Cache Keys Structure
```
# KPI Cache Keys
kpi:{hospital_id}:{metric}:{time_window}
kpi:123:revenue:daily
kpi:123:patient_satisfaction:realtime

# User Session Keys
session:{user_id}:{dashboard_id}
session:456:executive_dashboard

# Dashboard Cache Keys
dashboard:{user_id}:{dashboard_id}:{component_id}
dashboard:456:executive:revenue_chart
```

### Real-Time Dashboard Updates

#### WebSocket Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Dashboard     │    │  WebSocket      │    │   Redis PubSub  │
│   Client        │◄──►│   Server        │◄──►│   Channel       │
│                 │    │                 │    │                 │
│ • React App     │    │ • Socket.IO     │    │ • KPI Updates   │
│ • Vue.js App    │    │ • Authentication │    │ • User Events  │
│ • Mobile App    │    │ • Rate Limiting  │    │ • Alerts       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

#### WebSocket Message Format
```json
{
  "type": "kpi_update",
  "dashboard_id": "executive",
  "component_id": "revenue_card",
  "data": {
    "value": 125430,
    "change": 12.5,
    "trend": "up",
    "timestamp": "2024-01-15T10:30:00Z"
  },
  "metadata": {
    "user_id": 456,
    "hospital_id": 123,
    "permissions": ["read", "export"]
  }
}
```

### Data Flow Architecture

#### Event Flow Diagram
```
1. Source Event ──► 2. Kafka Topic ──► 3. Flink Processing ──► 4. Redis Cache
       ▲                   │                       │                       │
       │                   ▼                       ▼                       ▼
5. WebSocket ───────► Dashboard Update ─────► UI Refresh ───────► User View
   Broadcast               Real-time               Live Data               KPI
```

#### Detailed Data Pipeline
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Application │     │   Kafka     │     │   Flink     │     │   Redis     │
│  Events     │────►│   Queue     │────►│ Processing │────►│   Cache     │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │                   │
       ▼                   ▼                   ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Database  │     │   Metrics   │     │   Alerts    │     │ WebSocket   │
│   Storage   │     │ Collection  │     │ Generation │     │ Broadcast   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

### Change Data Capture (CDC)

#### Debezium Configuration
```yaml
debezium:
  connectors:
    - name: mysql-connector
      config:
        database.hostname: localhost
        database.port: 3306
        database.user: debezium
        database.password: dbz
        database.server.id: 184054
        database.server.name: mysql
        table.include.list: medicine_ai.appointments,medicine_ai.patient_data
        database.history.kafka.bootstrap.servers: kafka:9092
        database.history.kafka.topic: schema-changes.medicine_ai
```

#### CDC Event Processing
```java
// Process CDC Events
DataStream<CDCEvent> cdcEvents = env.addSource(new DebeziumSource());

DataStream<AnalyticsEvent> analyticsEvents = cdcEvents
    .filter(event -> event.getOperation() != "DELETE")
    .map(new CDCToAnalyticsMapper())
    .filter(event -> isRelevantForAnalytics(event));

analyticsEvents.addSink(new KafkaSink("analytics.events"));
```

### Monitoring and Observability

#### Metrics Collection
- **System Metrics**: CPU, memory, disk I/O, network
- **Application Metrics**: Event throughput, processing latency
- **Business Metrics**: KPI calculation accuracy, dashboard load times
- **Error Metrics**: Failed events, processing errors, data quality issues

#### Alerting System
```yaml
alerting:
  rules:
    - name: high_latency
      condition: 'processing_latency > 5000'
      severity: critical
      channels: [email, slack, pager]
    - name: data_quality_drop
      condition: 'null_values_ratio > 0.05'
      severity: warning
      channels: [email]
    - name: kafka_lag
      condition: 'consumer_lag > 10000'
      severity: warning
      channels: [slack]
```

### Security Architecture

#### Authentication and Authorization
- **JWT Tokens**: For WebSocket connections
- **Role-Based Access**: Dashboard-level permissions
- **Data Filtering**: Row-level security for multi-tenant data
- **Encryption**: TLS 1.3 for all communications

#### Data Protection
- **PII Masking**: Sensitive data anonymization in streams
- **Audit Logging**: All data access and modifications
- **Compliance**: HIPAA and GDPR compliance measures
- **Backup**: Point-in-time recovery capabilities

### Scalability and Performance

#### Horizontal Scaling
- **Kafka**: Add brokers and partitions as needed
- **Flink**: Scale job managers and task managers
- **Redis**: Cluster mode with automatic sharding
- **WebSocket**: Load balancer with sticky sessions

#### Performance Optimization
- **Event Batching**: Group events for efficient processing
- **Caching Strategy**: Multi-level caching (L1, L2, L3)
- **Query Optimization**: Pre-computed aggregations
- **Compression**: Message compression in Kafka

### Disaster Recovery

#### Backup Strategy
- **Data Backup**: Daily snapshots of Redis and Kafka
- **Configuration Backup**: Infrastructure as code
- **Application Backup**: Container images and configurations

#### Failover Procedures
- **Automatic Failover**: Kubernetes-based pod restarts
- **Manual Failover**: Runbooks for critical failures
- **Data Recovery**: Point-in-time restore capabilities
- **Testing**: Regular disaster recovery drills

### Implementation Roadmap

#### Phase 1: Foundation (Week 1-2)
- Set up Kafka cluster
- Configure Debezium CDC
- Basic Flink processing jobs
- Redis caching layer

#### Phase 2: Core Processing (Week 3-4)
- Implement KPI calculation pipelines
- WebSocket server setup
- Basic dashboard integration
- Monitoring and alerting

#### Phase 3: Advanced Features (Week 5-6)
- Complex event processing
- Machine learning integration
- Advanced analytics pipelines
- Performance optimization

#### Phase 4: Production Ready (Week 7-8)
- Security hardening
- Load testing
- Documentation
- Go-live preparation
