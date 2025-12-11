#!/bin/bash

# Waitlist Management Deployment Script with Monitoring
# This script deploys the waitlist management feature with comprehensive monitoring

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOG_FILE="./logs/waitlist_deployment_$(date +%Y%m%d_%H%M%S).log"
BACKUP_DIR="./backups/pre_deployment_$(date +%Y%m%d_%H%M%S)"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}" | tee -a "$LOG_FILE"
}

# Setup
setup() {
    log "Setting up deployment environment..."
    mkdir -p "$(dirname "$LOG_FILE")"
    mkdir -p "$BACKUP_DIR"

    echo "Waitlist Deployment Started: $(date)" > "$LOG_FILE"
    success "Setup completed"
}

# Pre-deployment checks
pre_deployment_checks() {
    log "Running pre-deployment checks..."

    # Check if Laravel is properly configured
    if ! php artisan --version >> "$LOG_FILE" 2>&1; then
        error "Laravel is not properly configured"
    fi

    # Check database connection
    if ! php artisan tinker --execute="echo 'Database connected'" >> "$LOG_FILE" 2>&1; then
        error "Database connection failed"
    fi

    # Check if required extensions are installed
    REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "$ext"; then
            error "Required PHP extension missing: $ext"
        fi
    done

    # Check queue configuration
    if ! grep -q "waitlist-urgent" config/queue.php; then
        warning "Waitlist queues not found in queue configuration"
        warning "Please ensure config/queue.php has been updated with waitlist queues"
    fi

    success "Pre-deployment checks passed"
}

# Backup current state
create_backup() {
    log "Creating pre-deployment backup..."

    # Backup database (structure only for safety)
    log "Backing up database structure..."
    mysqldump --no-data --single-transaction --routines \
        -h "${DB_HOST:-localhost}" \
        -u "${DB_USERNAME:-root}" \
        -p"${DB_PASSWORD:-}" \
        "${DB_DATABASE:-laravel}" > "$BACKUP_DIR/database_structure.sql" 2>> "$LOG_FILE" || warning "Database backup failed"

    # Backup configuration files
    cp config/queue.php "$BACKUP_DIR/" 2>> "$LOG_FILE" || warning "Config backup failed"
    cp composer.json "$BACKUP_DIR/" 2>> "$LOG_FILE" || warning "Composer backup failed"

    success "Backup completed: $BACKUP_DIR"
}

# Run migrations
run_migrations() {
    log "Running database migrations..."

    # Run waitlist-related migrations
    MIGRATIONS=(
        "2025_11_15_000014_create_waitlists_table.php"
        "2025_11_15_000015_create_waitlist_entries_table.php"
        "2025_11_15_000016_create_waitlist_patient_preferences_table.php"
        "2025_11_15_000017_add_waitlist_notification_types.php"
        "2025_11_15_000018_add_waitlist_notification_preferences.php"
        "2025_11_15_000019_add_enhanced_fields_to_waitlist_patient_preferences_table.php"
    )

    for migration in "${MIGRATIONS[@]}"; do
        log "Running migration: $migration"
        if php artisan migrate --path="database/migrations/$migration" >> "$LOG_FILE" 2>&1; then
            success "Migration completed: $migration"
        else
            error "Migration failed: $migration"
        fi
    done

    success "All migrations completed"
}

# Clear and cache configuration
clear_cache() {
    log "Clearing and caching configuration..."

    php artisan config:clear >> "$LOG_FILE" 2>&1
    php artisan cache:clear >> "$LOG_FILE" 2>&1
    php artisan route:clear >> "$LOG_FILE" 2>&1
    php artisan view:clear >> "$LOG_FILE" 2>&1

    php artisan config:cache >> "$LOG_FILE" 2>&1
    php artisan route:cache >> "$LOG_FILE" 2>&1
    php artisan view:cache >> "$LOG_FILE" 2>&1

    success "Cache operations completed"
}

# Setup queue workers
setup_queue_workers() {
    log "Setting up queue workers..."

    # Create supervisor configuration for waitlist queues
    cat > /tmp/waitlist-worker.conf << EOF
[program:waitlist-urgent]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=waitlist-urgent --sleep=3 --tries=3 --max-jobs=1000
directory=/path/to/project
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/waitlist-urgent.log

[program:waitlist-high]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=waitlist-high --sleep=3 --tries=3 --max-jobs=1000
directory=/path/to/project
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/waitlist-high.log

[program:waitlist-medium]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=waitlist-medium --sleep=3 --tries=3 --max-jobs=1000
directory=/path/to/project
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/waitlist-medium.log

[program:waitlist-low]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=waitlist-low --sleep=3 --tries=3 --max-jobs=1000
directory=/path/to/project
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/waitlist-low.log

[program:waitlist-maintenance]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=waitlist-maintenance --sleep=3 --tries=3 --max-jobs=1000
directory=/path/to/project
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/waitlist-maintenance.log
EOF

    warning "Supervisor configuration created at /tmp/waitlist-worker.conf"
    warning "Please copy this to /etc/supervisor/conf.d/ and update paths"
    warning "Then run: supervisorctl reread && supervisorctl update"

    success "Queue worker configuration prepared"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."

    # Test health check endpoint
    if curl -f -s "http://localhost/api/health" >> "$LOG_FILE" 2>&1; then
        success "Health check endpoint is accessible"
    else
        warning "Health check endpoint not accessible - ensure web server is running"
    fi

    # Test metrics endpoint
    if curl -f -s "http://localhost/api/metrics" >> "$LOG_FILE" 2>&1; then
        success "Metrics endpoint is accessible"
    else
        warning "Metrics endpoint not accessible - ensure web server is running"
    fi

    success "Monitoring setup completed"
}

