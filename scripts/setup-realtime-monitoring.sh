#!/bin/bash

# Real-time Features Monitoring and Alerting Setup
# Production monitoring for broadcasting and real-time appointment features

set -e

# Configuration
APP_NAME="medicine-ai-realtime"
MONITORING_DIR="/opt/monitoring/${APP_NAME}"
LOG_DIR="/var/log/${APP_NAME}"
ALERT_EMAIL=${ALERT_EMAIL:-"admin@medicine-ai.com"}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Install monitoring dependencies
install_monitoring_dependencies() {
    log "Installing monitoring dependencies..."

    # Install Prometheus Node Exporter if not present
    if ! command -v node_exporter >/dev/null 2>&1; then
        log "Installing Prometheus Node Exporter..."
        wget -q https://github.com/prometheus/node_exporter/releases/download/v1.3.1/node_exporter-1.3.1.linux-amd64.tar.gz
        tar -xzf node_exporter-1.3.1.linux-amd64.tar.gz
        sudo mv node_exporter-1.3.1.linux-amd64/node_exporter /usr/local/bin/
        sudo useradd -rs /bin/false node_exporter
        rm -rf node_exporter-1.3.1.linux-amd64*

        # Create systemd service
        sudo tee /etc/systemd/system/node_exporter.service > /dev/null <<EOF
[Unit]
Description=Node Exporter
After=network.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF

        sudo systemctl daemon-reload
        sudo systemctl enable node_exporter
        sudo systemctl start node_exporter
    fi

    # Install monitoring tools
    sudo apt-get update
    sudo apt-get install -y bc jq curl wget

    success "Monitoring dependencies installed"
}

# Create monitoring directories
create_monitoring_directories() {
    log "Creating monitoring directories..."

    sudo mkdir -p "$MONITORING_DIR"
    sudo mkdir -p "$LOG_DIR"
    sudo mkdir -p /var/lib/prometheus
    sudo mkdir -p /etc/prometheus

    # Set permissions
    sudo chown -R $USER:$USER "$MONITORING_DIR"
    sudo chown -R $USER:$USER "$LOG_DIR"

    success "Monitoring directories created"
}

# Create monitoring scripts
create_monitoring_scripts() {
    log "Creating monitoring scripts..."

    # Real-time features health check script
    cat > "$MONITORING_DIR/health_check.sh" <<'EOF'
#!/bin/bash

# Real-time Features Health Check Script

HEALTH_LOG="/var/log/medicine-ai-realtime/health_check.log"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Configuration
APP_URL="http://localhost"
PUSHER_TIMEOUT=10
REDIS_TIMEOUT=5

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$HEALTH_LOG"
}

check_web_application() {
    local status=0

    # Check if application is responding
    if ! curl -f -s --max-time 10 "$APP_URL/health" >/dev/null 2>&1; then
        log "ERROR: Web application health check failed"
        status=1
    else
        log "OK: Web application is responding"
    fi

    return $status
}

check_database() {
    local status=0

    # Check database connectivity
    if ! php artisan db:monitor >/dev/null 2>&1; then
        log "ERROR: Database connectivity check failed"
        status=1
    else
        log "OK: Database is accessible"
    fi

    return $status
}

check_redis() {
    local status=0

    # Check Redis connectivity
    if ! timeout $REDIS_TIMEOUT redis-cli ping >/dev/null 2>&1; then
        log "ERROR: Redis connectivity check failed"
        status=1
    else
        log "OK: Redis is accessible"
    fi

    return $status
}

check_pusher() {
    local status=0

    # Check Pusher connectivity
    if ! timeout $PUSHER_TIMEOUT php artisan pusher:test >/dev/null 2>&1; then
        log "ERROR: Pusher connectivity check failed"
        status=1
    else
        log "OK: Pusher is accessible"
    fi

    return $status
}

check_queue_workers() {
    local status=0
    local worker_count=$(pgrep -f "php artisan queue:work" | wc -l)

    if [ "$worker_count" -lt 2 ]; then
        log "ERROR: Insufficient queue workers running ($worker_count)"
        status=1
    else
        log "OK: $worker_count queue workers running"
    fi

    return $status
}

