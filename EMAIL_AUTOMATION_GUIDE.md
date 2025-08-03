# 📧 Email Automation System - Complete Guide

## 🎯 **When Are Emails Sent Automatically?**

### **Current Status: ✅ AUTOMATIC EMAILS ACTIVE**
The system is now fully configured with cron jobs running every 6 hours. This means:
- ✅ Automatic grace period reminders (every `reminder_frequency_days` set by admin)
- ✅ Automatic warning period reminders (daily)
- ✅ Automatic account restrictions (when periods end)
- ✅ Manual reminders also work (via admin panel)

---

## 🔄 **How Automated Emails SHOULD Work**

### **1. Grace Period Reminders**
**When:** When a user's subscription expires and they enter the grace period
**Frequency:** Every `reminder_frequency_days` (default: 3 days)
**Template:** Uses Laravel Notification system (`GracePeriodReminder`)

**Logic:**
```php
// User subscription expires (subscription_ends_at < now())
// User enters grace period (grace_period_days after expiration)
// Email sent every reminder_frequency_days during grace period
```

### **2. Warning Period Reminders**  
**When:** After grace period ends, during warning period
**Frequency:** **DAILY** (every 24 hours)
**Template:** Uses Laravel Notification system (`FinalWarning`)

**Logic:**
```php
// Grace period ends (subscription_ends_at + grace_period_days < now())
// User enters warning period (warning_period_days after grace period)
// Email sent DAILY during warning period
```

### **3. Account Restriction**
**When:** After warning period ends
**Frequency:** Once (when restriction is applied)
**Template:** Uses Laravel Notification system (`AccountRestricted`)

**Logic:**
```php
// Warning period ends (subscription_ends_at + grace_period_days + warning_period_days < now())
// Account gets restricted (is_restricted = true)
// Access to specified pages blocked
```

---

## ⚙️ **System Components**

### **1. Automated Processing Job**
- **File:** `app/Jobs/ProcessSubscriptionLifecycle.php`
- **Service:** `app/Services/SubscriptionLifecycleService.php`
- **Command:** `php artisan subscriptions:process-lifecycle`

### **2. Notification Classes**
- **Grace Period:** `app/Notifications/GracePeriodReminder.php`
- **Warning Period:** `app/Notifications/FinalWarning.php`
- **Restriction:** `app/Notifications/AccountRestricted.php`

### **3. Manual System (Working)**
- **Controller:** `app/Http/Controllers/AdminController.php`
- **Templates:** `resources/views/emails/reminders/`
- **Service:** `app/Services/EmailService.php`

---

## 🚀 **Setting Up Automated Emails**

### **Step 1: Configure Cron Job**
Add this to your server's crontab:
```bash
# Edit crontab
crontab -e

# Add this line (runs every hour)
0 * * * * cd /home/laith/Documents/Medicine && php artisan subscriptions:process-lifecycle >> /dev/null 2>&1

# Or run every 6 hours
0 */6 * * * cd /home/laith/Documents/Medicine && php artisan subscriptions:process-lifecycle >> /dev/null 2>&1
```

### **Step 2: Test the Automated System**
```bash
# Test the lifecycle processing manually
php artisan subscriptions:process-lifecycle

# Check logs for results
tail -f storage/logs/laravel.log
```

### **Step 3: Monitor the System**
```bash
# Check email health
php artisan email:health-check

# Monitor email system
php artisan email:monitor
```

---

## 🧪 **Testing Email Automation**

### **Method 1: Create Test User with Expired Subscription**

```bash
php artisan tinker
```

```php
// Create test user
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

// Create expired subscription setting
$setting = \App\Models\MonthlyInvoiceSetting::create([
    'user_id' => $user->id,
    'billing_amount' => 99.99,
    'monthly_price' => 99.99,
    'yearly_price' => 999.99,
    'subscription_period_months' => 1,
    'subscription_starts_at' => now()->subDays(35), // Started 35 days ago
    'subscription_ends_at' => now()->subDays(5),    // Expired 5 days ago
    'grace_period_days' => 7,                       // 7-day grace period
    'warning_period_days' => 3,                     // 3-day warning period
    'reminder_frequency_days' => 3,                 // Remind every 3 days
    'is_restricted' => false,
    'is_active' => true,
]);

// Check status
echo $setting->getSubscriptionStatus(); // Should be 'grace_period'

// Test the lifecycle processor
php artisan subscriptions:process-lifecycle
```

