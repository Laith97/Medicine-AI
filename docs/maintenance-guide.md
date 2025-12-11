# MedcuraAI Maintenance Guide

This guide provides procedures and best practices for maintaining the MedcuraAI medical practice management system in production environments.

## Table of Contents

1. [Daily Maintenance Tasks](#daily-maintenance-tasks)
2. [Weekly Maintenance Tasks](#weekly-maintenance-tasks)
3. [Monthly Maintenance Tasks](#monthly-maintenance-tasks)
4. [Security Maintenance](#security-maintenance)
5. [Performance Monitoring](#performance-monitoring)
6. [Backup and Recovery](#backup-and-recovery)
7. [Incident Response](#incident-response)
8. [Compliance Maintenance](#compliance-maintenance)
9. [System Updates](#system-updates)
10. [Troubleshooting Procedures](#troubleshooting-procedures)

## Daily Maintenance Tasks

### Morning Health Check

Perform these checks every morning before users start accessing the system:

```bash
# 1. Check system resources
uptime
free -h
df -h

# 2. Check service status
sudo systemctl status nginx php8.1-fpm mysql redis queue-worker

# 3. Check application health
curl -f https://your-domain.com/health

# 4. Check error logs
sudo tail -50 /var/log/nginx/error.log
sudo tail -50 /var/www/medcura-ai/storage/logs/laravel.log

# 5. Check queue status
sudo -u www-data php artisan queue:status

# 6. Check database connections
sudo mysql -e "SHOW PROCESSLIST;" | head -20
```

### Log Rotation and Cleanup

```bash
# Rotate logs
sudo logrotate -f /etc/logrotate.d/medcura-ai

# Clean old temporary files
sudo find /tmp -name "php*" -type f -mtime +1 -delete
sudo find /var/www/medcura-ai/storage/logs -name "*.log" -mtime +30 -delete
```

### Database Maintenance

```bash
# Check database size and growth
sudo mysql -e "SELECT table_schema, ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 'Size (MB)' FROM information_schema.tables WHERE table_schema = 'medcura_ai' GROUP BY table_schema;"

# Check for long-running queries
sudo mysql -e "SELECT id, user, host, db, command, time, state, info FROM information_schema.processlist WHERE command != 'Sleep' AND time > 30 ORDER BY time DESC;"

# Update table statistics
sudo mysql medcura_ai -e "ANALYZE TABLE users, appointments, notifications, patient_data;"
```

## Weekly Maintenance Tasks

### Application Maintenance

```bash
# Clear expired cache entries
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Rebuild optimized cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Clear expired notifications (older than 90 days)
sudo mysql medcura_ai -e "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# Archive old appointment data (older than 2 years)
sudo mysql medcura_ai -e "INSERT INTO archived_appointments SELECT * FROM appointments WHERE appointment_date < DATE_SUB(NOW(), INTERVAL 2 YEAR);"
sudo mysql medcura_ai -e "DELETE FROM appointments WHERE appointment_date < DATE_SUB(NOW(), INTERVAL 2 YEAR);"
```

### Security Updates

```bash
# Update system packages
sudo apt update
sudo apt upgrade -y

# Update PHP packages
sudo apt install --only-upgrade php*

# Restart services after updates
sudo systemctl restart nginx php8.1-fpm

# Check for security vulnerabilities
sudo apt list --upgradable | grep -i security
```

### Performance Optimization

```bash
# Optimize database tables
sudo mysql medcura_ai -e "OPTIMIZE TABLE users, appointments, notifications, patient_data, patient_analyses;"

# Check and repair tables if needed
sudo mysql medcura_ai -e "CHECK TABLE users, appointments, notifications, patient_data, patient_analyses;"

# Clear Redis cache if memory usage is high
redis-cli FLUSHDB  # Only if necessary - this clears all cache

# Restart queue worker if needed
sudo systemctl restart queue-worker
```

### Backup Verification

```bash
# List recent backups
ls -la /var/backups/medcura-ai/

# Verify backup integrity
gzip -t /var/backups/medcura-ai/db_backup_$(date +%Y%m%d)*.sql.gz

# Test backup restoration (on staging environment)
# mysqldump -u user -p medcura_ai < /var/backups/medcura-ai/db_backup_$(date +%Y%m%d)*.sql
```

## Monthly Maintenance Tasks

### Comprehensive System Audit

```bash
# 1. Review user access and permissions
sudo -u www-data php artisan tinker --execute="
\User::with('permissions')->get()->each(function(\$user) {
    echo \$user->name . ': ' . \$user->permissions->count() . ' permissions' . PHP_EOL;
});
"

# 2. Check for inactive users
sudo mysql medcura_ai -e "SELECT COUNT(*) as inactive_users FROM users WHERE last_login_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);"

# 3. Review failed login attempts
sudo grep "Failed login" /var/www/medcura-ai/storage/logs/laravel.log | tail -20

# 4. Check SSL certificate expiration
openssl x509 -in /path/to/ssl/certificate.crt -text -noout | grep "Not After"

# 5. Review disk usage
du -sh /var/www/medcura-ai/*
df -h
```

### Compliance and Data Management

```bash
# Archive old patient data (HIPAA compliance)
sudo -u www-data php artisan tinker --execute="
\App\Models\PatientData::where('created_at', '<', now()->subYears(7))
    ->update(['archived' => true]);
"

# Review data retention policies
sudo mysql medcura_ai -e "
SELECT 'Appointments' as table_name, COUNT(*) as record_count FROM appointments
UNION ALL
SELECT 'Notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'Patient Data', COUNT(*) FROM patient_data
UNION ALL
SELECT 'Reviews', COUNT(*) FROM reviews;
"

# Generate compliance report
sudo -u www-data php artisan tinker --execute="
// Generate monthly compliance metrics
\$metrics = [
    'total_users' => \App\Models\User::count(),
    'active_users' => \App\Models\User::where('last_login_at', '>', now()->subMonth())->count(),
    'total_appointments' => \App\Models\Appointment::count(),
    'completed_appointments' => \App\Models\Appointment::where('status', 'completed')->count(),
    'failed_notifications' => \App\Models\Notification::where('failed_at', '!=', null)->count(),
];
file_put_contents(storage_path('logs/compliance_' . date('Y-m') . '.json'), json_encode(\$metrics, JSON_PRETTY_PRINT));
"
```

### Performance Analysis

```bash
# Analyze slow queries
sudo mysql -e "
SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_sec
FROM performance_schema.events_statements_summary_by_digest
WHERE avg_timer_wait > 1000000000000  -- Queries taking >1 second on average
ORDER BY avg_timer_wait DESC
LIMIT 10;
"

# Check index usage
sudo mysql -e "
SELECT object_schema, object_name, index_name, count_read, count_fetch, count_insert, count_update, count_delete
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = 'medcura_ai'
ORDER BY (count_read + count_fetch) DESC
LIMIT 20;
"

# Review application performance metrics
sudo -u www-data php artisan tinker --execute="
// Check average response times
\$slowRequests = \Illuminate\Support\Facades\DB::select('
    SELECT DATE(created_at) as date,
           AVG(response_time) as avg_response_time,
           COUNT(*) as total_requests
    FROM request_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
');
print_r(\$slowRequests);
"
```

## Security Maintenance

### Access Control Review

```bash
# Review sudo access
sudo cat /etc/sudoers.d/*

# Check SSH access
sudo cat /var/log/auth.log | grep "sshd" | tail -20

# Review file permissions
sudo find /var/www/medcura-ai -type f -perm 777
sudo find /var/www/medcura-ai -type d -perm 777

# Check for world-writable files
sudo find /var/www -type f -perm -o+w
```

### Security Updates and Patching

```bash
# Update all packages
sudo apt update && sudo apt upgrade -y

# Check for kernel updates
sudo apt list --upgradable | grep linux

# Update PHP and extensions
sudo apt install --only-upgrade php*

# Restart services
sudo systemctl restart nginx php8.1-fpm mysql redis

# Check for Laravel security updates
sudo -u www-data composer update --dry-run | grep -i security
```

### Intrusion Detection

```bash
# Check Fail2Ban status
sudo fail2ban-client status

# Review firewall rules
sudo ufw status verbose

# Check for suspicious log entries
sudo grep "Failed password" /var/log/auth.log | tail -10
sudo grep "Invalid user" /var/log/auth.log | tail -10
```

### Data Encryption Verification

```bash
# Verify SSL certificate
openssl s_client -connect your-domain.com:443 -servername your-domain.com < /dev/null | openssl x509 -noout -dates

# Check database encryption (if enabled)
sudo mysql -e "SHOW VARIABLES LIKE 'innodb_encrypt%';"

# Verify backup encryption
file /var/backups/medcura-ai/db_backup_*.gpg 2>/dev/null || echo "Backups not encrypted!"
```

## Performance Monitoring

### Real-time Monitoring

```bash
# Monitor system resources
watch -n 5 'uptime; free -h; df -h /'

# Monitor PHP-FPM processes
sudo systemctl status php8.1-fpm
ps aux | grep php | wc -l

# Monitor database connections
sudo mysql -e "SHOW STATUS LIKE 'Threads_connected';"
sudo mysql -e "SHOW PROCESSLIST;" | wc -l

# Monitor Redis
redis-cli info | grep -E "(connected_clients|used_memory|total_commands_processed)"
```

### Application Performance Metrics

```bash
# Check Laravel Telescope (if installed)
sudo -u www-data php artisan telescope:prune

# Monitor queue performance
sudo -u www-data php artisan queue:status
redis-cli LLEN queues:default

# Check cache hit rates
redis-cli info | grep -E "(keyspace_hits|keyspace_misses)"
```

### Database Performance

```bash
# Monitor slow queries
sudo mysql -e "
SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_sec, max_timer_wait/1000000000 as max_time_sec
FROM performance_schema.events_statements_summary_by_digest
WHERE avg_timer_wait > 500000000000  -- >0.5 seconds
ORDER BY avg_timer_wait DESC
LIMIT 20;
"

# Check table locks
sudo mysql -e "
SELECT r.trx_id waiting_trx_id,
       r.trx_mysql_thread_id waiting_thread,
       r.trx_query waiting_query,
       b.trx_id blocking_trx_id,
       b.trx_mysql_thread_id blocking_thread,
       b.trx_query blocking_query
FROM information_schema.innodb_lock_waits w
INNER JOIN information_schema.innodb_trx b ON b.trx_id = w.blocking_trx_id
INNER JOIN information_schema.innodb_trx r ON r.trx_id = w.requesting_trx_id;
"
```

## Backup and Recovery

### Backup Procedures

```bash
# Manual database backup
DATE=$(date +%Y%m%d_%H%M%S)
sudo mysqldump -u medcura_user -p medcura_ai > /var/backups/medcura-ai/manual_db_$DATE.sql
gzip /var/backups/medcura-ai/manual_db_$DATE.sql

# Manual file backup
sudo tar -czf /var/backups/medcura-ai/manual_files_$DATE.tar.gz \
    --exclude="/var/www/medcura-ai/vendor" \
    --exclude="/var/www/medcura-ai/node_modules" \
    --exclude="/var/www/medcura-ai/storage/logs" \
    /var/www/medcura-ai

# Backup configuration files
sudo tar -czf /var/backups/medcura-ai/config_$DATE.tar.gz \
    /etc/nginx/sites-available/medcura-ai \
    /etc/php/8.1/fpm/php.ini \
    /etc/mysql/mysql.conf.d/mysqld.cnf
```

### Recovery Procedures

#### Database Recovery

```bash
# Stop application
sudo systemctl stop nginx php8.1-fpm

# Restore database
mysql -u medcura_user -p medcura_ai < /var/backups/medcura-ai/db_backup_20231201_020000.sql

# Restart application
sudo systemctl start nginx php8.1-fpm

# Verify data integrity
sudo -u www-data php artisan tinker --execute="
echo 'Users: ' . \App\Models\User::count() . PHP_EOL;
echo 'Appointments: ' . \App\Models\Appointment::count() . PHP_EOL;
"
```

#### File System Recovery

```bash
# Stop application
sudo systemctl stop nginx php8.1-fpm

# Restore files
sudo tar -xzf /var/backups/medcura-ai/files_backup_20231201_030000.tar.gz -C /

# Restore permissions
sudo chown -R www-data:www-data /var/www/medcura-ai
sudo find /var/www/medcura-ai -type f -exec chmod 644 {} \;
sudo find /var/www/medcura-ai -type d -exec chmod 755 {} \;

# Restart application
sudo systemctl start nginx php8.1-fpm
```

### Backup Testing

```bash
# Test database backup restoration
mysql -e "CREATE DATABASE test_restore;"
mysql test_restore < /var/backups/medcura-ai/db_backup_*.sql
mysql -e "DROP DATABASE test_restore;"

# Test file backup integrity
tar -tzf /var/backups/medcura-ai/files_backup_*.tar.gz > /dev/null
```

## Incident Response

### Severity Levels

1. **Critical**: System completely down, data loss, security breach
2. **High**: Major functionality broken, affecting many users
3. **Medium**: Limited functionality affected, workarounds available
4. **Low**: Minor issues, not affecting core functionality

### Response Procedures

#### Critical Incident Response

1. **Immediate Actions**:
   ```bash
   # Assess the situation
   sudo systemctl status nginx php8.1-fpm mysql redis

   # Isolate the issue
   sudo systemctl stop nginx  # Prevent further damage

   # Notify stakeholders
   # Send alerts to development team and management
   ```

2. **Investigation**:
   ```bash
   # Check recent logs
   sudo tail -100 /var/log/nginx/error.log
   sudo tail -100 /var/www/medcura-ai/storage/logs/laravel.log
   sudo tail -100 /var/log/mysql/error.log

   # Check system resources
   dmesg | tail -50
   journalctl -u nginx -u php8.1-fpm -u mysql --since "1 hour ago"
   ```

3. **Recovery**:
   ```bash
   # Restore from backup if necessary
   # Follow recovery procedures above

   # Restart services gradually
   sudo systemctl start mysql
   sudo systemctl start redis
   sudo systemctl start php8.1-fpm
   sudo systemctl start nginx
   ```

#### Communication Plan

- **Internal Communication**: Slack/Teams channel for technical team
- **External Communication**: Status page, email notifications to users
- **Escalation**: Management notification for critical incidents

### Post-Incident Review

After incident resolution:

```bash
# Document the incident
sudo nano /var/log/incidents/$(date +%Y%m%d)_incident_report.md

# Review and update procedures
# Identify root cause
# Implement preventive measures
# Update monitoring if needed
```

## Compliance Maintenance

### HIPAA Compliance

```bash
# Regular compliance checks
sudo -u www-data php artisan tinker --execute="
// Check for unencrypted data transmissions
\$unencryptedConnections = \Illuminate\Support\Facades\DB::select('
    SELECT COUNT(*) as unencrypted FROM user_sessions
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND encrypted = 0
');

// Review audit logs
\$auditEntries = \App\Models\AuditLog::where('created_at', '>', now()->subMonth())->count();

// Check data retention compliance
\$oldRecords = \App\Models\PatientData::where('created_at', '<', now()->subYears(7))->count();

echo 'Compliance Check Results:' . PHP_EOL;
echo 'Unencrypted connections: ' . \$unencryptedConnections[0]->unencrypted . PHP_EOL;
echo 'Audit entries (30 days): ' . \$auditEntries . PHP_EOL;
echo 'Records >7 years old: ' . \$oldRecords . PHP_EOL;
"
```

### Data Privacy

```bash
# Review data access logs
sudo mysql medcura_ai -e "
SELECT user_id, COUNT(*) as access_count,
       MAX(created_at) as last_access
FROM data_access_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY user_id
ORDER BY access_count DESC
LIMIT 20;
"

# Check for unauthorized access attempts
sudo grep "unauthorized" /var/www/medcura-ai/storage/logs/laravel.log | tail -10
```

### Security Audits

```bash
# Run security scans
sudo apt install lynis
sudo lynis audit system

# Check file integrity
sudo find /var/www/medcura-ai -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

# Review user permissions
sudo -u www-data php artisan tinker --execute="
\App\Models\User::with('permissions')->get()->each(function(\$user) {
    \$restrictedPerms = \$user->permissions->where('is_restricted', true);
    if (\$restrictedPerms->count() > 0) {
        echo \$user->name . ' has ' . \$restrictedPerms->count() . ' restricted permissions' . PHP_EOL;
    }
});
"
```

## System Updates

### Laravel Updates

```bash
# Check for Laravel updates
sudo -u www-data composer outdated

# Update Laravel (test in staging first)
sudo -u www-data composer update laravel/framework

# Run migrations if needed
sudo -u www-data php artisan migrate

# Clear and rebuild caches
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize
```

### Dependency Updates

```bash
# Update PHP dependencies
sudo -u www-data composer update

# Update Node.js dependencies
sudo -u www-data npm audit
sudo -u www-data npm update

# Rebuild assets
sudo -u www-data npm run production
```

### Database Schema Updates

```bash
# Check for pending migrations
sudo -u www-data php artisan migrate:status

# Run migrations
sudo -u www-data php artisan migrate

# Seed new data if needed
sudo -u www-data php artisan db:seed
```

## Troubleshooting Procedures

### Application Issues

#### High Memory Usage

```bash
# Check memory usage
ps aux --sort=-%mem | head -10

# Check PHP-FPM configuration
sudo cat /etc/php/8.1/fpm/pool.d/www.conf | grep -E "(pm|max_children)"

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

#### Slow Response Times

```bash
# Check database slow queries
sudo mysql -e "
SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_sec
FROM performance_schema.events_statements_summary_by_digest
WHERE avg_timer_wait > 1000000000
ORDER BY avg_timer_wait DESC
LIMIT 10;
"

# Check cache hit rates
redis-cli info | grep -E "(keyspace_hits|keyspace_misses)"

# Clear application cache
sudo -u www-data php artisan optimize:clear
```

#### Queue Processing Issues

```bash
# Check queue status
sudo -u www-data php artisan queue:status

# Check failed jobs
sudo -u www-data php artisan queue:failed

# Restart queue worker
sudo systemctl restart queue-worker

# Clear failed jobs if needed
sudo -u www-data php artisan queue:clear
```

### Database Issues

#### Connection Pool Exhaustion

```bash
# Check connection count
sudo mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# Check max connections
sudo mysql -e "SHOW VARIABLES LIKE 'max_connections';"

# Kill idle connections if needed
sudo mysql -e "
SELECT CONCAT('KILL ', id, ';') AS kill_query
FROM information_schema.processlist
WHERE user = 'medcura_user'
AND time > 300;  -- Idle for more than 5 minutes
" > /tmp/kill_idle.sql

sudo mysql < /tmp/kill_idle.sql
```

#### Deadlocks

```bash
# Check for deadlocks
sudo mysql -e "SHOW ENGINE INNODB STATUS\G" | grep -A 20 "LATEST DETECTED DEADLOCK"

# Review deadlock-prone queries
sudo mysql -e "
SELECT sql_text, count_star
FROM performance_schema.events_statements_summary_by_digest
WHERE sql_text LIKE '%UPDATE%' OR sql_text LIKE '%INSERT%' OR sql_text LIKE '%DELETE%'
ORDER BY count_star DESC
LIMIT 10;
"
```

### Network Issues

#### SSL Certificate Problems

```bash
# Check certificate expiration
openssl x509 -in /etc/ssl/certs/medcura.crt -text -noout | grep "Not After"

# Renew certificate
sudo certbot renew

# Restart web server
sudo systemctl restart nginx
```

#### DNS Issues

```bash
# Check DNS resolution
nslookup your-domain.com

# Check DNS propagation
dig your-domain.com

# Clear DNS cache if needed
sudo systemctl restart systemd-resolved
```

### Monitoring and Alerting

#### Set Up Alerts

```bash
# Install monitoring tools
sudo apt install prometheus-node-exporter alertmanager

# Configure alerts for:
# - High CPU usage (>80%)
# - High memory usage (>90%)
# - Low disk space (<10% free)
# - Service down (nginx, php-fpm, mysql, redis)
# - SSL certificate expiration (<30 days)
# - Failed backups
# - High error rates
```

#### Log Monitoring

```bash
# Monitor error logs
sudo tail -f /var/www/medcura-ai/storage/logs/laravel.log | grep -i error

# Set up log alerts
# Configure alerts for:
# - PHP fatal errors
# - Database connection errors
# - Authentication failures
# - Security violations
```

---

**Maintenance Schedule Summary:**

- **Daily**: Health checks, log rotation, basic monitoring
- **Weekly**: Cache clearing, security updates, performance optimization
- **Monthly**: Comprehensive audit, compliance review, backup testing
- **Quarterly**: Major updates, security audits, performance reviews
- **Annually**: Full system review, disaster recovery testing

**Contact Information:**
- **System Administrator**: sysadmin@medcura.ai
- **Development Team**: dev@medcura.ai
- **Emergency Hotline**: +1-XXX-XXX-XXXX

**Last Updated**: November 2024
**Version**: 1.0.0
