# MedCura AI Reminder System - Complete Solution

## 🔍 Problem Analysis

The reminder emails are not being sent because:

1. **Queue Worker Not Running**: Jobs are queued to database but not processed
2. **No Actual Overdue Invoices**: Currently no invoices meet overdue criteria
3. **127 Jobs Pending**: Large backlog of unprocessed jobs in queue

## ✅ Complete Solution

### 1. Start Queue Worker (IMMEDIATE ACTION REQUIRED)

**Option A: Manual Start (for testing)**
```bash
cd /home/laith/Documents/Medicine
php artisan queue:work --tries=3 --timeout=60 --verbose
```

**Option B: Use the provided script**
```bash
cd /home/laith/Documents/Medicine
./start_queue_worker.sh
```

**Option C: Background process**
```bash
cd /home/laith/Documents/Medicine
nohup php artisan queue:work --daemon --tries=3 --timeout=60 > storage/logs/queue.log 2>&1 &
```

### 2. Process Existing Queue Backlog

```bash
# Clear failed jobs
php artisan queue:flush

# Process all pending jobs
php artisan queue:work --stop-when-empty
```

### 3. Test the Reminder System

1. **Start queue worker** (using any method above)
2. **Go to Admin Panel** → Monthly Invoice Management
3. **Click "Process Overdue"** button
4. **Check logs**: `tail -f storage/logs/laravel.log`

### 4. Production Setup (RECOMMENDED)

**Install Supervisor for automatic queue management:**

```bash
# Install supervisor
sudo apt update
sudo apt install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/medcura-queue.conf
```

**Supervisor Configuration:**
```ini
[program:medcura-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/laith/Documents/Medicine/artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-jobs=1000
directory=/home/laith/Documents/Medicine
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home/laith/Documents/Medicine/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Start supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start medcura-queue:*
```

## 📧 How the Reminder System Works

### Process Flow:
1. **Admin clicks "Process Overdue"** → Job queued
2. **Queue worker processes job** → Finds overdue invoices
3. **For each overdue invoice:**
   - Restricts user access (if not already restricted)
   - Sends reminder notification (if needed)
   - Updates reminder count and timestamp

### Reminder Criteria:
- Invoice status = 'open'
- Invoice type = 'monthly'
- Past grace period (`grace_period_ends_at` < now)
- No reminder sent OR last reminder was sent > `reminder_frequency_days` ago

### Notification Channels:
- **Email**: Always sent (SMTP configured)
- **SMS**: If phone number available and Twilio configured
- **Database**: For in-app notifications

## 🎯 Testing Reminders

### Create Test Overdue Invoice:
```php
// Run in php artisan tinker
$user = User::first();
$invoice = StripeInvoice::create([
    'user_id' => $user->id,
    'stripe_invoice_id' => 'test_' . time(),
    'amount_due' => 10000, // $100.00
    'amount_paid' => 0,
    'currency' => 'usd',
    'status' => 'open',
    'due_date' => now()->subDays(5),
    'grace_period_ends_at' => now()->subDays(2), // IMPORTANT!
    'description' => 'Test Monthly Subscription',
    'invoice_type' => 'monthly',
    'period_start' => now()->subMonth(),
    'period_end' => now(),
    'reminder_count' => 0,
    'last_reminder_sent_at' => null,
]);
```

### Test Email Manually:
```php
// Run in php artisan tinker
use Illuminate\Support\Facades\Mail;
Mail::raw('Test reminder email', function($m) {
    $m->to('your-email@example.com')
      ->subject('Test Reminder')
      ->from('info@medcuraai.com', 'MedCura AI');
});
```

## 🚀 Quick Start Commands

```bash
# 1. Navigate to project
cd /home/laith/Documents/Medicine

# 2. Clear queue backlog
php artisan queue:flush

# 3. Start queue worker
./start_queue_worker.sh

# 4. In another terminal, test the system
# Go to admin panel and click "Process Overdue"

# 5. Monitor logs
tail -f storage/logs/laravel.log
```

## 📊 Monitoring Commands

```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all jobs
php artisan queue:flush

# Process queue once
php artisan queue:work --once
```

## 🔧 Troubleshooting

### If emails still don't send:

1. **Check SMTP settings in .env**
2. **Test email configuration:**
   ```bash
   php artisan tinker
   Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
   ```
3. **Check Laravel logs:** `storage/logs/laravel.log`
4. **Verify queue worker is running:** `ps aux | grep queue:work`

### If no overdue invoices found:

1. **Check invoice criteria in database**
2. **Verify `grace_period_ends_at` is set and past**
3. **Check `invoice_type` = 'monthly'**
4. **Verify `status` = 'open'**

## 🎉 Success Indicators

✅ **Queue worker running**: `ps aux | grep queue:work` shows process  
✅ **Jobs being processed**: Queue count decreasing  
✅ **Emails being sent**: Check logs for "Mail sent" messages  
✅ **Reminders working**: Users receive overdue notifications  

## 📞 Support

If issues persist:
1. Check `storage/logs/laravel.log` for errors
2. Verify SMTP credentials are correct
3. Ensure queue worker has proper permissions
4. Test email sending manually first

---

**Remember**: The queue worker MUST be running for any background jobs (including email sending) to work!