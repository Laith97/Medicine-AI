Hello {{ $userName }},

We wanted to let you know that your MedCura AI subscription payment is due. No worries - you have a grace period to complete your payment.

Account Details:
- Email: {{ $userEmail }}
- Amount Due: ${{ number_format($billingAmount, 2) }}
- Grace Period: {{ $gracePeriodDays }} days

@if($subscriptionEndsAt)
- Payment Was Due: {{ $subscriptionEndsAt->format('M d, Y') }}
- Days Remaining in Grace Period: {{ max(0, $gracePeriodDays - $subscriptionEndsAt->diffInDays(now())) }} days
@endif

To continue enjoying uninterrupted access to MedCura AI, please complete your payment at your convenience.

You can view and pay your invoice here: {{ url('/invoices') }}

If you have any questions or need assistance, our support team is here to help at info@medcuraai.com.

Thank you for using MedCura AI.

Best regards,
The MedCura AI Team

---
This message was sent from MedCura AI regarding your account.
For support, please contact us at info@medcuraai.com
© {{ date('Y') }} MedCura AI. All rights reserved.