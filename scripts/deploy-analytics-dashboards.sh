#!/bin/bash

# Advanced Analytics Dashboards - Zero-Downtime Deployment Script
# This script implements blue-green deployment strategy for the analytics dashboards

set -e  # Exit on any error

# Configuration
APP_NAME="medicine-ai-analytics"
DEPLOY_ENV=${1:-"production"}
BLUE_PORT=8000
GREEN_PORT=8001
HEALTH_CHECK_TIMEOUT=300
ROLLBACK_TIMEOUT=600

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Function to check if a port is responding
check_port_health() {
    local port=$1
    local timeout=$2

    log_info "Checking health of port $port..."

    local start_time=$(date +%s)
    while ! curl -f -s "http://localhost:$port/api/health" > /dev/null 2>&1; do
        local current_time=$(date +%s)
        local elapsed=$((current_time - start_time))

        if [ $elapsed -gt $timeout ]; then
            log_error "Port $port failed to respond within $timeout seconds"
            return 1
        fi

        log_info "Waiting for port $port to respond... ($elapsed/$timeout seconds)"
        sleep 5
    done

    log_success "Port $port is healthy"
    return 0
}

# Function to get current active environment
get_active_environment() {
    if curl -f -s "http://localhost:$BLUE_PORT/api/health" > /dev/null 2>&1; then
        echo "blue"
    elif curl -f -s "http://localhost:$GREEN_PORT/api/health" > /dev/null 2>&1; then
        echo "green"
    else
        echo "none"
    fi
}

# Function to deploy to a specific environment
deploy_to_environment() {
    local env=$1
    local port=$2

    log_info "Starting deployment to $env environment (port $port)"

    # Create deployment directory
    local deploy_dir="/var/www/$APP_NAME-$env"
    local backup_dir="/var/www/backups/$APP_NAME-$(date +%Y%m%d_%H%M%S)"

    # Backup current deployment
    if [ -d "$deploy_dir" ]; then
        log_info "Creating backup of current deployment"
        mkdir -p "$(dirname "$backup_dir")"
        cp -r "$deploy_dir" "$backup_dir"
    fi

    # Update code
    log_info "Updating code from repository"
    if [ -d "$deploy_dir" ]; then
        cd "$deploy_dir"
        git fetch origin
        git checkout "$DEPLOY_ENV"
        git pull origin "$DEPLOY_ENV"
    else
        git clone --branch "$DEPLOY_ENV" https://github.com/your-org/medicine-ai.git "$deploy_dir"
        cd "$deploy_dir"
    fi

    # Install dependencies
    log_info "Installing PHP dependencies"
    composer install --no-dev --optimize-autoloader

    log_info "Installing Node.js dependencies"
    npm ci
    npm run build

    # Run database migrations (if needed)
    log_info "Running database migrations"
    php artisan migrate --force

    # Clear and optimize caches
    log_info "Optimizing application"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Seed analytics data (if first deployment)
    if [ ! -f "$deploy_dir/.analytics_initialized" ]; then
        log_info "Initializing analytics data"
        php artisan db:seed --class=AnalyticsSeeder
        touch "$deploy_dir/.analytics_initialized"
    fi

    # Start the application
    log_info "Starting $env environment on port $port"
    sudo systemctl restart "$APP_NAME-$env"

    # Wait for health check
    if check_port_health "$port" "$HEALTH_CHECK_TIMEOUT"; then
        log_success "Deployment to $env environment completed successfully"
        return 0
    else
        log_error "Deployment to $env environment failed health check"
        return 1
    fi
}

