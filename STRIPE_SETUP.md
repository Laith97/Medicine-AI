# Stripe Subscription System Setup Guide

This guide will help you set up the complete Stripe subscription and billing system for the Medical AI application.

## 🚀 Quick Start

### 1. Install Dependencies
The Stripe PHP SDK is already installed. If you need to reinstall:
```bash
composer require stripe/stripe-php
```

### 2. Environment Configuration
Copy the example environment file and configure Stripe settings:
```bash
cp .env.example .env
```

Add your Stripe credentials to `.env`:
```env
# Stripe Configuration
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
STRIPE_ENVIRONMENT=test

# Stripe Price IDs (create these in your Stripe dashboard)
STRIPE_BASIC_MONTHLY_PRICE_ID=price_basic_monthly_id
STRIPE_BASIC_YEARLY_PRICE_ID=price_basic_yearly_id
STRIPE_PRO_MONTHLY_PRICE_ID=price_pro_monthly_id
STRIPE_PRO_YEARLY_PRICE_ID=price_pro_yearly_id
STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_enterprise_monthly_id
STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_enterprise_yearly_id
```

### 3. Database Setup
Run the migrations to create the necessary tables:
```bash
php artisan migrate
```

This creates:
- `subscriptions` table - stores user subscription data
- `openai_usages` table - tracks token usage and costs
- Adds subscription fields to `users` table

### 4. Queue Configuration
Configure queues for email notifications:
```bash
# In .env
QUEUE_CONNECTION=database

# Run queue worker
php artisan queue:work
```

## 🏗️ Stripe Dashboard Setup

### 1. Create Products and Prices
In your Stripe Dashboard, create the following products with their respective prices:

#### Basic Plan
- **Product Name**: Basic Plan
- **Monthly Price**: $29.00 (price_basic_monthly_id)
- **Yearly Price**: $290.00 (price_basic_yearly_id)

#### Professional Plan
- **Product Name**: Professional Plan
- **Monthly Price**: $79.00 (price_pro_monthly_id)
- **Yearly Price**: $790.00 (price_pro_yearly_id)

#### Enterprise Plan
- **Product Name**: Enterprise Plan
- **Monthly Price**: $199.00 (price_enterprise_monthly_id)
- **Yearly Price**: $1990.00 (price_enterprise_yearly_id)

### 2. Configure Webhooks
Set up a webhook endpoint in Stripe Dashboard:
- **URL**: `https://yourdomain.com/stripe/webhook`
- **Events to send**:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
  - `customer.created`
  - `customer.updated`

Copy the webhook signing secret to your `.env` file as `STRIPE_WEBHOOK_SECRET`.

## 📊 Features Overview

### For Users
1. **Pricing Page**: View and compare subscription plans on the homepage
2. **Stripe Checkout**: Secure payment processing with Stripe
3. **Subscription Management**: View usage, manage billing, cancel subscriptions
4. **Email Notifications**: Confirmation emails for subscription events
5. **Usage Tracking**: Monitor AI token consumption and costs

### For Admins
1. **Billing Dashboard**: Overview of all users and their subscriptions
2. **Usage Analytics**: Detailed token usage statistics and trends
3. **Revenue Tracking**: Monitor subscription revenue and costs
4. **CSV Export**: Export billing data for accounting
5. **User Management**: View individual user subscription details

## 🔧 System Components

### Models
- **User**: Extended with subscription fields
- **Subscription**: Stores Stripe subscription data
- **OpenAIUsage**: Tracks token usage and costs

### Controllers
- **SubscriptionController**: Handles subscription operations
- **Admin/BillingController**: Admin billing dashboard
- **Admin/AnalyticsController**: Usage analytics

### Services
- **StripeService**: Handles all Stripe API interactions
- **OpenAIClient**: Extended to track token usage

### Views
- **Pricing Section**: Homepage pricing cards with Stripe integration
- **Subscription Management**: User subscription dashboard
- **Admin Billing**: Admin billing overview
- **Usage Analytics**: Admin usage statistics

## 🎯 Usage Tracking

The system automatically tracks:
- **Token Usage**: Every OpenAI API call logs token consumption
- **Cost Calculation**: Estimates costs based on token usage
- **Monthly Limits**: Enforces plan-based token limits
- **Usage Analytics**: Provides detailed usage insights

### Token Limits by Plan
- **Free**: 10,000 tokens/month
- **Basic**: 50,000 tokens/month
- **Professional**: 250,000 tokens/month
- **Enterprise**: Unlimited tokens

## 📧 Email Notifications

The system sends emails for:
- **Subscription Confirmation**: Welcome email with plan details
- **Payment Success**: Confirmation of successful payments
- **Payment Failed**: Notification of failed payments
- **Usage Warnings**: Alerts when approaching token limits

## 🔒 Security Features

- **Webhook Verification**: All Stripe webhooks are verified
- **CSRF Protection**: All forms include CSRF tokens
- **Rate Limiting**: API endpoints are rate limited
- **Input Validation**: All user inputs are validated
- **Secure Storage**: Sensitive data is encrypted

## 🚦 Testing

### Test Mode
- Use Stripe test keys for development
- Test cards: `4242424242424242` (Visa), `4000000000000002` (Declined)
- Webhooks work with ngrok for local testing

### Production Checklist
- [ ] Replace test keys with live Stripe keys
- [ ] Update webhook URL to production domain
- [ ] Configure email settings for notifications
- [ ] Set up proper queue workers
- [ ] Enable SSL/HTTPS
- [ ] Test all subscription flows

## 🛠️ Troubleshooting

### Common Issues

1. **Webhook Signature Verification Failed**
   - Check webhook secret in `.env`
   - Ensure webhook URL is correct
   - Verify SSL certificate

2. **Price ID Not Found**
   - Verify price IDs in Stripe Dashboard
   - Check `.env` configuration
   - Ensure prices are active

3. **Email Not Sending**
   - Configure mail settings in `.env`
   - Check queue worker is running
   - Verify email templates exist

4. **Token Tracking Not Working**
   - Check OpenAI API integration
   - Verify database migrations ran
   - Check OpenAIUsage model relationships

### Logs
Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

## 📈 Monitoring

### Key Metrics to Monitor
- **Subscription Growth**: New subscriptions per month
- **Churn Rate**: Cancelled subscriptions
- **Token Usage**: Average tokens per user
- **Revenue**: Monthly recurring revenue (MRR)
- **API Costs**: OpenAI API expenses

### Admin Dashboard
Access the admin dashboard at `/admin/dashboard` to monitor:
- User subscriptions and status
- Token usage analytics
- Revenue and cost tracking
- System health metrics

## 🔄 Maintenance

### Regular Tasks
- Monitor webhook delivery in Stripe Dashboard
- Review failed payments and follow up
- Analyze usage patterns and adjust limits
- Update pricing based on costs and market
- Backup subscription and usage data

### Updates
- Keep Stripe PHP SDK updated
- Monitor Stripe API changes
- Update webhook event handling as needed
- Review and update email templates

## 📞 Support

For technical support:
- Check Laravel logs first
- Review Stripe Dashboard for payment issues
- Test webhook delivery
- Verify environment configuration

The system is designed to be robust and handle edge cases, but monitoring and maintenance are important for optimal performance.