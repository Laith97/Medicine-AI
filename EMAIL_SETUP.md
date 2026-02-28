# Email Configuration Guide

This guide explains how to set up email delivery for both local development and production environments.

## Local Development Setup

For local development, we recommend using **Mailtrap** to test emails without sending real messages.

### 1. Sign up for Mailtrap
- Go to [mailtrap.io](https://mailtrap.io) and create a free account
- Create a new inbox for testing

### 2. Configure Environment
Copy `.env.local.example` to `.env.local` and update the mail settings:

```bash
cp .env.local.example .env.local
```

Update the mail configuration in `.env.local`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="test@medcuraai.com"
MAIL_FROM_NAME="MedCura AI (Local Test)"
```

### 3. Test Email Setup
Run the email test command:

```bash
php artisan email:test-all your-email@example.com
```

Or send a simple test email:

```bash
php artisan email:test your-email@example.com
```

## Production Setup

For production, the application is configured to use Gmail SMTP.

### 1. Gmail App Password Setup
- Enable 2-factor authentication on your Gmail account
- Generate an App Password: [Google Account Settings](https://myaccount.google.com/apppasswords)
- Use the app password (not your regular password) in the configuration

### 2. Update Production Environment
In your `.env.production` file, update the mail settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="MedCura AI"
```

### 3. Alternative: Use Your Existing SMTP
If you prefer to use a different SMTP provider, update the settings accordingly:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="MedCura AI"
```

## Available Email Commands

- `php artisan email:test {email}` - Send a basic test email
- `php artisan email:test-all {email?}` - Test all email templates
- `php artisan user:verify-email {email}` - Verify user email addresses

## Email Templates

Email templates are located in `resources/views/emails/`:
- `appointment-confirmation.blade.php`
- `contact.blade.php`
- `patient-account-created.blade.php`
- `reset-password.blade.php`
- `review-verification.blade.php`
- `subscription-confirmation.blade.php`
- `system-alert.blade.php`
- `usage-warning.blade.php`
- `test.blade.php` (for testing)

## Troubleshooting

### Common Issues

1. **Emails not sending in production**
   - Check that `MAIL_MAILER` is set to `smtp` (not `log`)
   - Verify SMTP credentials are correct
   - Check server firewall allows SMTP ports

2. **Gmail authentication errors**
   - Ensure you're using an App Password, not your regular password
   - Check that 2FA is enabled on the Gmail account

3. **Local development emails not appearing**
   - Verify Mailtrap credentials are correct
   - Check the Mailtrap inbox for received emails

### Testing Email Configuration

Run the comprehensive test:

```bash
php artisan email:test-all
```

This will test:
- Basic email configuration
- Contact form emails
- Reminder emails
- Email deliverability recommendations

## Security Notes

- Never commit real SMTP passwords to version control
- Use environment variables for all sensitive configuration
- For production, consider using dedicated SMTP services like SendGrid, Mailgun, or AWS SES for better deliverability