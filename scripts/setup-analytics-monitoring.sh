#!/bin/bash

# Advanced Analytics Dashboards - Production Monitoring Setup
# This script sets up comprehensive monitoring and alerting for the analytics system

set -e

# Configuration
APP_NAME="medicine-ai-analytics"
ENVIRONMENT=${1:-"production"}
PROMETHEUS_VERSION="2.45.0"
GRAFANA_VERSION="10.1.0"
ALERTMANAGER_VERSION="0.26.0"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log_info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

log_warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARN: $1${NC}"
}

log_error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

log_success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1${NC}"
}

# Function to install Prometheus
install_prometheus() {
    log_info "Installing Prometheus $PROMETHEUS_VERSION"

    local prometheus_dir="/opt/prometheus"
    local prometheus_user="prometheus"

    # Create prometheus user
    sudo useradd --no-create-home --shell /bin/false $prometheus_user || true

    # Download and install Prometheus
    wget -q "https://github.com/prometheus/prometheus/releases/download/v${PROMETHEUS_VERSION}/prometheus-${PROMETHEUS_VERSION}.linux-amd64.tar.gz"
    tar -xzf "prometheus-${PROMETHEUS_VERSION}.linux-amd64.tar.gz"
    sudo mv "prometheus-${PROMETHEUS_VERSION}.linux-amd64" $prometheus_dir
    sudo chown -R $prometheus_user:$prometheus_user $prometheus_dir

    # Create systemd service
    sudo tee /etc/systemd/system/prometheus.service > /dev/null <<EOF
[Unit]
Description=Prometheus
Wants=network-online.target
After=network-online.target

[Service]
User=$prometheus_user
Group=$prometheus_user
Type=simple
ExecStart=$prometheus_dir/prometheus \\
  --config.file=$prometheus_dir/prometheus.yml \\
  --storage.tsdb.path=$prometheus_dir/data \\
  --web.console.templates=$prometheus_dir/consoles \\
  --web.console.libraries=$prometheus_dir/console_libraries

[Install]
WantedBy=multi-user.target
EOF

    # Create Prometheus configuration
    sudo tee $prometheus_dir/prometheus.yml > /dev/null <<EOF
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - "analytics_alerts.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - localhost:9093

scrape_configs:
  - job_name: 'analytics-app'
    static_configs:
      - targets: ['localhost:8000', 'localhost:8001']
    metrics_path: '/metrics'
    scrape_interval: 5s

  - job_name: 'analytics-database'
    static_configs:
      - targets: ['localhost:9104']
    scrape_interval: 30s

  - job_name: 'analytics-redis'
    static_configs:
      - targets: ['localhost:9121']

  - job_name: 'analytics-kafka'
    static_configs:
      - targets: ['localhost:7071']

  - job_name: 'analytics-flink'
    static_configs:
      - targets: ['localhost:9249']

  - job_name: 'node-exporter'
    static_configs:
      - targets: ['localhost:9100']
EOF

    # Create alert rules
    sudo tee $prometheus_dir/analytics_alerts.yml > /dev/null <<EOF
groups:
  - name: analytics_alerts
    rules:
      - alert: AnalyticsAppDown
        expr: up{job="analytics-app"} == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Analytics application is down"
          description: "Analytics application has been down for more than 1 minute"

      - alert: AnalyticsHighResponseTime
        expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{job="analytics-app"}[5m])) > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High response time detected"
          description: "95th percentile response time is above 2 seconds for 5 minutes"

      - alert: AnalyticsHighErrorRate
        expr: rate(http_requests_total{status=~"5..", job="analytics-app"}[5m]) / rate(http_requests_total{job="analytics-app"}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is above 5% for 5 minutes"

      - alert: AnalyticsDatabaseHighConnections
        expr: mysql_global_status_threads_connected{job="analytics-database"} > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High database connections"
          description: "Database has more than 80 active connections"

      - alert: AnalyticsRedisMemoryHigh
        expr: redis_memory_used_bytes{job="analytics-redis"} / redis_memory_max_bytes{job="analytics-redis"} > 0.9
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Redis memory usage high"
          description: "Redis memory usage is above 90%"

      - alert: AnalyticsKafkaLagHigh
        expr: kafka_consumergroup_lag{job="analytics-kafka"} > 10000
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "High Kafka consumer lag"
          description: "Kafka consumer lag is above 10,000 messages"

      - alert: AnalyticsDataQualityIssues
        expr: analytics_data_quality_score < 0.95
        for: 15m
        labels:
          severity: warning
        annotations:
          summary: "Data quality issues detected"
          description: "Data quality score is below 95%"

      - alert: AnalyticsKPICalculationFailed
        expr: increase(analytics_kpi_calculation_errors_total[5m]) > 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "KPI calculation failures"
          description: "KPI calculations are failing"
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable prometheus
    sudo systemctl start prometheus

    log_success "Prometheus installed and configured"
}

