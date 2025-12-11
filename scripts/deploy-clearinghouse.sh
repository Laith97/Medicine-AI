#!/bin/bash

# Clearinghouse Integration Deployment Script
# Provides zero-downtime deployment with rollback capabilities

set -e  # Exit on any error

# Configuration
APP_NAME="medicine-ai"
DEPLOY_ENV=${1:-"production"}
DEPLOY_USER=${2:-"deploy"}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/${APP_NAME}/${TIMESTAMP}"
ROLLBACK_SCRIPT="${BACKUP_DIR}/rollback.sh"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Pre-deployment checks
pre_deployment_checks() {
    log_info "Running pre-deployment checks..."

    # Check if user has sudo privileges
    if ! sudo -n true 2>/dev/null; then
        log_error "User does not have sudo privileges"
        exit 1
    fi

    # Check disk space
    DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ $DISK_USAGE -gt 90 ]; then
        log_error "Disk usage is too high: ${DISK_USAGE}%"
        exit 1
    fi

    # Check if application is running
    if ! pgrep -f "php.*artisan.*serve" > /dev/null; then
        log_warning "Application does not appear to be running"
    fi

    # Check database connectivity
    if ! php artisan tinker --execute="echo 'Database connection OK'" > /dev/null 2>&1; then
        log_error "Database connection failed"
        exit 1
    fi

    log_success "Pre-deployment checks passed"
}

# Create backup
create_backup() {
    log_info "Creating backup..."

    # Create backup directory
    sudo mkdir -p $BACKUP_DIR

    # Backup application code
    sudo cp -r /var/www/${APP_NAME} ${BACKUP_DIR}/app_backup/

    # Backup database
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)

    sudo mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > ${BACKUP_DIR}/database_backup.sql

    # Backup environment file
    sudo cp .env ${BACKUP_DIR}/.env.backup

    # Create rollback script
    cat > $ROLLBACK_SCRIPT << EOF
#!/bin/bash
echo "Rolling back deployment from ${TIMESTAMP}..."