check_broadcasting_performance() {
    local status=0

    # Check broadcasting performance metrics
    local metrics=$(php artisan tinker --execute="
        \$service = app(\App\Services\RealtimePerformanceMonitoringService::class);
        \$metrics = \$service->getBroadcastMetrics();
        echo json_encode(\$metrics);
    " 2>/dev/null)

    if [ $? -ne 0 ]; then
        log "ERROR: Failed to retrieve broadcasting metrics"
        status=1
    else
        # Parse metrics and check thresholds
        local avg_latency=$(echo "$metrics" | jq -r '.average_latency // 0')
        local success_rate=$(echo "$metrics" | jq -r '.success_rate // 0')

        if (( $(echo "$avg_latency > 1000" | bc -l 2>/dev/null) )); then
            log "WARNING: High broadcasting latency ($avg_latency ms)"
        fi

        if (( $(echo "$success_rate < 0.95" | bc -l 2>/dev/null) )); then
            log "ERROR: Low broadcasting success rate ($success_rate)"
            status=1
        else
            log "OK: Broadcasting performance acceptable (latency: ${avg_latency}ms, success: ${success_rate})"
        fi
    fi

    return $status
}

check_system_resources() {
    local status=0

    # Check memory usage
    local memory_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    if [ "$memory_usage" -gt 90 ]; then
        log "ERROR: High memory usage ($memory_usage%)"
        status=1
    elif [ "$memory_usage" -gt 80 ]; then
        log "WARNING: Elevated memory usage ($memory_usage%)"
    else
        log "OK: Memory usage normal ($memory_usage%)"
    fi

    # Check disk usage
    local disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ "$disk_usage" -gt 90 ]; then
        log "ERROR: High disk usage ($disk_usage%)"
        status=1
    elif [ "$disk_usage" -gt 80 ]; then
        log "WARNING: Elevated disk usage ($disk_usage%)"
    else
        log "OK: Disk usage normal ($disk_usage%)"
    fi

    # Check CPU load
    local cpu_load=$(uptime | awk -F'load average:' '{ print $2 }' | cut -d, -f1 | xargs)
    local cpu_cores=$(nproc)
    local cpu_load_percent=$(echo "scale=2; $cpu_load / $cpu_cores * 100" | bc -l 2>/dev/null)

    if (( $(echo "$cpu_load_percent > 80" | bc -l 2>/dev/null) )); then
        log "ERROR: High CPU load ($cpu_load_percent%)"
        status=1
    elif (( $(echo "$cpu_load_percent > 60" | bc -l 2>/dev/null) )); then
        log "WARNING: Elevated CPU load ($cpu_load_percent%)"
    else
        log "OK: CPU load normal ($cpu_load_percent%)"
    fi

    return $status
}

# Main health check
main() {
    local overall_status=0

    log "Starting real-time features health check"

    check_web_application || overall_status=1
    check_database || overall_status=1
    check_redis || overall_status=1
    check_pusher || overall_status=1
    check_queue_workers || overall_status=1
    check_broadcasting_performance || overall_status=1
    check_system_resources || overall_status=1

    if [ $overall_status -eq 0 ]; then
        log "HEALTH CHECK PASSED"
        echo "OK"
    else
        log "HEALTH CHECK FAILED"
        echo "CRITICAL"
    fi

    return $overall_status
}

main "$@"
EOF

    # Performance monitoring script
    cat > "$MONITORING_DIR/performance_monitor.sh" <<'EOF'
#!/bin/bash

# Real-time Features Performance Monitoring Script

PERF_LOG="/var/log/medicine-ai-realtime/performance.log"
METRICS_FILE="/var/lib/prometheus/realtime_metrics.prom"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$PERF_LOG"
}

