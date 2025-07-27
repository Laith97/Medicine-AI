# Monthly Invoicing and Restriction System

This document describes the monthly invoicing and access restriction system implemented for the Medical Assistant Application.

## Overview

The system provides comprehensive monthly billing management with automated invoice generation, payment tracking, reminder notifications, and access restrictions for unpaid accounts.

## Features

### Admin Controls
- **Monthly Amount Assignment**: Set custom monthly fees per doctor
- **Grace Period Configuration**: Define forgiveness period after due date
- **Reminder Frequency**: Control how often reminders are sent
- **Page Restrictions**: Configure which pages are restricted per user
- **Manual Override**: Unlock users manually when needed

### Automated Processes
- **Monthly Invoice Generation**: Automatic creation at month start
- **Email & SMS Notifications**: Multi-channel communication
- **Progressive Reminders**: Escalating urgency levels
- **Access Restrictions**: Automatic enforcement after grace period

### User Experience
- **Invoice Dashboard**: Complete payment history and status
- **PDF Downloads**: Professional invoice documents
- **Direct Payment**: Integrated Stripe payment processing
- **Restriction Warnings**: Clear communication about access limits

## Database Schema

### monthly_invoice_settings
- `user_id`: Foreign key to users table
- `monthly_amount`: Decimal amount to charge monthly
- `is_active`: Boolean to enable/disable monthly billing
- `grace_period_days`: Days after due date before restrictions
- `reminder_frequency_days`: How often to send reminders
- `restricted_pages`: JSON array of restricted route names
- `is_restricted`: Current restriction status
- `restriction_message`: Custom message for restricted users

### stripe_invoices (Enhanced)
- `invoice_type`: 'monthly', 'subscription', or 'manual'
- `invoice_month`: Month number (1-12) for monthly invoices
- `invoice_year`: Year for monthly invoices
- `grace_period_ends_at`: When grace period expires
- `reminder_count`: Number of reminders sent
- `last_reminder_sent_at`: Timestamp of last reminder
- `auto_generated`: Whether invoice was auto-created

## API Endpoints

### Admin Routes
```
GET    /admin/monthly-invoices              # List all users and settings
GET    /admin/monthly-invoices/{user}/edit  # Edit user settings
PUT    /admin/monthly-invoices/{user}       # Update user settings
POST   /admin/monthly-invoices/generate     # Generate monthly invoices
POST   /admin/monthly-invoices/bulk-update  # Bulk update settings
POST   /admin/monthly-invoices/{user}/restrict    # Restrict user access
POST   /admin/monthly-invoices/{user}/unrestrict  # Remove restrictions
POST   /admin/monthly-invoices/process-overdue    # Process overdue invoices
POST   /admin/monthly-invoices/process-payments   # Check payment status
```

### User Routes
```
GET    /invoices                    # User invoice dashboard
GET    /invoices/{invoice}          # View specific invoice
GET    /invoices/{invoice}/pay      # Payment page
GET    /invoices/{invoice}/pdf      # Download PDF
GET    /access/restricted           # Restriction warning page
GET    /access/check-status         # AJAX status check
```

## Console Commands

### Generate Monthly Invoices
```bash
php artisan invoices:generate-monthly --month=2024-01
```
Creates monthly invoices for all active users for the specified month.

### Process Overdue Invoices
```bash
php artisan invoices:process-overdue
```
Sends reminders and applies restrictions for overdue invoices.

## Job Queue

### CreateMonthlyInvoices
- Generates invoices for all users with active monthly billing
- Calculates due dates and grace periods
- Sends email and SMS notifications
- Handles Stripe invoice creation

### ProcessOverdueInvoices
- Identifies overdue invoices past grace period
- Sends progressive reminder notifications
- Applies access restrictions when appropriate
- Updates reminder counters and timestamps

### SendInvoiceReminder
- Sends individual reminder notifications
- Escalates urgency based on reminder count
- Supports both email and SMS channels

## Notifications

### MonthlyInvoiceCreated
- **Channels**: Email, SMS (if configured)
- **Trigger**: New monthly invoice generated
- **Content**: Invoice details, due date, payment link

### InvoiceReminder
- **Channels**: Email, SMS (if configured)
- **Trigger**: Overdue invoice processing
- **Content**: Progressive urgency, payment deadline warnings

## Middleware

### CheckAccessRestriction
- Validates user access to restricted pages
- Redirects to restriction warning page
- Allows payment-related pages during restriction
- Bypasses restrictions for admin users

## Configuration

### Environment Variables
```env
# Twilio SMS Configuration
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=your_twilio_phone_number

# Stripe Configuration
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

### Available Restricted Pages
- `ask-ai`: AI consultation feature
- `cases`: Patient case management
- `dashboard`: Main dashboard
- `profile.edit`: Profile editing
- `settings`: User settings

## Cron Schedule

Add to your crontab for automated processing:

```cron
# Generate monthly invoices on the 1st of each month at 9 AM
0 9 1 * * cd /path/to/app && php artisan invoices:generate-monthly

# Process overdue invoices daily at 10 AM
0 10 * * * cd /path/to/app && php artisan invoices:process-overdue
```

## Testing

Run the monthly invoicing tests:
```bash
php artisan test --filter=MonthlyInvoiceSystemTest
```

## Seeding Test Data

Create sample monthly invoice settings:
```bash
php artisan db:seed --class=MonthlyInvoiceSettingsSeeder
```

## Security Considerations

1. **Admin Access**: Only authenticated admin users can modify settings
2. **User Isolation**: Users can only view their own invoices
3. **Payment Security**: All payments processed through Stripe
4. **Data Validation**: Comprehensive input validation on all forms
5. **Rate Limiting**: API endpoints protected against abuse

## Troubleshooting

### Common Issues

1. **SMS Not Sending**: Verify Twilio credentials and phone number format
2. **Invoices Not Generating**: Check user has active monthly settings
3. **Restrictions Not Working**: Verify middleware is applied to routes
4. **Reminders Not Sending**: Ensure queue workers are running

### Debug Commands
```bash
# Check queue status
php artisan queue:work --verbose

# View failed jobs
php artisan queue:failed

# Test notification
php artisan tinker
>>> $user = User::find(1);
>>> $user->notify(new App\Notifications\MonthlyInvoiceCreated($invoice));
```

## Support

For technical support or feature requests related to the monthly invoicing system, please contact the development team.