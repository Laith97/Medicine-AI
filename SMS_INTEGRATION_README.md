# SMS Provider Integration for MedcuraAI

This document describes the comprehensive SMS provider integration implemented for the MedcuraAI system, supporting multiple SMS providers with dynamic switching capabilities.

## Overview

The SMS integration supports three major providers:
- **Twilio** - Industry-leading SMS service
- **Plivo** - Cost-effective SMS platform
- **MessageBird** - Global communications platform

## Architecture

### Strategy Pattern Implementation

The system uses the Strategy Pattern for clean, scalable SMS provider management:

```
SmsService (Context)
├── SmsProviderInterface (Strategy Interface)
├── TwilioProvider (Concrete Strategy)
├── PlivoProvider (Concrete Strategy)
├── MessageBirdProvider (Concrete Strategy)
└── LogProvider (Testing Strategy)
```

### Key Components

1. **SmsProviderInterface** - Contract defining SMS provider methods
2. **Provider Classes** - Individual implementations for each SMS service
3. **SmsService** - Central service managing provider selection and SMS sending
4. **Admin Panel** - Web interface for provider configuration and testing
5. **SystemSetting Model** - Database storage for dynamic provider selection

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# SMS Configuration
SMS_PROVIDER=log

# Twilio Configuration
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890

# Plivo Configuration
PLIVO_AUTH_ID=your_auth_id
PLIVO_AUTH_TOKEN=your_auth_token
PLIVO_FROM_NUMBER=+1234567890

# MessageBird Configuration
MESSAGEBIRD_ACCESS_KEY=your_access_key
MESSAGEBIRD_FROM_NUMBER=YourCompany
```

### Provider-Specific Setup

#### Twilio Setup
1. Create account at [twilio.com](https://twilio.com)
2. Get Account SID and Auth Token from Console
3. Purchase a phone number
4. Add credentials to `.env`

#### Plivo Setup
1. Create account at [plivo.com](https://plivo.com)
2. Get Auth ID and Auth Token from Console
3. Purchase a phone number
4. Add credentials to `.env`

#### MessageBird Setup
1. Create account at [messagebird.com](https://messagebird.com)
2. Get Access Key from Dashboard
3. Set originator (phone number or company name)
4. Add credentials to `.env`

## Usage

### Basic SMS Sending

```php
use App\Services\SmsService;

$smsService = app(SmsService::class);

// Send SMS
$result = $smsService->send('+1234567890', 'Your message here');

if ($result['success']) {
    echo "SMS sent successfully: " . $result['message'];
} else {
    echo "SMS failed: " . $result['message'];
}
```

### Legacy Compatibility

For backward compatibility with existing code:

```php
$success = $smsService->sendLegacy('+1234567890', 'Your message here');
```

### Provider Management

```php
// Get current provider info
$providerName = $smsService->getProviderName();
$isConfigured = $smsService->isProviderConfigured();

// Get all available providers
$providers = $smsService->getAvailableProviders();

// Switch provider
$smsService->setActiveProvider('twilio');

// Send test SMS
$result = $smsService->sendTestSms('+1234567890');
```

## Admin Panel Features

### SMS Settings Page

Access via: `/admin/sms-settings`

Features:
- **Provider Selection** - Dropdown to choose active SMS provider
- **Configuration Status** - Visual indicators for each provider's setup status
- **Requirements Display** - Shows required environment variables for unconfigured providers
- **Test Functionality** - Send test SMS to verify provider configuration

### Provider Status Indicators

- 🟢 **Configured & Active** - Provider is set up and currently selected
- 🟡 **Configured** - Provider is set up but not active
- 🔴 **Not Configured** - Provider missing required credentials

## Database Storage

The active SMS provider is stored in the `system_settings` table:

```sql
INSERT INTO system_settings (key, value, type, description) 
VALUES ('sms_provider', 'twilio', 'string', 'Active SMS provider for the system');
```

This allows dynamic switching without code deployment.

## Error Handling

### Provider-Level Error Handling

Each provider implements comprehensive error handling:

```php
public function send(string $to, string $message): array
{
    try {
        // Provider-specific sending logic
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $responseData
        ];
    } catch (\Exception $e) {
        Log::error('Provider SMS failed', [
            'provider' => $this->getName(),
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'message' => 'Provider error: ' . $e->getMessage(),
            'data' => []
        ];
    }
}
```

### Service-Level Error Handling

The main SmsService provides additional error handling:

```php
try {
    return $this->providerInstance->send($to, $message);
} catch (\Exception $e) {
    Log::error('SMS sending failed', [
        'to' => $to,
        'provider' => $this->provider,
        'error' => $e->getMessage()
    ]);
    
    return [
        'success' => false,
        'message' => 'SMS service error: ' . $e->getMessage(),
        'data' => []
    ];
}
```

## Integration Points

### Patient Notifications

The system automatically sends SMS notifications when:
- New patient accounts are created via AI diagnosis
- New patient accounts are created via manual diagnosis

Example integration in controllers:

```php
// Send SMS notification if phone provided
if ($patient->phone) {
    $smsMessage = "Hello {$patient->name}, Dr. " . Auth::user()->name . " has created your medical account.";
    $result = $this->smsService->send($patient->phone, $smsMessage);
    
    if (!$result['success']) {
        \Log::warning('Failed to send SMS notification', [
            'patient_id' => $patient->id,
            'error' => $result['message']
        ]);
    }
}
```

## Testing

### Manual Testing via Admin Panel

1. Navigate to `/admin/sms-settings`
2. Configure your preferred provider's credentials in `.env`
3. Select the provider from the dropdown
4. Click "Update Provider"
5. Enter a test phone number
6. Click "Send Test SMS"

### Programmatic Testing

```php
// Test current provider
$smsService = app(\App\Services\SmsService::class);
$result = $smsService->sendTestSms('+1234567890');