# Collect broadcasting metrics
collect_broadcasting_metrics() {
    log "Collecting broadcasting metrics..."

    local metrics=$(php artisan tinker --execute="
        try {
            \$service = app(\App\Services\RealtimePerformanceMonitoringService::class);
            \$metrics = \$service->getBroadcastMetrics();
            echo json_encode(\$metrics);
        } catch (Exception \$e) {
            echo '{}';
        }
    " 2>/dev/null)

    # Parse and export metrics
    local total_broadcasts=$(echo "$metrics" | jq -r '.total_broadcasts // 0')
    local successful_broadcasts=$(echo "$metrics" | jq -r '.successful_broadcasts // 0')
    local average_latency=$(echo "$metrics" | jq -r '.average_latency // 0')
    local success_rate=$(echo "$metrics" | jq -r '.success_rate // 0')

    cat >> "$METRICS_FILE" << METRICS_EOF
# HELP realtime_broadcasts_total Total number of broadcast operations
# TYPE realtime_broadcasts_total counter
realtime_broadcasts_total $total_broadcasts

# HELP realtime_broadcasts_successful Number of successful broadcast operations
# TYPE realtime_broadcasts_successful counter
realtime_broadcasts_successful $successful_broadcasts

# HELP realtime_broadcast_latency_ms Average broadcast latency in milliseconds
# TYPE realtime_broadcast_latency_ms gauge
realtime_broadcast_latency_ms $average_latency

# HELP realtime_broadcast_success_rate Broadcast success rate (0.0-1.0)
# TYPE realtime_broadcast_success_rate gauge
realtime_broadcast_success_rate $success_rate
METRICS_EOF
}

# Collect subscription metrics
collect_subscription_metrics() {
    log "Collecting subscription metrics..."

    local broadcast_service=$(php artisan tinker --execute="
        try {
            \$service = app(\App\Services\AppointmentBroadcastService::class);
            \$stats = \$service->getSubscriptionStats();
            echo json_encode(\$stats);
        } catch (Exception \$e) {
            echo '{}';
        }
    " 2>/dev/null)

    local streaming_service=$(php artisan tinker --execute="
        try {
            \$service = app(\App\Services\RealtimeStreamingService::class);
            \$stats = \$service->getSubscriptionStats();
            echo json_encode(\$stats);
        } catch (Exception \$e) {
            echo '{}';
        }
    " 2>/dev/null)

    local broadcast_subscriptions=$(echo "$broadcast_service" | jq -r '.total_active_subscriptions // 0')
    local streaming_subscriptions=$(echo "$streaming_service" | jq -r '.total_active_subscriptions // 0')

    cat >> "$METRICS_FILE" << METRICS_EOF

# HELP realtime_broadcast_subscriptions_total Total active broadcast subscriptions
# TYPE realtime_broadcast_subscriptions_total gauge
realtime_broadcast_subscriptions_total $broadcast_subscriptions

# HELP realtime_streaming_subscriptions_total Total active streaming subscriptions
# TYPE realtime_streaming_subscriptions_total gauge
realtime_streaming_subscriptions_total $streaming_subscriptions
METRICS_EOF
}

# Collect queue metrics
collect_queue_metrics() {
    log "Collecting queue metrics..."

    local queue_size=$(php artisan queue:monitor | grep -oP '(?<=Size: )\d+' | head -1 || echo "0")
    local failed_jobs=$(php artisan queue:failed | wc -l 2>/dev/null || echo "0")

    cat >> "$METRICS_FILE" << METRICS_EOF

# HELP realtime_queue_size Current queue size
# TYPE realtime_queue_size gauge
realtime_queue_size ${queue_size:-0}

# HELP realtime_failed_jobs_total Total failed queue jobs
# TYPE realtime_failed_jobs_total counter
realtime_failed_jobs_total ${failed_jobs:-0}
METRICS_EOF
}

# Main performance monitoring
main() {
    # Clear previous metrics
    > "$METRICS_FILE"

    log "Starting performance metrics collection"

    collect_broadcasting_metrics
    collect_subscription_metrics
    collect_queue_metrics

    log "Performance metrics collection completed"
}

main "$@"
EOF

    # Alerting script
    cat > "$MONITORING_DIR/alert_manager.sh" <<'EOF'
#!/bin/bash

# Real-time Features Alert Manager

ALERT_LOG="/var/log/medicine-ai-realtime/alerts.log"
ALERT_STATE_FILE="/var/lib/medicine-ai-realtime/alert_state.json"

# Configuration
ALERT_EMAIL="admin@medicine-ai.com"
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"
PAGERDUTY_INTEGRATION_KEY="${PAGERDUTY_INTEGRATION_KEY:-}"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$ALERT_LOG"
}

send_email_alert() {
    local subject="$1"
    local message="$2"

    if command -v mail >/dev/null 2>&1; then
        echo "$message" | mail -s "$subject" "$ALERT_EMAIL"
        log "Email alert sent: $subject"
    else
        log "ERROR: mail command not available for email alerts"
    fi
}

send_slack_alert() {
    local message="$1"

    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        curl -X POST "$SLACK_WEBHOOK_URL" \
            -H 'Content-type: application/json' \
            -d "{\"text\":\"$message\"}" \
            >/dev/null 2>&1
        log "Slack alert sent"
    fi
}

send_pagerduty_alert() {
    local summary="$1"
    local severity="$2"

    if [[ -n "$PAGERDUTY_INTEGRATION_KEY" ]]; then
        curl -X POST "https://events.pagerduty.com/v2/enqueue" \
            -H "Content-Type: application/json" \
            -d "{
                \"routing_key\": \"$PAGERDUTY_INTEGRATION_KEY\",
                \"event_action\": \"trigger\",
                \"payload\": {
                    \"summary\": \"$summary\",
                    \"severity\": \"$severity\",
                    \"source\": \"medicine-ai-realtime\"
                }
            }" >/dev/null 2>&1
        log "PagerDuty alert sent"
    fi
}