# Function to switch traffic to new environment
switch_traffic() {
    local new_env=$1
    local new_port=$2

    log_info "Switching traffic to $new_env environment"

    # Update nginx configuration
    sudo cp "/etc/nginx/sites-available/$APP_NAME.conf" "/etc/nginx/sites-available/$APP_NAME.conf.backup"

    # Update upstream server
    sudo sed -i "s/server localhost:[0-9]\+;/server localhost:$new_port;/" "/etc/nginx/sites-available/$APP_NAME.conf"

    # Test nginx configuration
    if sudo nginx -t; then
        sudo systemctl reload nginx
        log_success "Traffic switched to $new_env environment"
        return 0
    else
        log_error "Nginx configuration test failed"
        # Restore backup
        sudo cp "/etc/nginx/sites-available/$APP_NAME.conf.backup" "/etc/nginx/sites-available/$APP_NAME.conf"
        return 1
    fi
}

# Function to rollback deployment
rollback() {
    local failed_env=$1
    local active_env=$2

    log_error "Rolling back deployment. Failed environment: $failed_env, Active environment: $active_env"

    if [ "$active_env" != "none" ]; then
        local active_port=$([ "$active_env" = "blue" ] && echo "$BLUE_PORT" || echo "$GREEN_PORT")
        switch_traffic "$active_env" "$active_port"
        log_warn "Rolled back to $active_env environment"
    else
        log_error "No active environment available for rollback"
        exit 1
    fi
}

# Main deployment logic
main() {
    log_info "Starting zero-downtime deployment for $APP_NAME in $DEPLOY_ENV environment"

    # Determine deployment strategy
    local active_env=$(get_active_environment)
    local deploy_env=""
    local deploy_port=""

    case $active_env in
        "blue")
            deploy_env="green"
            deploy_port=$GREEN_PORT
            ;;
        "green")
            deploy_env="blue"
            deploy_port=$BLUE_PORT
            ;;
        "none")
            # First deployment
            deploy_env="blue"
            deploy_port=$BLUE_PORT
            ;;
    esac

    log_info "Active environment: $active_env"
    log_info "Deploying to: $deploy_env (port $deploy_port)"

    # Deploy to new environment
    if deploy_to_environment "$deploy_env" "$deploy_port"; then
        # Switch traffic
        if switch_traffic "$deploy_env" "$deploy_port"; then
            log_success "Deployment completed successfully"

            # Wait for traffic to stabilize
            sleep 30

            # Verify deployment
            if check_port_health "$deploy_port" 30; then
                log_success "Deployment verified successfully"

                # Clean up old environment if it exists
                if [ "$active_env" != "none" ]; then
                    log_info "Stopping old environment: $active_env"
                    sudo systemctl stop "$APP_NAME-$active_env"
                fi

                exit 0
            else
                log_error "Deployment verification failed"
                rollback "$deploy_env" "$active_env"
                exit 1
            fi
        else
            log_error "Traffic switch failed"
            rollback "$deploy_env" "$active_env"
            exit 1
        fi
    else
        log_error "Deployment failed"
        if [ "$active_env" != "none" ]; then
            rollback "$deploy_env" "$active_env"
        fi
        exit 1
    fi
}

# Pre-deployment checks
pre_deployment_checks() {
    log_info "Running pre-deployment checks"

    # Check if running as root or with sudo
    if [ "$EUID" -ne 0 ]; then
        log_error "This script must be run with sudo privileges"
        exit 1
    fi

    # Check required tools
    for tool in git composer npm curl; do
        if ! command -v $tool &> /dev/null; then
            log_error "Required tool '$tool' is not installed"
            exit 1
        fi
    done

    # Check disk space
    local available_space=$(df /var/www | tail -1 | awk '{print $4}')
    if [ "$available_space" -lt 1048576 ]; then # 1GB in KB
        log_error "Insufficient disk space. At least 1GB required"
        exit 1
    fi

    log_success "Pre-deployment checks passed"
}

# Cleanup function
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log_error "Deployment failed with exit code $exit_code"
    fi
    # Any cleanup logic here
}

# Set up cleanup trap
trap cleanup EXIT

# Run pre-deployment checks
pre_deployment_checks

# Run main deployment
main
