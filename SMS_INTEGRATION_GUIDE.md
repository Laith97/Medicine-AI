# SMS Integration Guide

## Overview
SMS functionality has been integrated into both **cron job emails** and **manual reminder emails** to ensure users receive notifications via both email and SMS channels.

## What's Been Implemented

### 1. Manual Reminder Emails (AdminController)
- **Grace Period Reminders**: Now send both email and SMS
- **Warning Period Reminders**: Now send both email and SMS  
- **Overdue Reminders**: Now send both email and SMS

### 2. Cron Job Emails (Laravel Notifications)
- **GracePeriodReminder**: Updated to use SmsService instead of Twilio-only
- **FinalWarning**: Updated to use SmsService instead of Twilio-only
- **AccountRestricted**: Updated to use SmsService instead of Twilio-only

### 3. SMS Service Integration
- Uses the existing comprehensive `SmsService` that supports multiple providers
- Automatic country-based provider routing
- Fallback provider support
- Comprehensive logging

### 4. Custom SMS Channel
- Created `App\Channels\SmsChannel` for Laravel Notifications
- Registered in `AppServiceProvider`
- Handles SMS sending via the `SmsService`

## SMS Providers Supported

The system supports multiple SMS providers:
- **Twilio** (Global)
- **Plivo** (Global)
- **MessageBird** (Global)
- **Unifonic** (Middle East focused)
- **SMS Gateway Hub** (Android device-based)
- **Log** (Development/testing)

## Configuration

### Environment Variables
Add your SMS provider credentials to `.env`:

```env
# Choose your primary SMS provider
SMS_PROVIDER=twilio

# Twilio Configuration
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890

# Plivo Configuration (alternative)
PLIVO_AUTH_ID=your_auth_id
PLIVO_AUTH_TOKEN=your_auth_token
PLIVO_FROM_NUMBER=+1234567890

# MessageBird Configuration (alternative)
MESSAGEBIRD_ACCESS_KEY=your_access_key
MESSAGEBIRD_FROM_NUMBER=+1234567890

# Unifonic Configuration (Middle East)
UNIFONIC_APP_SID=your_app_sid
UNIFONIC_SENDER_ID=your_sender_id

# SMS Gateway Hub Configuration (Android device)
SMSGATEWAYHUB_EMAIL=your_email
SMSGATEWAYHUB_PASSWORD=your_password
SMSGATEWAYHUB_DEVICE=your_device_id
```

### Provider Selection
1. **Automatic**: Set `SMS_PROVIDER=auto` for country-based routing
2. **Specific**: Set `SMS_PROVIDER=twilio` (or plivo, messagebird, etc.)
3. **Development**: Set `SMS_PROVIDER=log` for testing

## How It Works

### Manual Reminders
When an admin sends manual reminders:
1. Email is sent via `EmailService`
2. If user has a phone number, SMS is sent via `SmsService`
3. Both successes and failures are logged
4. SMS failures don't prevent email sending

### Cron Job Reminders
When cron jobs run:
1. Laravel Notifications are triggered
2. Each notification checks if user has phone number
3. If yes, adds 'sms' to notification channels
4. Custom `SmsChannel` handles SMS sending
5. Uses `SmsService` for actual delivery

### SMS Message Examples

**Grace Period:**
```
🔔 MedCura AI: Your subscription expired but you're in grace period. 5 days remaining. Renew now: https://app.com/subscription
```

**Warning Period:**
```
🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in 2 days. Renew immediately: https://app.com/subscription
```

**Overdue:**
```
⚠️ MedCura AI: Your invoice of $29.99 is overdue. Update your payment method to avoid service interruption: https://app.com/subscription
```

**Account Restricted:**
```
❌ MedCura AI: Your account has been RESTRICTED due to expired subscription. Pay now to restore access: https://app.com/subscription
```

## Testing

### Test SMS Configuration
```bash
# Test SMS service
php artisan tinker
$sms = new \App\Services\SmsService();
$result = $sms->sendTestSms('+1234567890');
dd($result);
```

### Test Manual Reminders
1. Go to Admin Panel → Send Reminders
2. Select users with phone numbers
3. Send reminders
4. Check logs for SMS delivery status

### Test Cron Job Reminders
```bash
# Run subscription lifecycle processing
php artisan subscriptions:process-lifecycle
```

## Logging

All SMS activities are logged:
- **Success**: Provider used, delivery status
- **Failures**: Error messages, fallback attempts
- **Skipped**: Missing phone numbers, configuration issues

Check logs at: `storage/logs/laravel.log`

## Next Steps

1. **Add SMS Credentials**: Update your `.env` file with chosen provider credentials
2. **Test Configuration**: Use the testing commands above
3. **Monitor Logs**: Check SMS delivery success rates
4. **Configure Country Routing**: Set up country-specific providers if needed (via admin panel)

## Admin Panel Features

The system includes:
- SMS provider configuration
- Country-based routing setup
- Test SMS functionality
- Provider status monitoring

Access via: Admin Panel → SMS Settings

## Support

If you need help with:
- Provider setup
- Credential configuration
- Country routing
- Troubleshooting

Check the logs first, then contact support with specific error messages.