# SMS Country-Based Routing System

## Overview

This system allows administrators to configure multiple SMS providers and assign specific countries to each provider. This enables intelligent routing of SMS messages based on the recipient's country code, optimizing delivery rates and costs.

## Features

✅ **Multiple Provider Support**: Twilio, Plivo, MessageBird, Unifonic, SMS Gateway Hub, and Log (testing)
✅ **Country-Based Routing**: Automatically route SMS based on phone number country code
✅ **Fallback Provider**: Unassigned countries use a fallback provider
✅ **Admin Interface**: Easy-to-use web interface for managing provider-country assignments
✅ **Conflict Prevention**: Ensures no country is assigned to multiple providers
✅ **Real-time Testing**: Test SMS functionality with country-based routing

## How It Works

1. **Phone Number Analysis**: When sending an SMS, the system extracts the country code from the phone number
2. **Provider Lookup**: Checks if the country has a specific provider assigned
3. **Fallback Logic**: If no specific provider is found, uses the fallback provider
4. **Message Delivery**: Routes the message through the appropriate provider

## Provider Configuration

### Environment Variables Required

#### Twilio
```env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=your_twilio_number
```

#### Plivo
```env
PLIVO_AUTH_ID=your_auth_id
PLIVO_AUTH_TOKEN=your_auth_token
PLIVO_FROM_NUMBER=your_plivo_number
```

#### MessageBird
```env
MESSAGEBIRD_ACCESS_KEY=your_access_key
MESSAGEBIRD_FROM_NUMBER=your_sender_id
```

#### Unifonic
```env
UNIFONIC_APP_SID=your_app_sid
UNIFONIC_SENDER_ID=your_sender_id
```

#### SMS Gateway Hub
```env
SMSGATEWAYHUB_EMAIL=your_account_email
SMSGATEWAYHUB_PASSWORD=your_account_password
SMSGATEWAYHUB_DEVICE=your_device_id
```

## Admin Interface

### Accessing SMS Settings
Navigate to `/admin/sms-settings` to manage SMS providers and country assignments.

### Features Available:
- **Provider Status**: View which providers are configured and active
- **Country Assignment**: Assign countries to specific providers via modal interface
- **Search Functionality**: Search countries when assigning
- **Bulk Operations**: Remove all assignments from a provider
- **Test SMS**: Send test messages to verify routing
- **Unassigned Countries**: View countries without specific provider assignments

## Usage Examples

### Example Country Assignments

**Unifonic** (Middle East & Arabic regions):
- Jordan (JO), Saudi Arabia (SA), UAE (AE), Kuwait (KW), Qatar (QA), Bahrain (BH), Oman (OM), Lebanon (LB), Egypt (EG)

**MessageBird** (Europe):
- UK (GB), Germany (DE), France (FR), Italy (IT), Spain (ES), Netherlands (NL), Belgium (BE), Switzerland (CH)

**Twilio** (North America):
- United States (US), Canada (CA), Mexico (MX)

**SMS Gateway Hub** (Asia):
- India (IN), Pakistan (PK), Bangladesh (BD), Philippines (PH), Thailand (TH), Malaysia (MY), Singapore (SG)

### Phone Number Examples
- `+962791234567` → Routes to Unifonic (Jordan)
- `+447123456789` → Routes to MessageBird (UK)
- `+1234567890` → Routes to Twilio (US)
- `+91987654321` → Routes to SMS Gateway Hub (India)
- `+61412345678` → Routes to fallback provider (Australia - unassigned)

## Database Schema

### `sms_provider_countries` Table
```sql
- id (primary key)
- provider_key (string) - Provider identifier
- country_code (string, 2 chars) - ISO 3166-1 alpha-2 country code
- country_name (string) - Human readable country name
- is_active (boolean) - Whether assignment is active
- created_at, updated_at (timestamps)

Indexes:
- Unique: [provider_key, country_code]
- Index: [country_code, is_active]
- Index: [provider_key, is_active]
```

## API Methods

### SmsService Methods
```php
// Send SMS with automatic routing
$smsService->send($phoneNumber, $message);

// Get provider for specific country
SmsProviderCountry::getProviderForCountry('JO'); // Returns 'unifonic'

// Assign countries to provider
$smsService->assignCountriesToProvider('unifonic', $countries);

// Remove provider assignments
$smsService->removeProviderCountryAssignments('unifonic');

// Get active providers with their countries
$smsService->getActiveProvidersWithCountries();
```

## Installation & Setup

1. **Run Migration**:
   ```bash
   php artisan migrate
   ```

2. **Seed Demo Data** (optional):
   ```bash
   php artisan db:seed --class=SmsProviderCountrySeeder
   ```

3. **Configure Providers**: Add environment variables for desired providers

4. **Access Admin Panel**: Go to `/admin/sms-settings` to configure country assignments

## Testing

### Test SMS Routing
1. Go to `/admin/sms-settings`
2. Enter a phone number with country code (e.g., `+962791234567`)
3. Click "Send Test SMS"
4. Check logs to see which provider was used

### Log Monitoring
SMS routing decisions are logged with:
- Phone number
- Detected country code
- Provider used
- Success/failure status

## Benefits

1. **Cost Optimization**: Use cheaper providers for specific regions
2. **Delivery Optimization**: Use providers with better delivery rates in specific countries
3. **Redundancy**: Fallback providers ensure message delivery
4. **Compliance**: Use local providers for regulatory compliance
5. **Scalability**: Easy to add new providers and reassign countries

## Troubleshooting

### Common Issues

1. **Provider Not Configured**: Ensure all required environment variables are set
2. **Country Not Routing**: Check if country is assigned to a configured provider
3. **Fallback Not Working**: Verify at least one provider is configured without country assignments
4. **Phone Number Format**: Ensure phone numbers include country codes

### Debug Information
Check Laravel logs for SMS routing decisions and any errors during message sending.

## Future Enhancements

- **Load Balancing**: Distribute load among multiple providers for the same country
- **Cost Tracking**: Track SMS costs per provider and country
- **Delivery Reports**: Monitor delivery success rates by provider and country
- **Auto-Failover**: Automatically switch to backup provider if primary fails
- **Scheduling**: Schedule country reassignments for different time periods
