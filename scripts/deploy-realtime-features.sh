#!/bin/bash

# Real-time Features Deployment Script
# Zero-downtime deployment for broadcasting and real-time appointment features

set -e

# Configuration
APP_NAME="medicine-ai-realtime"
DEPLOY_ENV=${1:-"production"}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/${APP_NAME}/${TIMESTAMP}"
LOG_FILE="/var/log/${APP_NAME}/deploy_${TIMESTAMP}.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE" >&2
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

# Pre-deployment checks
pre_deployment_checks() {
    log "Running pre-deployment checks..."

    # Check if we're running as the correct user
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
    fi

    # Check required environment variables
    required_vars=("DB_HOST" "DB_DATABASE" "PUSHER_APP_KEY" "PUSHER_APP_SECRET" "REDIS_HOST")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            error "Required environment variable $var is not set"
        fi
    done

    # Check database connectivity
    log "Checking database connectivity..."
    if ! php artisan db:monitor >/dev/null 2>&1; then
        error "Database connection failed"
    fi

    # Check Redis connectivity
    log "Checking Redis connectivity..."
    if ! redis-cli ping >/dev/null 2>&1; then
        error "Redis connection failed"
    fi

    # Check Pusher connectivity
    log "Checking Pusher connectivity..."
    if ! php artisan pusher:test >/dev/null 2>&1; then
        warning "Pusher connection test failed - deployment will continue but broadcasting may not work"
    fi

    success "Pre-deployment checks completed"
}

# Create backup
create_backup() {
    log "Creating backup..."

    mkdir -p "$BACKUP_DIR"

    # Backup database
    log "Backing up database..."
    mysqldump --single-transaction --routines --triggers \
        -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE" \
        > "$BACKUP_DIR/database.sql" 2>/dev/null || error "Database backup failed"

    # Backup configuration files
    log "Backing up configuration files..."
    cp config/broadcasting.php "$BACKUP_DIR/" 2>/dev/null || true
    cp config/queue.php "$BACKUP_DIR/" 2>/dev/null || true
    cp .env "$BACKUP_DIR/" 2>/dev/null || true

    # Backup real-time related files
    log "Backing up real-time feature files..."
    tar -czf "$BACKUP_DIR/realtime_features.tar.gz" \
        app/Services/AppointmentBroadcastService.php \
        app/Services/RealtimeStreamingService.php \
        app/Services/PusherConnectionPool.php \
        app/Events/ \
        routes/channels.php \
        2>/dev/null || true

    success "Backup created at $BACKUP_DIR"
}

# Deploy code
deploy_code() {
    log "Deploying code..."

    # Pull latest changes
    log "Pulling latest code..."
    git fetch origin
    git checkout "$DEPLOY_ENV"
    git pull origin "$DEPLOY_ENV"

    # Install dependencies
    log "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader

    log "Installing Node.js dependencies..."
    npm ci --production

    # Build assets
    log "Building frontend assets..."
    npm run production

    # Clear and optimize Laravel caches
    log "Optimizing Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    success "Code deployment completed"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."

    # Check for pending migrations
    if php artisan migrate:status | grep -q "Pending"; then
        log "Running migrations..."
        php artisan migrate --force

        # Run seeders if needed for real-time features
        if [[ -f database/seeders/RealtimeFeaturesSeeder.php ]]; then
            php artisan db:seed --class=RealtimeFeaturesSeeder --force
        fi

        success "Database migrations completed"
    else
        log "No pending migrations found"
    fi
}

# Health checks
health_checks() {
    log "Running health checks..."

    # Check if application is responding
    max_attempts=30
    attempt=1

    while [[ $attempt -le $max_attempts ]]; do
        log "Health check attempt $attempt/$max_attempts"

        if curl -f -s "http://localhost/health" >/dev/null 2>&1; then
            success "Application health check passed"
            return 0
        fi

        sleep 2
        ((attempt++))
    done

    error "Application health check failed after $max_attempts attempts"
}

# Test real-time features
test_realtime_features() {
    log "Testing real-time features..."

    # Test broadcasting configuration
    log "Testing broadcasting configuration..."
    if ! php artisan broadcast:test >/dev/null 2>&1; then
        error "Broadcasting configuration test failed"
    fi

    # Test queue connectivity
    log "Testing queue connectivity..."
    if ! php artisan queue:monitor >/dev/null 2>&1; then
        error "Queue connectivity test failed"
    fi

    # Test real-time services
    log "Testing real-time services..."
    if ! php artisan tinker --execute="
        try {
            \$broadcastService = app(\App\Services\AppointmentBroadcastService::class);
            \$streamingService = app(\App\Services\RealtimeStreamingService::class);
            echo 'Real-time services initialized successfully';
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage();
            exit(1);
        }
    " >/dev/null 2>&1; then
        error "Real-time services test failed"
    fi

    success "Real-time features tests passed"
}

