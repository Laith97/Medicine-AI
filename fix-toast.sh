#!/bin/bash
set -e

cd /home/u741521150/domains/medcuraai.com

echo "Step 1: Fixing hardcoded Pusher key in unified-notifications.js..."
sed -i 's/new Pusher("57bd15962a354114cb5e"/new Pusher(import.meta.env.VITE_PUSHER_APP_KEY/' resources/js/unified-notifications.js
sed -i 's/cluster: "ap2"/cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER/' resources/js/unified-notifications.js
echo "OK"

echo "Step 2: Rebuilding frontend assets..."
npm run build
echo "OK"

echo "Step 3: Clearing cache..."
php artisan optimize:clear
echo "OK"

echo "Done! Toast notifications should now work."