check_and_alert() {
    local check_name="$1"
    local status="$2"
    local details="$3"

    # Read current alert state
    local alert_key="${check_name}_alert_sent"
    local last_alert_time=0

    if [[ -f "$ALERT_STATE_FILE" ]]; then
        last_alert_time=$(jq -r ".${alert_key} // 0" "$ALERT_STATE_FILE" 2>/dev/null || echo "0")
    fi

    local current_time=$(date +%s)
    local time_since_last_alert=$((current_time - last_alert_time))

    # Only send alert if status is critical and enough time has passed (5 minutes)
    if [[ "$status" == "CRITICAL" && $time_since_last_alert -gt 300 ]]; then
        local alert_message="🚨 ALERT: $check_name - $details

Time: $(date)
Status: $status
Details: $details

Please check the application logs and system status."

        log "Sending alert for $check_name: $status"

        send_email_alert "CRITICAL: $check_name Alert" "$alert_message"
        send_slack_alert "$alert_message"
        send_pagerduty_alert "$check_name failure" "critical"

        # Update alert state
        mkdir -p "$(dirname "$ALERT_STATE_FILE")"
        if [[ -f "$ALERT_STATE_FILE" ]]; then
            jq ".${alert_key} = $current_time" "$ALERT_STATE_FILE" > "${ALERT_STATE_FILE}.tmp" && mv "${ALERT_STATE_FILE}.tmp" "$ALERT_STATE_FILE"
        else
            echo "{\"${alert_key}\": $current_time}" > "$ALERT_STATE_FILE"
        fi
    elif [[ "$status" == "OK" ]]; then
        # Clear alert state on recovery
        if [[ -f "$ALERT_STATE_FILE" ]]; then
            jq "del(.${alert_key})" "$ALERT_STATE_FILE" > "${ALERT_STATE_FILE}.tmp" && mv "${ALERT_STATE_FILE}.tmp" "$ALERT_STATE_FILE"
        fi
    fi
}

# Main alerting function
main() {
    local health_status
    health_status=$("$MONITORING_DIR/health_check.sh")

    case "$health_status" in
        "OK")
            check_and_alert "health_check" "OK" "All systems operational"
            ;;
        "CRITICAL")
            check_and_alert "health_check" "CRITICAL" "One or more systems are failing. Check logs for details."
            ;;
        *)
            log "Unknown health status: $health_status"
            ;;
    esac
}

main "$@"
EOF

    # Make scripts executable
    chmod +x "$MONITORING_DIR"/*.sh

    success "Monitoring scripts created"
}

# Setup Prometheus configuration
setup_prometheus_config() {
    log "Setting up Prometheus configuration..."

    cat > /etc/prometheus/realtime_monitoring.yml <<EOF
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - "realtime_alerts.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - localhost:9093

scrape_configs:
  - job_name: 'realtime_features'
    static_configs:
      - targets: ['localhost:8000']
    metrics_path: '/metrics'
    scrape_interval: 30s

  - job_name: 'node'
    static_configs:
      - targets: ['localhost:9100']
EOF

    cat > /etc/prometheus/realtime_alerts.yml <<EOF
groups:
  - name: realtime_alerts
    rules:
      - alert: HighBroadcastLatency
        expr: realtime_broadcast_latency_ms > 2000
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High broadcasting latency detected"
          description: "Broadcast latency is {{ \$value }}ms, above 2000ms threshold"

      - alert: LowBroadcastSuccessRate
        expr: realtime_broadcast_success_rate < 0.9
        for: 10m
        labels:
          severity: critical
        annotations:
          summary: "Low broadcast success rate"
          description: "Broadcast success rate is {{ \$value }}, below 90% threshold"

      - alert: HighQueueSize
        expr: realtime_queue_size > 1000
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High queue size detected"
          description: "Queue size is {{ \$value }}, above 1000 threshold"

      - alert: FailedJobsIncreasing
        expr: increase(realtime_failed_jobs_total[10m]) > 50
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Increasing failed jobs"
          description: "Failed jobs increased by {{ \$value }} in the last 10 minutes"
EOF

    success "Prometheus configuration created"
}

# Setup cron jobs for monitoring
setup_cron_jobs() {
    log "Setting up cron jobs for monitoring..."

    # Add cron jobs
    (crontab -l 2>/dev/null; echo "# Real-time features monitoring") | crontab -
    (crontab -l 2>/dev/null; echo "*/5 * * * * $MONITORING_DIR/health_check.sh") | crontab -
    (crontab -l 2>/dev/null; echo "*/1 * * * * $MONITORING_DIR/performance_monitor.sh") | crontab -
    (crontab -l 2>/dev/null; echo "*/5 * * * * $MONITORING_DIR/alert_manager.sh") | crontab -

    success "Cron jobs configured"
}