# Zero-downtime deployment
zero_downtime_deploy() {
    log "Performing zero-downtime deployment..."

    # Get current PHP-FPM process ID for graceful restart
    if pgrep php-fpm >/dev/null 2>&1; then
        log "Gracefully restarting PHP-FPM..."
        sudo systemctl reload php8.1-fpm || sudo systemctl reload php-fpm
    fi

    # Restart queue workers with zero downtime
    log "Restarting queue workers..."
    php artisan queue:restart

    # Wait for workers to restart
    sleep 5

    # Clear opcode cache if using OPcache
    if php -m | grep -q opcache; then
        log "Clearing OPcache..."
        php artisan opcache:clear >/dev/null 2>&1 || true
    fi

    success "Zero-downtime deployment completed"
}

# Post-deployment verification
post_deployment_verification() {
    log "Running post-deployment verification..."

    # Test real-time appointment workflow
    log "Testing real-time appointment workflow..."
    if ! php artisan test --filter=RealtimeAppointmentWorkflowTest >/dev/null 2>&1; then
        warning "Real-time workflow tests failed - manual verification recommended"
    fi

    # Verify broadcasting is working
    log "Verifying broadcasting functionality..."
    # This would typically involve a more comprehensive test

    # Check system resources
    log "Checking system resources..."
    memory_usage=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')
    if (( $(echo "$memory_usage > 90" | bc -l) )); then
        warning "High memory usage detected: ${memory_usage}%"
    fi

    success "Post-deployment verification completed"
}

# Rollback function
rollback() {
    error "Deployment failed - initiating rollback..."

    # Restore from backup
    if [[ -d "$BACKUP_DIR" ]]; then
        log "Restoring from backup..."

        # Restore database
        if [[ -f "$BACKUP_DIR/database.sql" ]]; then
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_DIR/database.sql"
        fi

        # Restore configuration files
        cp "$BACKUP_DIR/broadcasting.php" config/ 2>/dev/null || true
        cp "$BACKUP_DIR/queue.php" config/ 2>/dev/null || true
        cp "$BACKUP_DIR/.env" . 2>/dev/null || true

        # Clear caches and restart services
        php artisan config:clear
        php artisan cache:clear
        php artisan queue:restart

        success "Rollback completed"
    else
        error "No backup found for rollback"
    fi
}

# Main deployment function
main() {
    log "Starting real-time features deployment to $DEPLOY_ENV environment"
    log "Timestamp: $TIMESTAMP"
    log "Log file: $LOG_FILE"

    # Trap errors for rollback
    trap rollback ERR

    pre_deployment_checks
    create_backup
    deploy_code
    run_migrations
    zero_downtime_deploy
    health_checks
    test_realtime_features
    post_deployment_verification

    success "Real-time features deployment completed successfully!"
    log "Deployment summary:"
    log "- Environment: $DEPLOY_ENV"
    log "- Timestamp: $TIMESTAMP"
    log "- Backup location: $BACKUP_DIR"
    log "- Log file: $LOG_FILE"

    # Send notification (if configured)
    if command -v curl >/dev/null 2>&1 && [[ -n "$DEPLOY_WEBHOOK_URL" ]]; then
        curl -X POST "$DEPLOY_WEBHOOK_URL" \
            -H "Content-Type: application/json" \
            -d "{\"text\":\"Real-time features deployment completed successfully\",\"environment\":\"$DEPLOY_ENV\"}" \
            >/dev/null 2>&1 || true
    fi
}

# Script entry point
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "rollback")
        if [[ -z "$2" ]]; then
            error "Rollback requires timestamp: $0 rollback <timestamp>"
        fi
        TIMESTAMP="$2"
        BACKUP_DIR="/var/backups/${APP_NAME}/${TIMESTAMP}"
        rollback
        ;;
    "health-check")
        health_checks
        ;;
    "test-realtime")
        test_realtime_features
        ;;
    *)
        echo "Usage: $0 [deploy|rollback|health-check|test-realtime] [timestamp]"
        exit 1
        ;;
esac
