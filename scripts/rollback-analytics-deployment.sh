#!/bin/bash

# Advanced Analytics Dashboards - Rollback Script
# This script provides automated rollback functionality for failed deployments

set -e

# Configuration
APP_NAME="medicine-ai-analytics"
ROLLBACK_TIMEOUT=300

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

# Function to check port health
check_port_health() {
    local port=$1
    local timeout=${2:-30}

    local start_time=$(date +%s)
    while ! curl -f -s "http://localhost:$port/api/health" > /dev/null 2>&1; do
        local current_time=$(date +%s)
        local elapsed=$((current_time - start_time))

        if [ $elapsed -gt $timeout ]; then
            return 1
        fi
        sleep 2
    done
    return 0
}

# Function to get available backups
list_backups() {
    local env=$1
    local backup_dir="/var/www/backups/$APP_NAME-$env"

    if [ -d "$backup_dir" ]; then
        ls -la "$backup_dir" | grep "^d" | awk '{print $9}' | tail -10
    else
        echo "No backups found for $env environment"
    fi
}

# Function to rollback to specific backup
rollback_to_backup() {
    local env=$1
    local backup_name=$2
    local port=$3

    local deploy_dir="/var/www/$APP_NAME-$env"
    local backup_path="/var/www/backups/$APP_NAME-$env/$backup_name"

    if [ ! -d "$backup_path" ]; then
        log_error "Backup $backup_name not found"
        return 1
    fi

    log_info "Rolling back $env environment to backup: $backup_name"

    # Stop the service
    sudo systemctl stop "$APP_NAME-$env" || true

    # Backup current failed deployment
    local failed_backup="/var/www/backups/$APP_NAME-${env}-failed-$(date +%Y%m%d_%H%M%S)"
    if [ -d "$deploy_dir" ]; then
        cp -r "$deploy_dir" "$failed_backup"
    fi

    # Restore from backup
    rm -rf "$deploy_dir"
    cp -r "$backup_path" "$deploy_dir"

    # Restore dependencies and caches
    cd "$deploy_dir"
    composer install --no-dev --optimize-autoloader
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Start the service
    sudo systemctl start "$APP_NAME-$env"

    # Wait for health check
    if check_port_health "$port" "$ROLLBACK_TIMEOUT"; then
        log_success "Rollback to $backup_name completed successfully"
        return 0
    else
        log_error "Rollback failed - service did not become healthy"
        return 1
    fi
}

# Function to rollback to previous version
rollback_to_previous() {
    local env=$1
    local port=$2

    log_info "Attempting automatic rollback for $env environment"

    # Find the most recent backup
    local backup_dir="/var/www/backups/$APP_NAME-$env"
    local latest_backup=$(ls -td "$backup_dir"/*/ 2>/dev/null | head -1)

    if [ -z "$latest_backup" ]; then
        log_error "No backups found for automatic rollback"
        return 1
    fi

    local backup_name=$(basename "$latest_backup")
    log_info "Found latest backup: $backup_name"

    rollback_to_backup "$env" "$backup_name" "$port"
}

# Function to switch traffic back
switch_traffic_back() {
    local target_env=$1
    local target_port=$2

    log_info "Switching traffic back to $target_env environment"

    # Update nginx configuration
    sudo sed -i "s/server localhost:[0-9]\+;/server localhost:$target_port;/" "/etc/nginx/sites-available/$APP_NAME.conf"

    if sudo nginx -t; then
        sudo systemctl reload nginx
        log_success "Traffic switched back to $target_env environment"
        return 0
    else
        log_error "Failed to switch traffic back"
        return 1
    fi
}

# Main rollback logic
main() {
    local target_env=${1:-""}
    local backup_name=${2:-""}

    if [ -z "$target_env" ]; then
        log_error "Usage: $0 <environment> [backup_name]"
        log_info "Available environments: blue, green"
        log_info "To list backups: $0 <environment> --list"
        exit 1
    fi

    if [ "$backup_name" = "--list" ]; then
        log_info "Available backups for $target_env environment:"
        list_backups "$target_env"
        exit 0
    fi

    local port=$([ "$target_env" = "blue" ] && echo "8000" || echo "8001")

    if [ -n "$backup_name" ]; then
        # Specific backup rollback
        if rollback_to_backup "$target_env" "$backup_name" "$port"; then
            switch_traffic_back "$target_env" "$port"
            log_success "Manual rollback completed successfully"
        else
            log_error "Manual rollback failed"
            exit 1
        fi
    else
        # Automatic rollback to latest backup
        if rollback_to_previous "$target_env" "$port"; then
            switch_traffic_back "$target_env" "$port"
            log_success "Automatic rollback completed successfully"
        else
            log_error "Automatic rollback failed"
            exit 1
        fi
    fi
}

# Emergency rollback - rollback both environments if needed
emergency_rollback() {
    log_warn "Performing emergency rollback - attempting to restore both environments"

    # Try to rollback blue environment
    if rollback_to_previous "blue" "8000"; then
        switch_traffic_back "blue" "8000"
        log_success "Emergency rollback completed - blue environment restored"
        exit 0
    fi

    # If blue fails, try green
    if rollback_to_previous "green" "8001"; then
        switch_traffic_back "green" "8001"
        log_success "Emergency rollback completed - green environment restored"
        exit 0
    fi

    log_error "Emergency rollback failed - no viable backups found"
    exit 1
}

# Parse command line arguments
case "${1:-}" in
    "--emergency"|"-e")
        emergency_rollback
        ;;
    "--help"|"-h")
        echo "Usage:"
        echo "  $0 <environment> [backup_name]  - Rollback specific environment"
        echo "  $0 <environment> --list         - List available backups"
        echo "  $0 --emergency                  - Emergency rollback both environments"
        echo ""
        echo "Environments: blue, green"
        exit 0
        ;;
    *)
        main "$@"
        ;;
esac
