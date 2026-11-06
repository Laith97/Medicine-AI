#!/bin/bash
set -e

echo "Installing supervisor..."
sudo apt update -qq
sudo apt install -y supervisor

echo "Creating supervisor config..."
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
QUEUE_USER="${QUEUE_USER:-www-data}"
sudo tee /etc/supervisor/conf.d/medcura-queue.conf > /dev/null << EOF
[program:medcura-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --tries=3 --timeout=60 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
user=${QUEUE_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start medcura-queue:*

echo "Queue worker is now running via supervisor!"
sudo supervisorctl status