# Setup log rotation
setup_log_rotation() {
    log "Setting up log rotation..."

    cat > /etc/logrotate.d/medicine-ai-realtime <<EOF
/var/log/medicine-ai-realtime/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2 >/dev/null 2>&1 || true
        systemctl reload nginx >/dev/null 2>&1 || true
    endscript
}
EOF

    success "Log rotation configured"
}

# Create dashboard configuration
create_dashboard_config() {
    log "Creating Grafana dashboard configuration..."

    cat > "$MONITORING_DIR/grafana_dashboard.json" <<'EOF'
{
  "dashboard": {
    "title": "Real-time Features Monitoring",
    "tags": ["medicine-ai", "realtime"],
    "timezone": "browser",
    "panels": [
      {
        "title": "Broadcast Success Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "realtime_broadcast_success_rate",
            "legendFormat": "Success Rate"
          }
        ]
      },
      {
        "title": "Broadcast Latency",
        "type": "graph",
        "targets": [
          {
            "expr": "realtime_broadcast_latency_ms",
            "legendFormat": "Latency (ms)"
          }
        ]
      },
      {
        "title": "Active Subscriptions",
        "type": "graph",
        "targets": [
          {
            "expr": "realtime_broadcast_subscriptions_total",
            "legendFormat": "Broadcast Subscriptions"
          },
          {
            "expr": "realtime_streaming_subscriptions_total",
            "legendFormat": "Streaming Subscriptions"
          }
        ]
      },
      {
        "title": "Queue Status",
        "type": "graph",
        "targets": [
          {
            "expr": "realtime_queue_size",
            "legendFormat": "Queue Size"
          },
          {
            "expr": "realtime_failed_jobs_total",
            "legendFormat": "Failed Jobs"
          }
        ]
      }
    ]
  }
}
EOF

    success "Grafana dashboard configuration created"
}

# Main setup function
main() {
    log "Starting real-time features monitoring setup..."

    install_monitoring_dependencies
    create_monitoring_directories
    create_monitoring_scripts
    setup_prometheus_config
    setup_cron_jobs
    setup_log_rotation
    create_dashboard_config

    success "Real-time features monitoring setup completed!"
    log "Monitoring components installed:"
    log "- Health check script: $MONITORING_DIR/health_check.sh"
    log "- Performance monitor: $MONITORING_DIR/performance_monitor.sh"
    log "- Alert manager: $MONITORING_DIR/alert_manager.sh"
    log "- Prometheus config: /etc/prometheus/realtime_monitoring.yml"
    log "- Grafana dashboard: $MONITORING_DIR/grafana_dashboard.json"
    log ""
    log "Next steps:"
    log "1. Configure alert notification channels (email, Slack, PagerDuty)"
    log "2. Import Grafana dashboard from $MONITORING_DIR/grafana_dashboard.json"
    log "3. Start Prometheus and Grafana services"
    log "4. Test monitoring with: $MONITORING_DIR/health_check.sh"
}

# Script entry point
case "${1:-setup}" in
    "setup")
        main
        ;;
    "test")
        "$MONITORING_DIR/health_check.sh"
        ;;
    *)
        echo "Usage: $0 [setup|test]"
        exit 1
        ;;
esac
EOF

    # Make the monitoring setup script executable
    chmod +x "$MONITORING_DIR/setup-realtime-monitoring.sh"

    success "Monitoring and alerting setup script created"
}

# Main deployment function
main() {
    log "Starting real-time features monitoring setup..."

    install_monitoring_dependencies
    create_monitoring_directories
    create_monitoring_scripts
    setup_prometheus_config
    setup_cron_jobs
    setup_log_rotation
    create_dashboard_config

    success "Real-time features monitoring and alerting setup completed!"
    log "Setup summary:"
    log "- Monitoring directory: $MONITORING_DIR"
    log "- Log directory: $LOG_DIR"
    log "- Health checks: Every 5 minutes"
    log "- Performance monitoring: Every minute"
    log "- Alerting: Integrated with email/Slack/PagerDuty"
    log ""
    log "To test the setup:"
    log "  $MONITORING_DIR/health_check.sh"
    log "  $MONITORING_DIR/performance_monitor.sh"
    log "  $MONITORING_DIR/alert_manager.sh"
}

main "$@"
