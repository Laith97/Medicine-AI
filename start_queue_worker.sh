#!/bin/bash

# MedCura AI Queue Worker Startup Script
# Uses systemd user service for persistent queue processing

echo "🚀 Starting MedCura AI Queue Worker..."
echo "📍 Working Directory: $(pwd)"
echo "⏰ Started at: $(date)"
echo ""

# Check if Laravel is accessible
if ! php artisan --version > /dev/null 2>&1; then
    echo "❌ Error: Laravel not found or not accessible"
    exit 1
fi

echo "✅ Laravel found: $(php artisan --version)"

# Show current queue stats
echo "📊 Queue Status:"
JOBS_COUNT=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | tail -1)
FAILED_COUNT=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null | tail -1)

echo "   Jobs in queue: $JOBS_COUNT"
echo "   Failed jobs: $FAILED_COUNT"
echo ""

# Clear any failed jobs older than 24 hours
echo "🧹 Cleaning up old failed jobs..."
php artisan queue:prune-failed --hours=24 2>/dev/null || true

echo ""
echo "🔄 Queue worker is managed by systemd user service."
echo "   Service name: medcura-queue.service"
echo ""

# Check if service is running
if systemctl --user is-active medcura-queue.service > /dev/null 2>&1; then
    echo "✅ Queue worker is running!"
    systemctl --user status medcura-queue.service --no-pager | head -10
else
    echo "⚠️  Queue worker is NOT running. Starting it..."
    systemctl --user start medcura-queue.service
    echo "✅ Started! Check status with: systemctl --user status medcura-queue.service"
fi