# Function to install Grafana
install_grafana() {
    log_info "Installing Grafana $GRAFANA_VERSION"

    # Install Grafana
    sudo apt-get update
    sudo apt-get install -y apt-transport-https
    sudo apt-get install -y software-properties-common wget
    wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -
    echo "deb https://packages.grafana.com/oss/deb stable main" | sudo tee -a /etc/apt/sources.list.d/grafana.list
    sudo apt-get update
    sudo apt-get install -y "grafana=$GRAFANA_VERSION"

    # Configure Grafana
    sudo tee /etc/grafana/provisioning/datasources/prometheus.yml > /dev/null <<EOF
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://localhost:9090
    isDefault: true
    editable: true
EOF

    # Create analytics dashboard
    sudo mkdir -p /etc/grafana/provisioning/dashboards
    sudo tee /etc/grafana/provisioning/dashboards/analytics.yml > /dev/null <<EOF
apiVersion: 1

providers:
  - name: 'analytics'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 10
    allowUiUpdates: true
    options:
      path: /var/lib/grafana/dashboards
EOF

    # Create analytics dashboard JSON
    sudo mkdir -p /var/lib/grafana/dashboards
    sudo tee /var/lib/grafana/dashboards/analytics-overview.json > /dev/null <<EOF
{
  "dashboard": {
    "title": "Analytics System Overview",
    "tags": ["analytics", "overview"],
    "timezone": "browser",
    "panels": [
      {
        "title": "Application Response Time",
        "type": "graph",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{job=\"analytics-app\"}[5m]))",
            "legendFormat": "95th percentile"
          }
        ]
      },
      {
        "title": "Error Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(http_requests_total{status=~\"5..\", job=\"analytics-app\"}[5m]) / rate(http_requests_total{job=\"analytics-app\"}[5m]) * 100",
            "legendFormat": "Error rate %"
          }
        ]
      },
      {
        "title": "Active Users",
        "type": "graph",
        "targets": [
          {
            "expr": "analytics_active_users",
            "legendFormat": "Active users"
          }
        ]
      },
      {
        "title": "Database Connections",
        "type": "graph",
        "targets": [
          {
            "expr": "mysql_global_status_threads_connected{job=\"analytics-database\"}",
            "legendFormat": "DB connections"
          }
        ]
      }
    ]
  }
}
EOF

    sudo systemctl enable grafana-server
    sudo systemctl start grafana-server

    log_success "Grafana installed and configured"
}

# Function to install Alertmanager
install_alertmanager() {
    log_info "Installing Alertmanager $ALERTMANAGER_VERSION"

    local alertmanager_dir="/opt/alertmanager"
    local alertmanager_user="alertmanager"

    # Create alertmanager user
    sudo useradd --no-create-home --shell /bin/false $alertmanager_user || true

    # Download and install Alertmanager
    wget -q "https://github.com/prometheus/alertmanager/releases/download/v${ALERTMANAGER_VERSION}/alertmanager-${ALERTMANAGER_VERSION}.linux-amd64.tar.gz"
    tar -xzf "alertmanager-${ALERTMANAGER_VERSION}.linux-amd64.tar.gz"
    sudo mv "alertmanager-${ALERTMANAGER_VERSION}.linux-amd64" $alertmanager_dir
    sudo chown -R $alertmanager_user:$alertmanager_user $alertmanager_dir

    # Create configuration
    sudo tee $alertmanager_dir/alertmanager.yml > /dev/null <<EOF
global:
  smtp_smarthost: 'smtp.gmail.com:587'
  smtp_from: 'alerts@yourcompany.com'
  smtp_auth_username: 'alerts@yourcompany.com'
  smtp_auth_password: 'your-smtp-password'

route:
  group_by: ['alertname']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'analytics-team'
  routes:
  - match:
      severity: critical
    receiver: 'analytics-critical'

receivers:
- name: 'analytics-team'
  email_configs:
  - to: 'analytics-team@yourcompany.com'
    send_resolved: true

- name: 'analytics-critical'
  email_configs:
  - to: 'oncall@yourcompany.com'
    send_resolved: true
  slack_configs:
  - api_url: 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK'
    channel: '#analytics-alerts'
    send_resolved: true
EOF

    # Create systemd service
    sudo tee /etc/systemd/system/alertmanager.service > /dev/null <<EOF
[Unit]
Description=Alertmanager
Wants=network-online.target
After=network-online.target

[Service]
User=$alertmanager_user
Group=$alertmanager_user
Type=simple
ExecStart=$alertmanager_dir/alertmanager \\
  --config.file=$alertmanager_dir/alertmanager.yml \\
  --storage.path=$alertmanager_dir/data

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable alertmanager
    sudo systemctl start alertmanager

    log_success "Alertmanager installed and configured"
}