### **Method 2: Modify Existing User for Testing**

```bash
php artisan tinker
```

```php
// Get existing user
$user = \App\Models\User::find(65); // Replace with actual user ID
$setting = $user->monthlyInvoiceSetting;

// Temporarily modify dates for testing
$setting->update([
    'subscription_starts_at' => now()->subDays(35),
    'subscription_ends_at' => now()->subDays(5),    // Expired 5 days ago
    'grace_period_days' => 7,
    'warning_period_days' => 3,
    'reminder_frequency_days' => 3,
    'last_reminder_sent_at' => null, // Reset to trigger reminder
    'is_restricted' => false,
    'is_active' => true,
]);

// Check status
echo $setting->getSubscriptionStatus(); // Should be 'grace_period'

// Run processor
exit();
php artisan subscriptions:process-lifecycle
```

### **Method 3: Test Different Periods**

```php
// Test Grace Period (subscription expired, within grace period)
$setting->update([
    'subscription_ends_at' => now()->subDays(3),    // Expired 3 days ago
    'grace_period_days' => 7,                       // Still in grace (4 days left)
    'last_reminder_sent_at' => null,
]);

// Test Warning Period (grace period ended, within warning period)
$setting->update([
    'subscription_ends_at' => now()->subDays(10),   // Expired 10 days ago
    'grace_period_days' => 7,                       // Grace ended 3 days ago
    'warning_period_days' => 5,                     // Still in warning (2 days left)
    'last_reminder_sent_at' => null,
]);

// Test Should Be Restricted (all periods ended)
$setting->update([
    'subscription_ends_at' => now()->subDays(15),   // Expired 15 days ago
    'grace_period_days' => 7,                       // Grace ended 8 days ago
    'warning_period_days' => 3,                     // Warning ended 5 days ago
    'is_restricted' => false,                       // Not yet restricted
]);
```

---

## 📊 **Email Timing Examples**

### **Example Timeline:**
- **Day 0:** Subscription expires (`subscription_ends_at`)
- **Day 1-7:** Grace period (user still has access)
  - **Day 1:** First grace period reminder sent
  - **Day 4:** Second grace period reminder sent (every 3 days)
  - **Day 7:** Third grace period reminder sent
- **Day 8-10:** Warning period (limited access)
  - **Day 8:** First warning email (daily)
  - **Day 9:** Second warning email (daily)
  - **Day 10:** Final warning email (daily)
- **Day 11:** Account restricted (no access to restricted pages)

### **Configuration Values:**
```php
'subscription_ends_at' => '2024-01-01',     // Subscription expires
'grace_period_days' => 7,                   // 7 days grace period
'warning_period_days' => 3,                 // 3 days warning period
'reminder_frequency_days' => 3,             // Grace reminders every 3 days
// Warning reminders are ALWAYS daily (hardcoded)
```

---

## 🔧 **Current System Status**

### ✅ **Working Components:**
- Manual reminder system (admin panel)
- Email templates and delivery
- User status calculation
- Lifecycle processing logic

### ❌ **Missing Components:**
- **Cron job configuration** (main issue)
- Automated scheduling
- Regular processing execution

### 🎯 **To Enable Automation:**
1. **Set up cron job** (most important)
2. **Test with sample data**
3. **Monitor logs for issues**
4. **Adjust timing as needed**

---

## 🚨 **Important Notes**

1. **No emails are sent automatically** until cron job is configured
2. **Manual reminders work perfectly** via admin panel
3. **All email templates are functional** and tested
4. **System calculates periods correctly** based on subscription dates
5. **Notifications use Laravel's built-in system** (different from manual templates)

---

## 📞 **Support Commands**

```bash
# Test email system health
php artisan email:health-check

# Test manual reminders
php artisan test:manual-reminder 65 grace_period

# Process lifecycle manually
php artisan subscriptions:process-lifecycle

# Monitor email system
php artisan email:monitor

# Check logs
tail -f storage/logs/laravel.log
```

---

**🎉 Once the cron job is configured, the system will automatically send emails based on subscription status and timing rules!**