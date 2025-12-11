#!/bin/bash

# Waitlist Management Rollback Script
# This script safely rolls back the waitlist management feature deployment

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKUP_DIR="./backups/waitlist_rollback_$(date +%Y%m%d_%H%M%S)"
LOG_FILE="./logs/waitlist_rollback_$(date +%Y%m%d_%H%M%S).log"

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

# Create backup directory and log file
setup_backup() {
    log "Setting up backup directory: $BACKUP_DIR"
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$(dirname "$LOG_FILE")"

    log "Starting waitlist rollback process..."
    echo "Waitlist Rollback Started: $(date)" > "$LOG_FILE"
}

# Backup database tables
backup_database() {
    log "Creating database backup..."

    # List of waitlist-related tables to backup
    TABLES=(
        "waitlists"
        "waitlist_entries"
        "waitlist_patient_preferences"
        "notification_types"
        "notification_preferences"
    )

    for table in "${TABLES[@]}"; do
        if php artisan tinker --execute="echo \DB::table('$table')->count() . PHP_EOL"; then
            log "Backing up table: $table"
            php artisan tinker --execute="
                \$data = \DB::table('$table')->get();
                file_put_contents('$BACKUP_DIR/${table}_backup.json', json_encode(\$data, JSON_PRETTY_PRINT));
                echo 'Backed up ' . count(\$data) . ' records from $table' . PHP_EOL;
            " >> "$LOG_FILE" 2>&1
        else
            warning "Table $table does not exist or is empty"
        fi
    done

    success "Database backup completed"
}

# Stop queue workers
stop_queue_workers() {
    log "Stopping queue workers..."

    # Stop any running queue workers for waitlist queues
    pkill -f "queue=waitlist" || warning "No waitlist queue workers found running"

    # Wait a moment for workers to stop gracefully
    sleep 5

    success "Queue workers stopped"
}

# Clear waitlist queues
clear_queues() {
    log "Clearing waitlist queues..."

    # Clear jobs from waitlist queues
    QUEUES=(
        "waitlist-urgent"
        "waitlist-high"
        "waitlist-medium"
        "waitlist-low"
        "waitlist-maintenance"
    )

    for queue in "${QUEUES[@]}"; do
        log "Clearing queue: $queue"
        php artisan queue:clear "$queue" --force >> "$LOG_FILE" 2>&1 || warning "Failed to clear queue: $queue"
    done

    success "Queues cleared"
}

# Rollback migrations
rollback_migrations() {
    log "Rolling back waitlist migrations..."

    # List of migration files to rollback (in reverse order)
    MIGRATIONS=(
        "2025_11_15_000019_add_enhanced_fields_to_waitlist_patient_preferences_table.php"
        "2025_11_15_000018_add_waitlist_notification_preferences.php"
        "2025_11_15_000017_add_waitlist_notification_types.php"
        "2025_11_15_000016_create_waitlist_patient_preferences_table.php"
        "2025_11_15_000015_create_waitlist_entries_table.php"
        "2025_11_15_000014_create_waitlists_table.php"
    )

    for migration in "${MIGRATIONS[@]}"; do
        log "Rolling back migration: $migration"
        if php artisan migrate:rollback --path="database/migrations/$migration" >> "$LOG_FILE" 2>&1; then
            success "Rolled back: $migration"
        else
            error "Failed to rollback: $migration"
        fi
    done

    success "All migrations rolled back"
}

# Remove queue configuration
remove_queue_config() {
    log "Removing waitlist queue configuration..."

    # Create a backup of the current queue config
    cp config/queue.php "$BACKUP_DIR/queue.php.backup"

    # Remove waitlist queue configurations from config/queue.php
    # This would need to be done manually or with a script that edits the PHP file
    warning "Queue configuration removal requires manual intervention"
    warning "Please remove waitlist queue configurations from config/queue.php"
    warning "Backup saved to: $BACKUP_DIR/queue.php.backup"

    success "Queue configuration backup completed"
}

# Clean up cache and config
clear_cache() {
    log "Clearing application cache..."

    php artisan config:clear >> "$LOG_FILE" 2>&1
    php artisan cache:clear >> "$LOG_FILE" 2>&1
    php artisan route:clear >> "$LOG_FILE" 2>&1
    php artisan view:clear >> "$LOG_FILE" 2>&1

    success "Cache cleared"
}

# Remove waitlist-related files (optional)
remove_files() {
    log "Removing waitlist-related files..."

    FILES_TO_REMOVE=(
        "app/Services/WaitlistQueueMonitoringService.php"
        "app/Jobs/WaitlistCleanupJob.php"
        "app/Jobs/WaitlistMonitoringJob.php"
        "app/Services/WaitlistService.php"
        "app/Services/WaitlistPreferenceService.php"
    )

    for file in "${FILES_TO_REMOVE[@]}"; do
        if [ -f "$file" ]; then
            log "Backing up and removing: $file"
            cp "$file" "$BACKUP_DIR/$(basename "$file").backup"
            rm "$file"
        else
            warning "File not found: $file"
        fi
    done

    success "Files cleaned up"
}

# Verify rollback
verify_rollback() {
    log "Verifying rollback..."

    # Check if tables still exist
    TABLES_CHECK=(
        "waitlists"
        "waitlist_entries"
        "waitlist_patient_preferences"
    )

    for table in "${TABLES_CHECK[@]}"; do
        if php artisan tinker --execute="echo \Schema::hasTable('$table') ? 'EXISTS' : 'NOT EXISTS'"; then
            error "Table still exists: $table"
        fi
    done

    # Check if queues are empty
    QUEUES_CHECK=(
        "waitlist-urgent"
        "waitlist-high"
        "waitlist-medium"
        "waitlist-low"
        "waitlist-maintenance"
    )

    for queue in "${QUEUES_CHECK[@]}"; do
        job_count=$(php artisan tinker --execute="echo \DB::table('jobs')->where('queue', '$queue')->count()")
        if [ "$job_count" -gt 0 ]; then
            warning "Queue still has jobs: $queue ($job_count jobs)"
        fi
    done

    success "Rollback verification completed"
}

# Main rollback process
main() {
    echo "=========================================="
    echo "  Waitlist Management Rollback Script"
    echo "=========================================="
    echo ""

    setup_backup
    backup_database
    stop_queue_workers
    clear_queues
    rollback_migrations
    remove_queue_config
    clear_cache
    remove_files
    verify_rollback

    echo ""
    echo "=========================================="
    success "Waitlist rollback completed successfully!"
    echo "=========================================="
    echo ""
    echo "Backup location: $BACKUP_DIR"
    echo "Log file: $LOG_FILE"
    echo ""
    warning "Please review the following manual steps:"
    echo "1. Remove waitlist queue configurations from config/queue.php"
    echo "2. Remove waitlist routes from routes files"
    echo "3. Remove waitlist-related frontend code"
    echo "4. Update any monitoring dashboards"
    echo "5. Notify team members of the rollback"
    echo ""
}

# Run main function
main "$@"