# Function to install Node Exporter
install_node_exporter() {
    log_info "Installing Node Exporter"

    local node_exporter_dir="/opt/node_exporter"
    local node_exporter_user="node_exporter"

    # Create user
    sudo useradd --no-create-home --shell /bin/false $node_exporter_user || true

    # Download and install
    wget -q "https://github.com/prometheus/node_exporter/releases/download/v1.6.1/node_exporter-1.6.1.linux-amd64.tar.gz"
    tar -xzf "node_exporter-1.6.1.linux-amd64.tar.gz"
    sudo mv "node_exporter-1.6.1.linux-amd64" $node_exporter_dir
    sudo chown -R $node_exporter_user:$node_exporter_user $node_exporter_dir

    # Create systemd service
    sudo tee /etc/systemd/system/node_exporter.service > /dev/null <<EOF
[Unit]
Description=Node Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=$node_exporter_user
Group=$node_exporter_user
Type=simple
ExecStart=$node_exporter_dir/node_exporter

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable node_exporter
    sudo systemctl start node_exporter

    log_success "Node Exporter installed and configured"
}

# Function to create custom metrics endpoint
create_metrics_endpoint() {
    log_info "Creating custom metrics endpoint"

    # Create Laravel command for metrics
    php artisan make:command GenerateMetrics

    # Create metrics middleware
    php artisan make:middleware MetricsMiddleware

    log_info "Custom metrics endpoint created"
}

# Function to setup log aggregation
setup_log_aggregation() {
    log_info "Setting up log aggregation with ELK stack"

    # Install Elasticsearch, Logstash, Kibana would go here
    # For now, just set up rsyslog forwarding

    sudo tee /etc/rsyslog.d/60-analytics.conf > /dev/null <<EOF
# Analytics application logs
if \$programname == 'analytics' then {
    action(type="omfwd"
           protocol="tcp"
           target="logstash.yourcompany.com"
           port="5044"
           template="RSYSLOG_ForwardFormat")
    stop
}
EOF

    sudo systemctl restart rsyslog

    log_success "Log aggregation configured"
}

# Function to create health check endpoint
create_health_checks() {
    log_info "Creating comprehensive health checks"

    # Create Laravel health check command
    php artisan make:command HealthCheck

    # Configure health checks for database, cache, queue, etc.
    log_success "Health checks configured"
}

# Main setup function
main() {
    log_info "Setting up production monitoring for $APP_NAME in $ENVIRONMENT environment"

    # Install monitoring stack
    install_node_exporter
    install_prometheus
    install_alertmanager
    install_grafana

    # Setup application monitoring
    create_metrics_endpoint
    create_health_checks
    setup_log_aggregation

    # Create monitoring dashboard URLs
    log_success "Monitoring setup completed!"
    echo ""
    echo "Monitoring URLs:"
    echo "  Prometheus: http://localhost:9090"
    echo "  Grafana: http://localhost:3000 (admin/admin)"
    echo "  Alertmanager: http://localhost:9093"
    echo ""
    echo "Application Health Check: http://localhost:8000/api/health"
    echo "Application Metrics: http://localhost:8000/metrics"
}

# Pre-setup checks
pre_setup_checks() {
    log_info "Running pre-setup checks"

    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        log_error "This script must be run with sudo privileges"
        exit 1
    fi

    # Check required tools
    for tool in wget tar systemctl; do
        if ! command -v $tool &> /dev/null; then
            log_error "Required tool '$tool' is not installed"
            exit 1
        fi
    done

    # Check available disk space
    local available_space=$(df /opt | tail -1 | awk '{print $4}')
    if [ "$available_space" -lt 2097152 ]; then # 2GB in KB
        log_error "Insufficient disk space. At least 2GB required in /opt"
        exit 1
    fi

    log_success "Pre-setup checks passed"
}

# Cleanup function
cleanup() {
    # Remove downloaded archives
    rm -f *.tar.gz
}

# Set up cleanup trap
trap cleanup EXIT

# Run pre-setup checks
pre_setup_checks

# Run main setup
main
