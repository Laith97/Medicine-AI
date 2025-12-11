# MedcuraAI Environment Configuration Guide

This guide documents all environment variables and configuration options required for the MedcuraAI medical practice management system.

## Table of Contents

1. [Application Configuration](#application-configuration)
2. [Database Configuration](#database-configuration)
3. [Cache and Session Configuration](#cache-and-session-configuration)
4. [Queue Configuration](#queue-configuration)
5. [Email Configuration](#email-configuration)
6. [External Services](#external-services)
7. [Security Configuration](#security-configuration)
8. [File Storage Configuration](#file-storage-configuration)
9. [Performance Configuration](#performance-configuration)
10. [Feature Flags](#feature-flags)
11. [Sample Environment Files](#sample-environment-files)

## Application Configuration

### Core Application Settings

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `APP_NAME` | Application name displayed in UI | MedcuraAI | Yes | `MedcuraAI` |
| `APP_ENV` | Application environment | local | Yes | `production` |
| `APP_KEY` | Laravel application key (32 chars) | Generated | Yes | `base64:your_app_key_here` |
| `APP_DEBUG` | Enable debug mode | false | Yes | `false` |
| `APP_URL` | Base application URL | http://localhost | Yes | `https://your-domain.com` |
| `APP_TIMEZONE` | Application timezone | UTC | No | `America/New_York` |
| `APP_LOCALE` | Default application locale | en | No | `en` |

### Logging Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `LOG_CHANNEL` | Logging channel | stack | No | `daily` |
| `LOG_DEPRECATIONS_CHANNEL` | Deprecation logging channel | null | No | `daily` |
| `LOG_LEVEL` | Minimum log level | debug | No | `error` |
| `LOG_STACK` | Log stack channels | single | No | `single,daily` |

## Database Configuration

### Primary Database

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `DB_CONNECTION` | Database driver | mysql | Yes | `mysql` |
| `DB_HOST` | Database host | 127.0.0.1 | Yes | `127.0.0.1` |
| `DB_PORT` | Database port | 3306 | Yes | `3306` |
| `DB_DATABASE` | Database name | laravel | Yes | `medcura_ai` |
| `DB_USERNAME` | Database username | root | Yes | `medcura_user` |
| `DB_PASSWORD` | Database password | null | Yes | `secure_password` |

### Database Performance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `DB_CONNECTION_TIMEOUT` | Connection timeout (seconds) | 60 | No | `30` |
| `DB_COMMAND_TIMEOUT` | Command timeout (seconds) | 0 | No | `300` |
| `DB_STRICT_MODE` | Enable strict mode | true | No | `true` |

### Read/Write Database Split (Optional)

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `DB_READ_HOST` | Read database host | Same as DB_HOST | No | `read-db.example.com` |
| `DB_READ_PORT` | Read database port | Same as DB_PORT | No | `3306` |
| `DB_READ_DATABASE` | Read database name | Same as DB_DATABASE | No | `medcura_ai_read` |
| `DB_READ_USERNAME` | Read database username | Same as DB_USERNAME | No | `medcura_read_user` |
| `DB_READ_PASSWORD` | Read database password | Same as DB_PASSWORD | No | `read_password` |

## Cache and Session Configuration

### Cache Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `CACHE_DRIVER` | Cache driver | file | Yes | `redis` |
| `CACHE_PREFIX` | Cache key prefix | laravel_cache | No | `medcura_cache` |
| `CACHE_DEFAULT_TTL` | Default cache TTL (seconds) | 3600 | No | `3600` |

### Session Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `SESSION_DRIVER` | Session driver | file | Yes | `redis` |
| `SESSION_LIFETIME` | Session lifetime (minutes) | 120 | Yes | `480` |
| `SESSION_ENCRYPT` | Encrypt session data | false | No | `true` |
| `SESSION_PATH` | Session cookie path | / | No | `/` |
| `SESSION_DOMAIN` | Session cookie domain | null | No | `your-domain.com` |
| `SESSION_SECURE_COOKIE` | Use secure cookies | false | Yes (production) | `true` |
| `SESSION_HTTP_ONLY` | HTTP only cookies | true | No | `true` |
| `SESSION_SAME_SITE` | Same-site cookie policy | lax | No | `strict` |

### Redis Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `REDIS_HOST` | Redis host | 127.0.0.1 | No | `127.0.0.1` |
| `REDIS_PASSWORD` | Redis password | null | No | `redis_password` |
| `REDIS_PORT` | Redis port | 6379 | No | `6379` |
| `REDIS_DB` | Redis database | 0 | No | `0` |
| `REDIS_CACHE_DB` | Cache Redis database | 1 | No | `1` |
| `REDIS_SESSION_DB` | Session Redis database | 2 | No | `2` |
| `REDIS_QUEUE_DB` | Queue Redis database | 3 | No | `3` |

## Queue Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `QUEUE_CONNECTION` | Queue driver | sync | Yes | `redis` |
| `QUEUE_FAILED_DRIVER` | Failed queue driver | database-uuids | No | `database-uuids` |

### Queue Performance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `QUEUE_WORKER_SLEEP` | Worker sleep time (seconds) | 3 | No | `3` |
| `QUEUE_WORKER_TRIES` | Max job attempts | 3 | No | `3` |
| `QUEUE_WORKER_TIMEOUT` | Job timeout (seconds) | 90 | No | `300` |
| `QUEUE_WORKER_MAX_JOBS` | Max jobs per worker | 0 (unlimited) | No | `1000` |

## Email Configuration

### SMTP Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `MAIL_MAILER` | Mail driver | smtp | Yes | `smtp` |
| `MAIL_HOST` | SMTP host | smtp.mailtrap.io | Yes | `smtp.gmail.com` |
| `MAIL_PORT` | SMTP port | 2525 | Yes | `587` |
| `MAIL_USERNAME` | SMTP username | null | Yes | `your-email@gmail.com` |
| `MAIL_PASSWORD` | SMTP password | null | Yes | `your-app-password` |
| `MAIL_ENCRYPTION` | SMTP encryption | null | No | `tls` |
| `MAIL_FROM_ADDRESS` | Default from address | null | Yes | `noreply@your-domain.com` |
| `MAIL_FROM_NAME` | Default from name | `${APP_NAME}` | No | `MedcuraAI` |

### Email Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `MAIL_QUEUE` | Queue email sending | false | No | `true` |
| `MAIL_VERIFY_PEER` | Verify SSL certificates | true | No | `true` |

## External Services

### Pusher (Real-time Notifications)

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `BROADCAST_DRIVER` | Broadcasting driver | log | Yes | `pusher` |
| `PUSHER_APP_ID` | Pusher app ID | null | Yes | `123456` |
| `PUSHER_APP_KEY` | Pusher app key | null | Yes | `your_app_key` |
| `PUSHER_APP_SECRET` | Pusher app secret | null | Yes | `your_app_secret` |
| `PUSHER_APP_CLUSTER` | Pusher cluster | mt1 | Yes | `us2` |
| `PUSHER_SCHEME` | Pusher scheme | http | No | `https` |
| `PUSHER_USE_TLS` | Use TLS for Pusher | false | No | `true` |

### Stripe (Payment Processing)

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `STRIPE_KEY` | Stripe publishable key | null | Yes | `pk_live_...` |
| `STRIPE_SECRET` | Stripe secret key | null | Yes | `sk_live_...` |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook secret | null | Yes | `whsec_...` |
| `STRIPE_CURRENCY` | Default currency | usd | No | `usd` |
| `STRIPE_API_VERSION` | Stripe API version | 2023-10-16 | No | `2023-10-16` |

### OpenAI (AI Features)

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `OPENAI_API_KEY` | OpenAI API key | null | Yes | `sk-...` |
| `OPENAI_MODEL` | Default AI model | gpt-4 | No | `gpt-4-turbo` |
| `OPENAI_MAX_TOKENS` | Max tokens per request | 2000 | No | `4000` |
| `OPENAI_TEMPERATURE` | AI creativity level | 0.7 | No | `0.7` |
| `OPENAI_TIMEOUT` | Request timeout (seconds) | 60 | No | `120` |

### Google Services

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | null | No | `your_client_id` |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret | null | No | `your_client_secret` |
| `GOOGLE_REDIRECT_URI` | Google OAuth redirect URI | null | No | `https://your-domain.com/auth/google/callback` |

## Security Configuration

### Authentication

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `SANCTUM_STATEFUL_DOMAINS` | Sanctum stateful domains | localhost,127.0.0.1 | No | `your-domain.com,api.your-domain.com` |
| `SANCTUM_GUARD` | Sanctum guard | web | No | `web` |

### CORS Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `CORS_ALLOWED_ORIGINS` | Allowed CORS origins | * | No | `https://your-domain.com,https://app.your-domain.com` |
| `CORS_ALLOWED_METHODS` | Allowed CORS methods | * | No | `GET,POST,PUT,DELETE,OPTIONS` |
| `CORS_ALLOWED_HEADERS` | Allowed CORS headers | * | No | `Content-Type,Authorization,X-Requested-With` |
| `CORS_EXPOSED_HEADERS` | Exposed CORS headers |  | No | `X-Custom-Header` |
| `CORS_MAX_AGE` | CORS max age | 0 | No | `86400` |
| `CORS_SUPPORTS_CREDENTIALS` | Support credentials | false | No | `true` |

### Rate Limiting

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `RATE_LIMIT_ENABLED` | Enable rate limiting | true | No | `true` |
| `RATE_LIMIT_ATTEMPTS` | Max attempts per window | 60 | No | `60` |
| `RATE_LIMIT_DECAY_MINUTES` | Rate limit window (minutes) | 1 | No | `1` |

## File Storage Configuration

### Local Storage

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `FILESYSTEM_DISK` | Default filesystem disk | local | Yes | `local` |

### AWS S3 Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `AWS_ACCESS_KEY_ID` | AWS access key ID | null | No | `AKIA...` |
| `AWS_SECRET_ACCESS_KEY` | AWS secret access key | null | No | `your_secret_key` |
| `AWS_DEFAULT_REGION` | AWS default region | us-east-1 | No | `us-west-2` |
| `AWS_BUCKET` | S3 bucket name | null | No | `medcura-uploads` |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Use path-style endpoints | false | No | `false` |

### Cloudinary (Optional)

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `CLOUDINARY_CLOUD_NAME` | Cloudinary cloud name | null | No | `your_cloud_name` |
| `CLOUDINARY_API_KEY` | Cloudinary API key | null | No | `your_api_key` |
| `CLOUDINARY_API_SECRET` | Cloudinary API secret | null | No | `your_api_secret` |

## Performance Configuration

### Application Performance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `APP_OPTIMIZE_CONFIG` | Optimize config loading | false | No | `true` |
| `APP_OPTIMIZE_ROUTES` | Optimize route loading | false | No | `true` |
| `APP_OPTIMIZE_VIEWS` | Optimize view loading | false | No | `true` |

### PHP Performance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `PHP_MEMORY_LIMIT` | PHP memory limit | 128M | No | `256M` |
| `PHP_MAX_EXECUTION_TIME` | Max execution time | 30 | No | `300` |
| `PHP_MAX_INPUT_TIME` | Max input time | 60 | No | `300` |
| `PHP_POST_MAX_SIZE` | Max POST size | 8M | No | `50M` |
| `PHP_UPLOAD_MAX_FILESIZE` | Max upload file size | 2M | No | `50M` |

### Database Performance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `DB_CONNECTION_POOL_SIZE` | Connection pool size | 10 | No | `20` |
| `DB_QUERY_TIMEOUT` | Query timeout (seconds) | 30 | No | `60` |
| `DB_SLOW_QUERY_THRESHOLD` | Slow query threshold (seconds) | 1 | No | `2` |

## Feature Flags

### Notification Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `NOTIFICATION_SOUND_ENABLED` | Enable notification sounds | true | No | `true` |
| `NOTIFICATION_BADGE_ENABLED` | Enable notification badges | true | No | `true` |
| `NOTIFICATION_TOAST_ENABLED` | Enable toast notifications | true | No | `true` |
| `DEFAULT_NOTIFICATION_PAGINATION` | Default pagination limit | 10 | No | `20` |

### AI Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `AI_VOICE_ASSISTANT_ENABLED` | Enable voice assistant | true | No | `true` |
| `AI_PRESCRIPTION_SUGGESTIONS_ENABLED` | Enable prescription AI | true | No | `true` |
| `AI_DIAGNOSIS_ASSISTANT_ENABLED` | Enable diagnosis AI | true | No | `true` |
| `AI_MAX_DAILY_REQUESTS` | Max AI requests per day | 1000 | No | `5000` |

### Billing Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `BILLING_ENABLED` | Enable billing features | true | No | `true` |
| `BILLING_CURRENCY` | Default billing currency | USD | No | `USD` |
| `BILLING_TAX_RATE` | Default tax rate | 0.00 | No | `0.08` |
| `BILLING_GRACE_PERIOD_DAYS` | Grace period in days | 7 | No | `14` |

### Kiosk Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `KIOSK_ENABLED` | Enable kiosk functionality | true | No | `true` |
| `KIOSK_SESSION_TIMEOUT` | Session timeout (minutes) | 30 | No | `60` |
| `KIOSK_MAX_CHECKINS_PER_HOUR` | Max check-ins per hour | 100 | No | `200` |

### Analytics Features

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `ANALYTICS_ENABLED` | Enable analytics | true | No | `true` |
| `ANALYTICS_RETENTION_DAYS` | Data retention (days) | 365 | No | `730` |
| `ANALYTICS_CACHE_TTL` | Analytics cache TTL | 3600 | No | `1800` |

## System Settings

### SaaS Configuration

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `SAAS_PROFESSIONAL_MONTHLY` | Professional monthly price | 30 | No | `49` |
| `SAAS_PROFESSIONAL_YEARLY` | Professional yearly price | 300 | No | `490` |
| `TRIAL_DAYS` | Trial period length | 14 | No | `30` |
| `SHOW_PRICING_SECTION` | Show pricing on landing | true | No | `true` |

### HIPAA Compliance

| Variable | Description | Default | Required | Example |
|----------|-------------|---------|----------|---------|
| `HIPAA_COMPLIANCE_ENABLED` | Enable HIPAA features | true | No | `true` |
| `DATA_RETENTION_YEARS` | Data retention period | 7 | No | `7` |
| `AUDIT_LOG_RETENTION_DAYS` | Audit log retention | 2555 | No | `2555` |

## Sample Environment Files

### Development Environment (.env)

```bash
# Application
APP_NAME=MedcuraAI
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcura_ai
DB_USERNAME=root
DB_PASSWORD=

# Cache & Sessions
CACHE_DRIVER=file
SESSION_DRIVER=file

# Queue
QUEUE_CONNECTION=sync

# Mail (using Mailtrap for development)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Broadcasting (using log for development)
BROADCAST_DRIVER=log

# OpenAI (use test key for development)
OPENAI_API_KEY=sk-test-your-openai-key

# Feature Flags
NOTIFICATION_SOUND_ENABLED=true
AI_VOICE_ASSISTANT_ENABLED=true
BILLING_ENABLED=false
```

### Production Environment (.env)

```bash
# Application
APP_NAME=MedcuraAI
APP_ENV=production
APP_KEY=base64:your_production_app_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=America/New_York

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=medcura_ai_prod
DB_USERNAME=medcura_prod_user
DB_PASSWORD=your_secure_db_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

REDIS_HOST=your-redis-cluster.amazonaws.com
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your_ses_username
MAIL_PASSWORD=your_ses_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="MedcuraAI"

# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=us2
PUSHER_USE_TLS=true

# Stripe
STRIPE_KEY=pk_live_your_publishable_key
STRIPE_SECRET=sk_live_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret

# OpenAI
OPENAI_API_KEY=sk-live-your-openai-key
OPENAI_MODEL=gpt-4-turbo
OPENAI_MAX_TOKENS=4000

# AWS S3
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=medcura-production-uploads

# Security
SANCTUM_STATEFUL_DOMAINS=your-domain.com,api.your-domain.com
CORS_ALLOWED_ORIGINS=https://your-domain.com,https://app.your-domain.com

# Performance
APP_OPTIMIZE_CONFIG=true
APP_OPTIMIZE_ROUTES=true
APP_OPTIMIZE_VIEWS=true

# Feature Flags
NOTIFICATION_SOUND_ENABLED=true
AI_VOICE_ASSISTANT_ENABLED=true
BILLING_ENABLED=true
KIOSK_ENABLED=true
ANALYTICS_ENABLED=true

# SaaS Settings
SAAS_PROFESSIONAL_MONTHLY=49
SAAS_PROFESSIONAL_YEARLY=490
TRIAL_DAYS=14
```

### Staging Environment (.env.staging)

```bash
# Application
APP_NAME="MedcuraAI (Staging)"
APP_ENV=staging
APP_KEY=base64:your_staging_app_key_here
APP_DEBUG=false
APP_URL=https://staging.your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=staging-db-host.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=medcura_ai_staging
DB_USERNAME=medcura_staging_user
DB_PASSWORD=staging_db_password

# Use same Redis, Pusher, etc. as production but with staging credentials
# ... (configure appropriately for staging environment)

# Feature Flags (enable testing features)
APP_DEBUG=true
BILLING_ENABLED=false
```

## Environment Validation

### Required Environment Variables Check

Create a validation script to ensure all required environment variables are set:

```php
// In AppServiceProvider or dedicated config validator
$requiredVars = [
    'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_URL',
    'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
    'MAIL_MAILER', 'MAIL_FROM_ADDRESS',
    'BROADCAST_DRIVER',
    'OPENAI_API_KEY',
];

foreach ($requiredVars as $var) {
    if (empty(env($var))) {
        throw new Exception("Required environment variable {$var} is not set");
    }
}
```

### Environment-Specific Validation

```php
// Production-specific validations
if (app()->environment('production')) {
    // Ensure HTTPS
    if (!str_starts_with(env('APP_URL'), 'https://')) {
        throw new Exception('Production must use HTTPS');
    }

    // Ensure secure broadcasting
    if (env('BROADCAST_DRIVER') === 'pusher' && !env('PUSHER_USE_TLS')) {
        throw new Exception('Production Pusher must use TLS');
    }

    // Ensure secure cookies
    if (!env('SESSION_SECURE_COOKIE')) {
        throw new Exception('Production must use secure cookies');
    }
}
```

## Security Considerations

### Sensitive Data Handling

1. **Never commit secrets** to version control
2. **Use environment-specific keys** for each deployment
3. **Rotate keys regularly** (quarterly minimum)
4. **Use secure key management** services (AWS KMS, Azure Key Vault, etc.)

### Environment File Security

```bash
# Secure .env file permissions
sudo chmod 600 .env

# Exclude from version control
echo ".env" >> .gitignore
echo ".env.*" >> .gitignore

# Create .env.example with dummy values
cp .env.example .env
# Replace real values with placeholders
```

### Database Credentials

- Use strong, unique passwords for database users
- Restrict database user privileges to minimum required
- Use SSL/TLS for database connections in production
- Regularly rotate database credentials

---

**Configuration Checklist:**

- [ ] All required environment variables are set
- [ ] Database connection is configured and tested
- [ ] Cache and session drivers are configured
- [ ] Email configuration is tested
- [ ] External services (Pusher, Stripe, OpenAI) are configured
- [ ] SSL/TLS certificates are installed and valid
- [ ] File permissions are properly set
- [ ] Application key is generated and secure
- [ ] Environment-specific configurations are applied

**Last Updated**: November 2024
**Version**: 1.0.0
