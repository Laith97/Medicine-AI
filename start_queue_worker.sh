#!/bin/bash

# MedCura AI Queue Worker Startup Script
# This script starts the Laravel queue worker to process jobs

echo "🚀 Starting MedCura AI Queue Worker..."
echo "📍 Working Directory: $(pwd)"
echo "⏰ Started at: $(date)"
echo ""

# Change to the correct directory
cd /home/laith/Documents/Medicine

# Check if Laravel is accessible
if ! php artisan --version > /dev/null 2>&1; then
    echo "❌ Error: Laravel not found or not accessible"
    exit 1
fi

echo "✅ Laravel found: $(php artisan --version)"

# Check queue connection
echo "📊 Queue Status:"
php artisan queue:monitor --once 2>/dev/null || echo "   No active queue workers"

# Show current queue stats
JOBS_COUNT=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | tail -1)
FAILED_COUNT=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null | tail -1)

echo "   Jobs in queue: $JOBS_COUNT"
echo "   Failed jobs: $FAILED_COUNT"
echo ""

# Clear any failed jobs older than 24 hours
echo "🧹 Cleaning up old failed jobs..."
php artisan queue:prune-failed --hours=24

echo ""
echo "🔄 Starting queue worker with the following settings:"
echo "   - Tries: 3 attempts per job"
echo "   - Timeout: 60 seconds per job"
echo "   - Sleep: 3 seconds between jobs"
echo "   - Max Jobs: 1000 before restart"
echo ""
echo "💡 To stop the worker, press Ctrl+C"
echo "📝 Logs will appear below:"
echo "----------------------------------------"

# Start the queue worker
php artisan queue:work \
    --tries=3 \
    --timeout=60 \
    --sleep=3 \
    --max-jobs=1000 \
    --verbose