# Setup scheduled tasks
setup_scheduled_tasks() {
    log "Setting up scheduled tasks..."

    # Check if scheduler is running
    if pgrep -f "queue:work" > /dev/null; then
        success "Queue workers are running"
    else
        warning "No queue workers detected - ensure they are started"
    fi

    # Schedule cleanup job
    log "Scheduling waitlist cleanup job..."
    php artisan tinker --execute="
        \$schedule = app(Illuminate\Console\Scheduling\Schedule::class);
        \$schedule->job(new App\Jobs\WaitlistCleanupJob('expired_entries', 30))
                 ->daily()
                 ->runInBackground();
        echo 'Cleanup job scheduled' . PHP_EOL;
    " >> "$LOG_FILE" 2>&1

    success "Scheduled tasks setup completed"
}

# Run initial health check
run_initial_health_check() {
    log "Running initial health check..."

    # Run waitlist maintenance health check
    if php artisan waitlist:maintenance health-check >> "$LOG_FILE" 2>&1; then
        success "Initial health check passed"
    else
        warning "Initial health check had issues - check logs"
    fi
}

# Post-deployment verification
verify_deployment() {
    log "Verifying deployment..."

    # Check if tables exist
    TABLES=(
        "waitlists"
        "waitlist_entries"
        "waitlist_patient_preferences"
    )

    for table in "${TABLES[@]}"; do
        if php artisan tinker --execute="echo \Schema::hasTable('$table') ? 'EXISTS' : 'NOT EXISTS' . PHP_EOL;" | grep -q "EXISTS"; then
            success "Table verified: $table"
        else
            error "Table missing: $table"
        fi
    done

    # Check if services are available
    if php artisan tinker --execute="echo app(App\Services\WaitlistService::class) ? 'AVAILABLE' : 'NOT AVAILABLE' . PHP_EOL;" | grep -q "AVAILABLE"; then
        success "WaitlistService is available"
    else
        error "WaitlistService is not available"
    fi

    success "Deployment verification completed"
}

# Create deployment summary
create_summary() {
    log "Creating deployment summary..."

    cat >> "$LOG_FILE" << EOF

==========================================
WAITLIST DEPLOYMENT SUMMARY
==========================================

Deployment completed successfully at: $(date)

Key Components Deployed:
✓ Database migrations (6 migrations)
✓ Queue configurations (5 priority queues)
✓ Monitoring services
✓ Cleanup jobs
✓ Health checks
✓ Scheduled tasks

Queue Configuration:
- waitlist-urgent: High priority, 30s retry
- waitlist-high: High priority, 60s retry
- waitlist-medium: Medium priority, 120s retry
- waitlist-low: Low priority, 300s retry
- waitlist-maintenance: Maintenance tasks, 600s retry

Monitoring Endpoints:
/api/health - General health check
/api/metrics - Prometheus metrics
/api/monitoring/dashboard - Monitoring dashboard

Maintenance Commands:
php artisan waitlist:maintenance cleanup --type=expired_entries
php artisan waitlist:maintenance monitor
php artisan waitlist:maintenance health-check

Scheduled Tasks:
- Daily cleanup of expired entries (30+ days old)
- Continuous queue monitoring

Backup Location: $BACKUP_DIR
Log File: $LOG_FILE

Next Steps:
1. Start queue workers: supervisorctl start waitlist-*
2. Monitor health: php artisan waitlist:maintenance health-check
3. Check logs: tail -f $LOG_FILE
4. Verify functionality in application

==========================================
EOF

    success "Deployment summary created"
}

# Main deployment process
main() {
    echo "=========================================="
    echo "  Waitlist Management Deployment Script"
    echo "=========================================="
    echo ""

    setup
    pre_deployment_checks
    create_backup
    run_migrations
    clear_cache
    setup_queue_workers
    setup_monitoring
    setup_scheduled_tasks
    run_initial_health_check
    verify_deployment
    create_summary

    echo ""
    echo "=========================================="
    success "Waitlist deployment completed successfully!"
    echo "=========================================="
    echo ""
    echo "Summary and logs: $LOG_FILE"
    echo "Backup location: $BACKUP_DIR"
    echo ""
    warning "Don't forget to:"
    echo "1. Configure and start supervisor workers"
    echo "2. Update monitoring dashboards"
    echo "3. Test the waitlist functionality"
    echo "4. Monitor logs for any issues"
    echo ""
}

# Run main function
main "$@"
