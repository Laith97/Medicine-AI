# Production Verification Checklist - AI Predictive Analytics

## 🔍 Quick Health Check Commands

### 1. Check if Laravel Scheduler is Running
```bash
# On production server
ps aux | grep "schedule:run"
```
**Expected:** Should see the cron process running

---

### 2. Verify Cron Job is Scheduled
```bash
# Check crontab
crontab -l | grep artisan
```
**Expected:** Should see:
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

### 3. Test Manual Training
```bash
# SSH to production
cd /path/to/project
php artisan models:train
```
**Expected Output:**
```
Starting ML model training...
ML model training completed successfully.
```

---

### 4. Check Feature Extraction (Most Important)
```bash
# View recent logs
tail -100 storage/logs/laravel.log | grep "ML Risk Assessment - Features"
```
**Expected:** Should see 9 features:
```json
{
  "features": [0, 0, 365, 87.1, 20, 1, 0, 0, 0],
  "feature_breakdown": {
    "no_show_count": 0,
    "cancellation_count": 0,
    "last_visit_days": 365,
    "visit_frequency": 87.1,
    "age": 20,
    "gender": 1,
    "chronic_conditions": 0,
    "medication_count": 0,
    "lead_time": 0
  }
}
```

---

### 5. Check Model Files
```bash
ls -lh storage/app/models/
```
**Expected:**
- `no_show_model.rbx` (if 50+ appointments exist)
- `hospitalization_model.rbx` (if 50+ appointments exist)
- Empty directory is OK if < 50 appointments

---

### 6. Verify Predictions Work
```bash
php artisan tinker
```
```php
$appointment = App\Models\Appointment::first();
$service = app(App\Services\PredictiveAnalyticsService::class);
$risks = $service->predictRisks($appointment->patient, $appointment);
print_r($risks);
```
**Expected:**
```php
Array (
    [no_show_risk] => 0.13
    [hospitalization_risk] => 0.05
)
```

---

### 7. Check UI Display
1. Go to: `https://yourdomain.com/doctor/appointments/{id}`
2. Scroll to "AI Predictive Analytics" section
3. **Verify:**
   - ✅ Shows 9 features (not 5)
   - ✅ Risk percentages display
   - ✅ Feature table shows all rows
   - ✅ No errors in browser console

---

### 8. Monitor Cron Execution
```bash
# Check if cron ran today at 2 AM
grep "ML model training" storage/logs/laravel.log | tail -5
```
**Expected:** Should see recent training attempts

---

### 9. Check Training Data Status
```bash
php artisan tinker
```
```php
$service = app(App\Services\PredictiveAnalyticsService::class);
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('checkTrainingDataAdequacy');
$method->setAccessible(true);
$adequacy = $method->invoke($service);
print_r($adequacy);
```
**Expected:**
```php
Array (
    [adequate] => false  // true when 50+ appointments
    [total_appointments] => 0
    [no_show_count] => 0
    [high_risk_count] => 0
    [no_show_rate] => 0
    [high_risk_rate] => 0
)
```

---

## 🚨 Common Issues & Fixes

### Issue 1: Cron Not Running
**Symptom:** No logs at 2 AM
**Fix:**
```bash
# Add to crontab
crontab -e
# Add this line:
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Issue 2: Only 5 Features Showing
**Symptom:** Old feature count in logs
**Fix:**
```bash
# Delete old models
rm storage/app/models/*.rbx
# Clear cache
php artisan cache:clear
php artisan config:clear
# Retrain
php artisan models:train
```

### Issue 3: Permission Errors
**Symptom:** Can't write model files
**Fix:**
```bash
chmod -R 775 storage/app/models
chown -R www-data:www-data storage/app/models
```

### Issue 4: Models Not Loading
**Symptom:** File not found errors
**Fix:**
```bash
# Create directory if missing
mkdir -p storage/app/models
chmod 775 storage/app/models
```

---

## ✅ Production Readiness Checklist

- [ ] Laravel scheduler cron is active
- [ ] `models:train` command runs without errors
- [ ] Logs show 9 features (not 5)
- [ ] UI displays all 9 features correctly
- [ ] Predictions return valid risk scores
- [ ] No errors in production logs
- [ ] Storage directory has write permissions
- [ ] Cron runs daily at 2 AM

---

## 📊 Monitoring Commands (Run Weekly)

### Check System Health
```bash
# 1. Check recent predictions
tail -200 storage/logs/laravel.log | grep "ML Risk Assessment - Final Result"

# 2. Count predictions made
grep "ML Risk Assessment - Final Result" storage/logs/laravel.log | wc -l

# 3. Check for errors
grep "Failed to train models" storage/logs/laravel.log

# 4. Verify feature count
tail -50 storage/logs/laravel.log | grep "feature_breakdown" | tail -1
```

### Performance Check
```bash
# Check model file sizes (should be 50-100KB each)
ls -lh storage/app/models/

# Check training time (should be < 1 minute)
grep "ML model training" storage/logs/laravel.log | tail -10
```

---

## 🎯 Success Indicators

### Working Correctly:
✅ Logs show 9 features consistently
✅ Predictions return reasonable values (0-1 range)
✅ UI displays all features
✅ No PHP errors in logs
✅ Cron runs daily without failures

### Needs Attention:
⚠️ Only 5 features in logs → Clear cache & retrain
⚠️ Predictions always 0 → Check if appointments exist
⚠️ File permission errors → Fix storage permissions
⚠️ Cron not running → Check crontab setup

---

## 📞 Quick Diagnostic Script

Save as `check-ml-health.php` and run with `php check-ml-health.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AI Predictive Analytics Health Check ===\n\n";

// 1. Check appointments
$count = App\Models\Appointment::count();
echo "✓ Total appointments: $count\n";

// 2. Check models exist
$noShowExists = file_exists(storage_path('app/models/no_show_model.rbx'));
$hospExists = file_exists(storage_path('app/models/hospitalization_model.rbx'));
echo "✓ No-show model: " . ($noShowExists ? "EXISTS" : "NOT FOUND") . "\n";
echo "✓ Hospitalization model: " . ($hospExists ? "EXISTS" : "NOT FOUND") . "\n";

// 3. Test prediction
try {
    $appointment = App\Models\Appointment::first();
    if ($appointment && $appointment->patient) {
        $service = app(App\Services\PredictiveAnalyticsService::class);
        $extractor = app(App\Services\FeatureExtractor::class);
        $features = $extractor->extractFeatures($appointment->patient, $appointment);
        echo "✓ Feature count: " . count($features) . " (should be 9)\n";
        
        $risks = $service->predictRisks($appointment->patient, $appointment);
        echo "✓ Prediction works: YES\n";
        echo "  - No-show risk: " . ($risks['no_show_risk'] * 100) . "%\n";
        echo "  - Hospitalization risk: " . ($risks['hospitalization_risk'] * 100) . "%\n";
    } else {
        echo "⚠ No appointments to test\n";
    }
} catch (Exception $e) {
    echo "✗ Prediction failed: " . $e->getMessage() . "\n";
}

echo "\n=== Health Check Complete ===\n";
```

---

## 🚀 One-Line Production Check

```bash
cd /path/to/project && php artisan tinker --execute="echo 'Features: ' . count(app(App\Services\FeatureExtractor::class)->extractFeatures(App\Models\User::first(), App\Models\Appointment::first()));"
```
**Expected:** `Features: 9`

---

**Save this file and use it as your production verification guide!**
