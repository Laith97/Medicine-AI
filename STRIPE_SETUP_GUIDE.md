# Stripe Setup Guide

This guide will help you configure Stripe payments for the MedCura AI application.

## Prerequisites

1. A Stripe account (sign up at https://stripe.com)
2. Access to your Stripe Dashboard

## Step 1: Get Your Stripe API Keys

1. Log in to your Stripe Dashboard
2. Go to **Developers** > **API keys**
3. Copy your **Publishable key** (starts with `pk_test_` for test mode)
4. Copy your **Secret key** (starts with `sk_test_` for test mode)

## Step 2: Create Products and Prices

### Create Products
1. Go to **Products** in your Stripe Dashboard
2. Create three products:
   - **Basic Plan** - $29/month, $290/year
   - **Professional Plan** - $79/month, $790/year  
   - **Enterprise Plan** - $199/month, $1990/year

### Create Prices for Each Product
For each product, create two prices:
1. **Monthly recurring price**
2. **Yearly recurring price**

After creating each price, copy the Price ID (starts with `price_`).

## Step 3: Set Up Webhooks (Optional but Recommended)

1. Go to **Developers** > **Webhooks**
2. Click **Add endpoint**
3. Set the endpoint URL to: `https://yourdomain.com/stripe/webhook`
4. Select these events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copy the **Signing secret** (starts with `whsec_`)

## Step 4: Update Environment Variables

Update your `.env` file with the following values:

```env
# Stripe Configuration
STRIPE_KEY=pk_test_your_actual_publishable_key_here
STRIPE_SECRET=sk_test_your_actual_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_actual_webhook_secret_here
STRIPE_ENVIRONMENT=test

# Stripe Price IDs (replace with your actual price IDs)
STRIPE_BASIC_MONTHLY_PRICE_ID=price_your_basic_monthly_price_id
STRIPE_BASIC_YEARLY_PRICE_ID=price_your_basic_yearly_price_id
STRIPE_PRO_MONTHLY_PRICE_ID=price_your_pro_monthly_price_id
STRIPE_PRO_YEARLY_PRICE_ID=price_your_pro_yearly_price_id
STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_your_enterprise_monthly_price_id
STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_your_enterprise_yearly_price_id
```

## Step 5: Clear Configuration Cache

After updating your `.env` file, run:

```bash
php artisan config:clear
```

## Step 6: Test the Integration

1. Try creating a subscription from the pricing page
2. Use Stripe's test card numbers:
   - **Success**: `4242424242424242`
   - **Decline**: `4000000000000002`
   - Use any future expiry date and any 3-digit CVC

## Production Setup

When ready for production:

1. Switch to live mode in your Stripe Dashboard
2. Get your live API keys (start with `pk_live_` and `sk_live_`)
3. Update your `.env` file with live keys
4. Set `STRIPE_ENVIRONMENT=live`
5. Update webhook endpoint to your production URL

## Troubleshooting

### Common Issues

1. **"No API key provided" error**
   - Check that `STRIPE_SECRET` is set in your `.env` file
   - Run `php artisan config:clear` after updating

2. **"Invalid plan" error**
   - Verify the plan name matches the config in `config/stripe.php`
   - Ensure the price IDs are correctly set

3. **"Price ID not configured" error**
   - Check that all price IDs are set in your `.env` file
   - Verify the price IDs exist in your Stripe Dashboard

4. **Webhook signature verification failed**
   - Ensure `STRIPE_WEBHOOK_SECRET` is correctly set
   - Check that the webhook endpoint URL is correct

### Testing Checklist

- [ ] API keys are correctly set
- [ ] All price IDs are configured
- [ ] Webhook endpoint is set up (if using webhooks)
- [ ] Test subscription creation works
- [ ] Test subscription cancellation works
- [ ] Email notifications are sent (if configured)

## Support

If you encounter issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check Stripe Dashboard logs for webhook events
3. Verify all environment variables are correctly set