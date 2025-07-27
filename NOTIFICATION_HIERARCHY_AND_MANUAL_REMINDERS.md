# Notification Hierarchy & Manual Reminders System

## Dashboard Notification Hierarchy

The dashboard shows different notifications based on user status in the following priority order:

### 1. **Account Restricted** (Highest Priority)
- **When**: User account is restricted due to unpaid invoices
- **Color**: Red (Danger)
- **Message**: "Account Access Restricted"
- **Icon**: Ban icon
- **Actions**: Pay Outstanding Invoices, View Details

### 2. **Grace Period** 
- **When**: Subscription expired but user is still in grace period
- **Color**: Yellow (Warning)  
- **Message**: "Subscription Expired - Grace Period"
- **Icon**: Clock icon
- **Actions**: Renew Subscription, View Invoices
- **Details**: Shows days remaining in grace period

### 3. **Warning Period**
- **When**: User is in final warning period before restriction
- **Color**: Red (Danger)
- **Message**: "Final Warning - Account Will Be Restricted Soon"
- **Icon**: Exclamation triangle
- **Actions**: Renew Now, Pay Invoices
- **Details**: Shows days remaining before restriction

### 4. **Overdue Invoices**
- **When**: User has overdue invoices (but not in grace/warning period)
- **Color**: Yellow (Warning)
- **Message**: "Overdue Invoices"
- **Icon**: Exclamation triangle
- **Actions**: View Invoices
- **Details**: Shows number of overdue invoices

### 5. **Monthly Service Fee Due** (Lowest Priority)
- **When**: User has unpaid monthly service fees (general case)
- **Color**: Blue (Info)
- **Message**: "Monthly Service Fee Due"
- **Icon**: Calendar icon
- **Actions**: Pay Now
- **Details**: Shows total unpaid amount ($84.00 in your example)

## When "Monthly Service Fee Due" Appears

The "Monthly Service Fee Due" notification appears when:
- ✅ User is **NOT** restricted
- ✅ User is **NOT** in grace period  
- ✅ User is **NOT** in warning period
- ✅ User does **NOT** have overdue invoices
- ✅ User **HAS** unpaid monthly amounts (`getTotalUnpaidMonthlyAmount() > 0`)

This is the **general/fallback** notification for users who have unpaid monthly fees but are not in any critical state.

## Email & SMS Reminder Types

### 1. **Grace Period Reminders**
- **Notification Class**: `GracePeriodReminder`
- **Email Subject**: "Subscription Expired - Grace Period Active"
- **SMS Content**: "🔔 MedCura AI: Your subscription expired but you're in grace period. X days remaining. Renew now: [URL]"
- **When Sent**: During grace period, respecting reminder frequency

### 2. **Warning Period Reminders**  
- **Notification Class**: `FinalWarning`
- **Email Subject**: "FINAL WARNING - Account Will Be Restricted Soon"
- **SMS Content**: "🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in X days. Renew immediately: [URL]"
- **When Sent**: During warning period, respecting reminder frequency

### 3. **Overdue Invoice Reminders**
- **Notification Class**: `InvoiceOverdue`
- **Email Subject**: "URGENT: Invoice Overdue - MedCura AI"
- **SMS Content**: "URGENT: Invoice $X.XX is X days overdue. Account restricted. Pay now: [URL]"
- **When Sent**: For overdue invoices past grace period, respecting reminder frequency

## Manual Reminder System (Admin Feature)

### Access
- **URL**: `/admin/send-reminders`
- **Location**: Admin Dashboard → Quick Actions → "Send Manual Reminders"
- **Permission**: Admin users only

### Features

#### 1. **Reminder Type Selection**
- **Grace Period Reminders**: Users in grace period
- **Warning Period Reminders**: Users in warning period  
- **Overdue Invoice Reminders**: Users with overdue invoices
- **All Reminder Types**: Send all applicable reminders

#### 2. **User Selection**
- **Automatic Detection**: System automatically identifies eligible users
- **Individual Selection**: Choose specific users to send reminders to
- **Bulk Selection**: Toggle all users in a category
- **User Information**: Shows name, email, phone, status, and days remaining

#### 3. **Smart Filtering**
- **Grace Period Users**: Shows users currently in grace period with days remaining
- **Warning Period Users**: Shows users in final warning with days remaining  
- **Overdue Users**: Shows users with overdue invoices and invoice count
- **Phone Number Detection**: Indicates which users will receive SMS

#### 4. **Force Send Option**
- **Normal Mode**: Respects reminder frequency settings (prevents spam)
- **Force Send**: Ignores frequency limits and sends immediately
- **Use Case**: Emergency notifications or testing

#### 5. **Multi-Channel Delivery**
- **Email**: Always sent to all selected users
- **SMS**: Sent to users with phone numbers (when Twilio configured)
- **Queue Processing**: Uses background jobs for scalability

### Usage Examples

#### Send Grace Period Reminders
1. Select "Grace Period Reminders"
2. Choose specific users or leave all selected
3. Click "Send Reminders"
4. System sends to users respecting frequency limits

#### Emergency Notification
1. Select "All Reminder Types"  
2. Check "Force Send" option
3. Click "Send Reminders"
4. System sends immediately to all eligible users

#### Test SMS Configuration
1. Select "Grace Period Reminders"
2. Choose a user with phone number
3. Check "Force Send"
4. Send reminder to test SMS delivery

### Technical Implementation

#### Controller Methods
- `showSendRemindersForm()`: Display the reminder form
- `sendManualReminders()`: Process reminder sending
- `sendGracePeriodReminders()`: Send grace period reminders
- `sendWarningPeriodReminders()`: Send warning period reminders  
- `sendOverdueReminders()`: Send overdue invoice reminders

#### Validation & Safety
- **User Validation**: Ensures selected users exist
- **Type Validation**: Validates reminder type selection
- **Frequency Respect**: Honors reminder frequency unless forced
- **Error Handling**: Captures and reports individual failures
- **Confirmation Dialog**: JavaScript confirmation before sending

#### Results Reporting
- **Success Count**: Number of reminders sent by type
- **Error Reporting**: Details of any failures
- **User Feedback**: Clear success/error messages

## Configuration Status

### ✅ Email Reminders
- **Status**: Production Ready
- **SMTP**: Configured (info@medcuraai.com)
- **Templates**: Professional HTML emails
- **Delivery**: Immediate via queue system

### ⚠️ SMS Reminders  
- **Status**: Code Ready (Needs Twilio Setup)
- **Implementation**: Fully coded and tested
- **Missing**: Twilio credentials in .env
- **Setup Required**:
  ```env
  TWILIO_SID=your_account_sid
  TWILIO_TOKEN=your_auth_token  
  TWILIO_FROM=+1234567890
  ```

## Summary

### Dashboard Notifications
- **5 notification types** in priority order
- **"Monthly Service Fee Due"** is the general/fallback notification
- **Appears when** user has unpaid fees but no critical status

### Manual Reminder System
- **Admin-controlled** manual reminder sending
- **3 reminder types** (Grace, Warning, Overdue) plus "All"
- **Smart user selection** with status detection
- **Force send option** for emergency use
- **Multi-channel delivery** (Email + SMS)
- **Frequency respect** to prevent spam
- **Production ready** for email, SMS ready with Twilio setup

The system provides comprehensive notification management with both automatic scheduled reminders and manual admin control for immediate notifications when needed.