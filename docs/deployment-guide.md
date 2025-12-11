# MedcuraAI Deployment Guide

This guide provides comprehensive instructions for deploying the MedcuraAI medical practice management system in production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Application Deployment](#application-deployment)
4. [Database Setup](#database-setup)
5. [External Services Configuration](#external-services-configuration)
6. [Security Configuration](#security-configuration)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring Setup](#monitoring-setup)
9. [Backup Strategy](#backup-strategy)
10. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **Operating System**: Ubuntu 20.04 LTS or CentOS 8+ (recommended)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.1 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Node.js**: 16+ (for asset compilation)
- **Composer**: Latest version
- **SSL Certificate**: Valid SSL certificate for HTTPS

### Hardware Requirements

- **CPU**: 2+ cores (4+ recommended for production)
- **RAM**: 4GB minimum (8GB+ recommended)
- **Storage**: 20GB+ SSD storage
- **Network**: Stable internet connection with adequate bandwidth

### Required Software

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y curl wget git unzip software-properties-common

# Install PHP and extensions
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install MySQL
sudo apt install -y mysql-server-8.0
```

## Environment Setup

### 1. Create Application Directory

```bash
# Create application directory
sudo mkdir -p /var/www/medcura-ai
sudo chown -R www-data:www-data /var/www/medcura-ai
sudo chmod -R 755 /var/www/medcura-ai
```

### 2. Clone Repository

```bash
cd /var/www/medcura-ai
sudo -u www-data git clone https://github.com/your-org/medcura-ai.git .
sudo -u www-data git checkout main  # or your production branch
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
sudo -u www-data npm install

# Build assets for production
sudo -u www-data npm run production
```

### 4. Environment Configuration

```bash
# Copy environment file
sudo -u www-data cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate
```

## Application Deployment

### Web Server Configuration

#### Nginx Configuration

Create `/etc/nginx/sites-available/medcura-ai`:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Root directory
    root /var/www/medcura-ai/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;

    # PHP handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static assets with caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # WebSocket support for real-time features
    location /app {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/medcura-ai /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache Configuration

Create `/etc/apache2/sites-available/medcura-ai.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/medcura-ai/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLCertificateChainFile /path/to/ssl/ca-bundle.crt

    <Directory /var/www/medcura-ai/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    # API rate limiting
    <Location /api/>
        SetEnvIf Request_URI "^/api/" api_request=1
    </Location>

    ErrorLog ${APACHE_LOG_DIR}/medcura-ai_error.log
    CustomLog ${APACHE_LOG_DIR}/medcura-ai_access.log combined
</VirtualHost>
```

Enable the site and required modules:

```bash
sudo a2ensite medcura-ai
sudo a2enmod ssl rewrite headers
sudo systemctl reload apache2
```

## Database Setup

### 1. Create Database

```sql
-- Create database
CREATE DATABASE medcura_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'medcura_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant permissions
GRANT ALL PRIVILEGES ON medcura_ai.* TO 'medcura_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;
```

### 2. Configure Database Connection

Update `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcura_ai
DB_USERNAME=medcura_user
DB_PASSWORD=secure_password_here
```

### 3. Run Migrations and Seeders

```bash
# Run migrations
sudo -u www-data php artisan migrate --force

# Seed initial data
sudo -u www-data php artisan db:seed --force

# Create storage link
sudo -u www-data php artisan storage:link
```

### 4. Database Optimization

```sql
-- Optimize database settings
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456;     -- 256MB
SET GLOBAL max_connections = 200;

-- Create indexes for performance
CREATE INDEX idx_appointments_date_status ON appointments(appointment_date, status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at);
CREATE INDEX idx_patient_data_assigned ON patient_data(assigned_patient_id);
```

## External Services Configuration

### 1. Pusher (Real-time Notifications)

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=mt1
```

### 2. Stripe (Payment Processing)

```env
STRIPE_KEY=pk_live_your_publishable_key
STRIPE_SECRET=sk_live_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 3. OpenAI (AI Features)

```env
OPENAI_API_KEY=sk-your_openai_api_key
OPENAI_MODEL=gpt-4
OPENAI_MAX_TOKENS=2000
```

### 4. Email Configuration (SMTP)

```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="MedcuraAI"
```

### 5. Redis (Caching & Sessions)

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Security Configuration

### 1. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/medcura-ai

# Set proper permissions
sudo find /var/www/medcura-ai -type f -exec chmod 644 {} \;
sudo find /var/www/medcura-ai -type d -exec chmod 755 {} \;

# Secure sensitive files
sudo chmod 600 /var/www/medcura-ai/.env
sudo chmod 600 /var/www/medcura-ai/storage/logs/*.log
```

### 2. Firewall Configuration

```bash
# Install UFW
sudo apt install ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw allow 443
sudo ufw --force enable
```

### 3. Fail2Ban Setup

```bash
# Install Fail2Ban
sudo apt install fail2ban

# Configure for Nginx
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit jail.local to enable nginx rules
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 4. SSL/TLS Configuration

Ensure SSL certificates are properly configured and auto-renewing:

```bash
# Install Certbot for Let's Encrypt
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Set up auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Performance Optimization

### 1. PHP Optimization

Update `/etc/php/8.1/fpm/php.ini`:

```ini
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

; OPcache settings
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 7963
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
```

Update `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 2. Laravel Optimization

```bash
# Optimize Laravel
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Clear and optimize
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize
```

### 3. Database Optimization

```sql
-- Analyze and optimize tables
ANALYZE TABLE users, appointments, notifications, patient_data;
OPTIMIZE TABLE users, appointments, notifications, patient_data;

-- Set up query cache
SET GLOBAL query_cache_size = 268435456; -- 256MB
SET GLOBAL query_cache_type = ON;
```

### 4. Redis Configuration

Update `/etc/redis/redis.conf`:

```ini
maxmemory 256mb
maxmemory-policy allkeys-lru
tcp-keepalive 300
```

### 5. Queue Worker Setup

```bash
# Create queue worker service
sudo nano /etc/systemd/system/queue-worker.service
```

Add to the service file:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/medcura-ai/artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
WorkingDirectory=/var/www/medcura-ai

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable queue-worker
sudo systemctl start queue-worker
```

## Monitoring Setup

### 1. Application Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop sysstat

# Laravel Telescope (for development monitoring)
sudo -u www-data php artisan telescope:install
sudo -u www-data php artisan migrate
```

### 2. Log Management

```bash
# Configure log rotation
sudo nano /etc/logrotate.d/medcura-ai
```

Add log rotation configuration:

```
/var/www/medcura-ai/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /usr/bin/php /var/www/medcura-ai/artisan optimize:clear
    endscript
}
```

### 3. Health Check Endpoint

Create a health check route in `routes/web.php`:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'redis' => Cache::store('redis')->getStore()->connection()->ping() ? 'connected' : 'disconnected',
            'queue' => Queue::size() !== null ? 'operational' : 'error',
        ]
    ]);
});
```

### 4. Server Monitoring

Install monitoring tools:

```bash
# Install Prometheus Node Exporter
wget https://github.com/prometheus/node_exporter/releases/download/v1.3.1/node_exporter-1.3.1.linux-amd64.tar.gz
tar xvf node_exporter-1.3.1.linux-amd64.tar.gz
sudo mv node_exporter-1.3.1.linux-amd64/node_exporter /usr/local/bin/
sudo useradd -rs /bin/false node_exporter
sudo nano /etc/systemd/system/node_exporter.service
```

Create service file:

```ini
[Unit]
Description=Node Exporter
After=network.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable node_exporter
sudo systemctl start node_exporter
```

## Backup Strategy

### 1. Database Backup

Create backup script `/usr/local/bin/medcura-backup.sh`:

```bash
#!/bin/bash

# Database backup
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/medcura-ai"
DB_NAME="medcura_ai"
DB_USER="medcura_user"
DB_PASS="your_db_password"

mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Keep only last 30 days of backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete

# Upload to cloud storage (optional)
# aws s3 cp $BACKUP_DIR/db_backup_$DATE.sql.gz s3://your-backup-bucket/
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/medcura-backup.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/medcura-backup.sh
```

### 2. File System Backup

```bash
# Create file backup script
sudo nano /usr/local/bin/medcura-file-backup.sh
```

Add to script:

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/medcura-ai"
SOURCE_DIR="/var/www/medcura-ai"

# Backup application files (excluding vendor, node_modules)
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz \
    --exclude="$SOURCE_DIR/vendor" \
    --exclude="$SOURCE_DIR/node_modules" \
    --exclude="$SOURCE_DIR/storage/logs" \
    --exclude="$SOURCE_DIR/.git" \
    $SOURCE_DIR

# Keep only last 7 days of file backups
find $BACKUP_DIR -name "files_backup_*.tar.gz" -mtime +7 -delete
```

Schedule file backup:

```bash
sudo chmod +x /usr/local/bin/medcura-file-backup.sh
sudo crontab -e
# Add: 0 3 * * * /usr/local/bin/medcura-file-backup.sh
```

### 3. Automated Testing

Set up automated testing in production:

```bash
# Create test script
sudo nano /usr/local/bin/medcura-test.sh
```

Add to script:

```bash
#!/bin/bash

cd /var/www/medcura-ai

# Run tests
/usr/bin/php artisan test --parallel

# Check application health
curl -f http://localhost/health > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "Application is healthy"
else
    echo "Application health check failed"
    # Send alert (integrate with monitoring system)
fi
```

Schedule health checks:

```bash
sudo chmod +x /usr/local/bin/medcura-test.sh
sudo crontab -e
# Add: */5 * * * * /usr/local/bin/medcura-test.sh
```

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Symptoms**: Application returns 500 errors

**Solutions**:
```bash
# Check Laravel logs
sudo tail -f /var/www/medcura-ai/storage/logs/laravel.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log

# Check web server logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/apache2/error.log

# Clear Laravel caches
sudo -u www-data php artisan optimize:clear
```

#### 2. Database Connection Issues

**Symptoms**: Database connection errors

**Solutions**:
```bash
# Check database service
sudo systemctl status mysql

# Test database connection
sudo -u www-data php artisan tinker
# In tinker: DB::connection()->getPdo()

# Check database credentials in .env
sudo -u www-data php artisan config:show database
```

#### 3. Queue Worker Issues

**Symptoms**: Jobs not processing

**Solutions**:
```bash
# Check queue worker status
sudo systemctl status queue-worker

# Restart queue worker
sudo systemctl restart queue-worker

# Check queue status
sudo -u www-data php artisan queue:status

# Clear failed jobs
sudo -u www-data php artisan queue:clear
```

#### 4. Real-time Features Not Working

**Symptoms**: Notifications not updating in real-time

**Solutions**:
```bash
# Check Pusher configuration
sudo -u www-data php artisan config:show broadcasting

# Test Pusher connection
sudo -u www-data php artisan tinker
# In tinker: broadcast(new \App\Events\TestEvent())

# Check WebSocket server (if using Laravel Echo Server)
sudo systemctl status laravel-echo-server
```

#### 5. Performance Issues

**Symptoms**: Slow response times

**Solutions**:
```bash
# Check server resources
htop
iotop

# Check database performance
sudo mysql -e "SHOW PROCESSLIST;"

# Optimize Laravel
sudo -u www-data php artisan optimize

# Check Redis
redis-cli ping
redis-cli info
```

### Emergency Procedures

#### Application Down

1. Check server status: `sudo systemctl status nginx php8.1-fpm mysql redis`
2. Restart services: `sudo systemctl restart nginx php8.1-fpm mysql redis`
3. Check application logs for errors
4. If database issues, restore from backup
5. Contact development team if needed

#### Data Loss

1. Stop application to prevent further data corruption
2. Restore from latest backup
3. Check data integrity
4. Notify users of temporary service disruption
5. Investigate root cause

### Support Contacts

- **Development Team**: dev@medcura.ai
- **System Administration**: sysadmin@medcura.ai
- **Emergency Hotline**: +1-XXX-XXX-XXXX

---

**Last Updated**: November 2024
**Version**: 1.0.0