// Test specific provider
$smsService->setActiveProvider('twilio');
$result = $smsService->send('+1234567890', 'Test message');
```

## Logging

All SMS activities are logged with appropriate levels:

- **Info**: Successful SMS sends
- **Warning**: Failed SMS sends (non-critical)
- **Error**: Provider configuration issues, API errors

Log entries include:
- Recipient phone number
- Provider used
- Success/failure status
- Error messages (if applicable)
- Timestamps

## Security Considerations

1. **Credential Protection**: All API credentials stored in environment variables
2. **Input Validation**: Phone numbers and messages validated before sending
3. **Rate Limiting**: Consider implementing rate limiting for SMS sends
4. **Audit Trail**: All SMS activities logged for compliance

## Scalability

### Adding New Providers

To add a new SMS provider:

1. Create provider class implementing `SmsProviderInterface`
2. Add provider configuration to `config/sms.php`
3. Update provider factory in `SmsService::createProviderInstance()`
4. Add environment variables to `.env.example`

Example new provider:

```php
<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;

class NewProvider implements SmsProviderInterface
{
    public function send(string $to, string $message): array
    {
        // Implementation
    }
    
    public function getName(): string
    {
        return 'New Provider';
    }
    
    public function isConfigured(): bool
    {
        // Check configuration
    }
    
    public function getConfigRequirements(): array
    {
        return [
            'NEW_PROVIDER_API_KEY' => 'API Key',
            'NEW_PROVIDER_FROM' => 'From Number'
        ];
    }
}
```

## Troubleshooting

### Common Issues

1. **Provider Not Configured**
   - Check environment variables are set correctly
   - Verify credentials with provider's dashboard
   - Ensure phone numbers include country codes

2. **SMS Not Sending**
   - Check provider account balance
   - Verify phone number format
   - Review application logs for error details

3. **Admin Panel Not Loading**
   - Clear Laravel caches: `php artisan cache:clear`
   - Check database connection
   - Verify admin authentication

### Debug Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check current configuration
php artisan tinker
>>> config('sms.default_provider')
>>> \App\Models\SystemSetting::get('sms_provider')

# Test SMS service
>>> $sms = app(\App\Services\SmsService::class);
>>> $sms->getProviderName();
>>> $sms->isProviderConfigured();
```

## Performance Considerations

1. **Provider Selection**: Database lookup cached for performance
2. **Connection Pooling**: HTTP clients reuse connections where possible
3. **Async Processing**: Consider queue jobs for bulk SMS sending
4. **Monitoring**: Implement provider response time monitoring

## Compliance

### GDPR Considerations
- Phone numbers are personal data - ensure proper consent
- Implement data retention policies
- Provide opt-out mechanisms

### Telecommunications Regulations
- Respect local SMS regulations
- Implement opt-out keywords (STOP, UNSUBSCRIBE)
- Maintain sending logs for compliance audits

## Support

For issues or questions regarding the SMS integration:

1. Check this documentation
2. Review application logs
3. Verify provider-specific documentation
4. Test with the log provider first to isolate issues

## Changelog

### Version 1.0.0 (Current)
- Initial implementation with Twilio, Plivo, and MessageBird support
- Admin panel for provider management
- Database-driven provider selection
- Comprehensive error handling and logging
- Test functionality for provider verification