# Restore application code
sudo cp -r ${BACKUP_DIR}/app_backup/* /var/www/${APP_NAME}/

# Restore database
mysql -u$DB_USER -p$DB_PASS $DB_NAME < ${BACKUP_DIR}/database_backup.sql

# Restore environment file
sudo cp ${BACKUP_DIR}/.env.backup .env

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

echo "Rollback completed"
EOF

    sudo chmod +x $ROLLBACK_SCRIPT

    log_success "Backup created at ${BACKUP_DIR}"
}

# Deploy application
deploy_application() {
    log_info "Deploying application..."

    # Pull latest changes
    git fetch origin
    git checkout ${DEPLOY_ENV}
    git pull origin ${DEPLOY_ENV}

    # Install dependencies
    composer install --no-dev --optimize-autoloader

    # Clear and cache config
    php artisan config:clear
    php artisan config:cache

    # Run database migrations
    php artisan migrate --force

    # Clear all caches
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear

    # Optimize application
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    log_success "Application deployed successfully"
}

# Run health checks
run_health_checks() {
    log_info "Running health checks..."

    # Wait for application to be ready
    sleep 10

    # Check if application is responding
    if ! curl -f -s http://localhost/health > /dev/null; then
        log_error "Health check failed - application not responding"
        return 1
    fi

    # Check database connectivity
    if ! php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';" > /dev/null; then
        log_error "Database health check failed"
        return 1
    fi

    # Check queue worker status
    if ! pgrep -f "php.*artisan.*queue:work" > /dev/null; then
        log_warning "Queue worker not running"
    fi

    # Run application-specific health checks
    if ! php artisan clearinghouse:health-check > /dev/null; then
        log_error "Clearinghouse health check failed"
        return 1
    fi

    log_success "All health checks passed"
    return 0
}

# Zero-downtime deployment
zero_downtime_deploy() {
    log_info "Performing zero-downtime deployment..."

    # Get current PHP-FPM master process ID for graceful reload
    FPM_MASTER_PID=$(pgrep -f "php-fpm.*master")

    # Graceful reload of PHP-FPM (zero downtime)
    sudo kill -USR2 $FPM_MASTER_PID

    # Wait for new workers to be ready
    sleep 5

    # Terminate old workers gracefully
    sudo kill -WINCH $FPM_MASTER_PID

    # Wait for old workers to finish
    sleep 10

    # Check if new workers are handling requests
    if pgrep -f "php.*artisan.*serve" > /dev/null; then
        # If using artisan serve, restart it
        pkill -f "php.*artisan.*serve"
        nohup php artisan serve --host=0.0.0.0 --port=8000 > storage/logs/artisan_serve.log 2>&1 &
    fi

    log_success "Zero-downtime deployment completed"
}

# Post-deployment tasks
post_deployment_tasks() {
    log_info "Running post-deployment tasks..."

    # Update file permissions
    sudo chown -R www-data:www-data /var/www/${APP_NAME}
    sudo chmod -R 755 /var/www/${APP_NAME}
    sudo chmod -R 775 /var/www/${APP_NAME}/storage

    # Clear opcode cache if using APCu or OPcache
    if php -m | grep -q "Zend OPcache"; then
        php artisan opcache:clear
    fi

    # Warm up application cache
    php artisan clearinghouse:warm-cache

    # Send deployment notification
    php artisan clearinghouse:deployment-notification --environment=$DEPLOY_ENV --status=success

    log_success "Post-deployment tasks completed"
}

# Rollback function
rollback() {
    log_error "Deployment failed, initiating rollback..."

    if [ -f "$ROLLBACK_SCRIPT" ]; then
        bash $ROLLBACK_SCRIPT

        # Send failure notification
        php artisan clearinghouse:deployment-notification --environment=$DEPLOY_ENV --status=failed || true

        log_info "Rollback completed"
        exit 1
    else
        log_error "No rollback script found"
        exit 1
    fi
}

# Main deployment function
main() {
    log_info "Starting deployment of ${APP_NAME} to ${DEPLOY_ENV} environment"
    log_info "Timestamp: ${TIMESTAMP}"

    # Change to application directory
    cd /var/www/${APP_NAME}

    # Run pre-deployment checks
    pre_deployment_checks

    # Create backup
    create_backup

    # Deploy application
    if ! deploy_application; then
        rollback
    fi

    # Run health checks
    if ! run_health_checks; then
        rollback
    fi

    # Zero-downtime deployment
    if ! zero_downtime_deploy; then
        rollback
    fi

    # Post-deployment tasks
    if ! post_deployment_tasks; then
        log_warning "Post-deployment tasks failed, but deployment succeeded"
    fi

    log_success "Deployment completed successfully!"
    log_info "Rollback script available at: ${ROLLBACK_SCRIPT}"

    # Clean up old backups (keep last 10)
    sudo find /var/backups/${APP_NAME} -maxdepth 1 -type d -name "20*" | sort | head -n -10 | xargs -r sudo rm -rf
}

# Handle command line arguments
case "$1" in
    --rollback)
        if [ -z "$2" ]; then
            log_error "Please specify timestamp for rollback: $0 --rollback YYYYMMDD_HHMMSS"
            exit 1
        fi
        ROLLBACK_SCRIPT="/var/backups/${APP_NAME}/$2/rollback.sh"
        if [ -f "$ROLLBACK_SCRIPT" ]; then
            bash $ROLLBACK_SCRIPT
        else
            log_error "Rollback script not found: $ROLLBACK_SCRIPT"
            exit 1
        fi
        ;;
    --list-backups)
        log_info "Available backups:"
        ls -la /var/backups/${APP_NAME}/ | grep "^d" | awk '{print $9}'
        ;;
    *)
        main "$@"
        ;;
esac
