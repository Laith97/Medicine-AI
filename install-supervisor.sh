#!/bin/bash
set -e

echo "Installing supervisor..."
sudo apt update -qq
sudo apt install -y supervisor

echo "Creating supervisor config..."
sudo tee /etc/supervisor/conf.d/medcura-queue.conf > /dev/null << 'EOF'
[program:medcura-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/laith/Documents/Medicine/artisan queue:work --tries=3 --timeout=60 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
user=laith
numprocs=1
redirect_stderr=true
stdout_logfile=/home/laith/Documents/Medicine/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start medcura-queue:*

echo "Queue worker is now running via supervisor!"
sudo supervisorctl status
