# Video Call & Phone Consultation System for MedcuraAI

A simple and practical video call and phone consultation system built for the MedcuraAI Laravel-based web application. This system uses Daily.co for video calls and manual phone calling for voice appointments.

## 🚀 Features

### Core Functionality
- **Video Calls**: HD video consultations using Daily.co
- **Manual Phone Calls**: Doctors see patient phone and call from their own device
- **Simple Integration**: No complex setup or automated calling systems
- **Cost-Effective**: Free tier available, cheaper than alternatives
- **HIPAA Compliant**: Available with Daily.co paid plans

### Video Call Features
- HD video and audio quality
- Built-in screen sharing
- Mute/unmute controls
- Camera on/off toggle
- Leave call button
- Mobile responsive
- No downloads required
- Works in any modern browser

### Phone Call Features
- Display patient phone number to doctor
- Click-to-call functionality
- Manual calling from doctor's own phone
- Simple and personal approach
- No automated systems or costs

## 🛠️ Installation

### 1. Daily.co Setup

1. Sign up at https://dashboard.daily.co/
2. Go to Developers → API Keys
3. Copy your API key

### 2. Environment Configuration

Add to your `.env` file:

```env
DAILY_API_KEY=your_daily_api_key_here
DAILY_DOMAIN=your-domain.daily.co
```

### 3. Test the Setup

```bash
php artisan tinker
>>> $service = new App\Services\DailyService();
>>> $room = $service->createRoom('test-room-123');
>>> print_r($room);
```

## 📁 File Structure

```
app/
├── Services/
│   └── DailyService.php          # Daily.co API integration
├── Http/Controllers/
│   └── VideoCallController.php   # Video & phone call controller

config/
└── daily.php                      # Daily.co configuration

resources/views/
├── video/
│   └── room.blade.php            # Video call interface
└── components/
    └── appointment-call-buttons.blade.php  # Call/video buttons

routes/
├── api.php                        # API routes
└── web.php                        # Web routes
```

## 🔧 Usage

### Sending Notifications

#### 1. Appointment Booked Notification

```php
use App\Notifications\AppointmentBookedNotification;

$appointment = Appointment::find(1);
$doctor->notify(new AppointmentBookedNotification($appointment));
```

#### 2. Diagnosis Submitted Notification

```php
use App\Notifications\DiagnosisSubmittedNotification;

$diagnosis = Diagnosis::find(1);
$patient->notify(new DiagnosisSubmittedNotification($diagnosis));
```

#### 3. Review Submitted Notification

```php
use App\Notifications\ReviewSubmittedNotification;

$review = Review::find(1);
$doctor->notify(new ReviewSubmittedNotification($review));
```

#### 4. Voice Transcription Completed

```php
use App\Notifications\VoiceTranscriptionCompletedNotification;

$doctor->notify(new VoiceTranscriptionCompletedNotification([
    'patient_name' => 'John Doe',
    'transcription_id' => '12345',
    'summary' => 'Patient discussed symptoms...',
]));
```

#### 5. System Alert Notification

```php
use App\Notifications\SystemAlertNotification;

$users->notify(new SystemAlertNotification([
    'title' => 'System Maintenance',
    'message' => 'Scheduled maintenance tonight...',
    'type' => 'warning',
]));
```

### Controller Methods

#### NotificationController

```php
// Get notifications list
$notifications = auth()->user()->notifications()->latest()->paginate(10);

// Get notification dropdown
$notifications = auth()->user()->notifications()->latest()->take(5)->get();

// Mark notification as read
auth()->user()->notifications()->findOrFail($id)->markAsRead();

// Mark all notifications as read
auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

// Delete notification
auth()->user()->notifications()->findOrFail($id)->delete();

// Get unread count
$unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();

// Update notification settings
auth()->user()->notificationPreferences()->update($request->validated());
```

### JavaScript Usage

#### Accessing Notification Manager

```javascript
// Get notification count
window.notificationManager.unreadCount;

// Mark notification as read
window.notifications.markAsRead(notificationId);

// Mark all as read
window.notifications.markAllAsRead();

// Delete notification
window.notifications.deleteNotification(notificationId);
```

#### Real-time Events

```javascript
// Listen for new notifications
Echo.private(`App.User.${userId}`)
    .notification((notification) => {
        console.log('New notification:', notification);
    });

// Listen for notification read events
Echo.channel('notification-updates')
    .listen('NotificationRead', (event) => {
        console.log('Notification read:', event.notificationId);
    });
```

## 🎨 UI Components

### Notification Bell

```html
<div class="notification-bell" onclick="toggleNotificationDropdown()">
    <i class="fas fa-bell"></i>
    <span class="notification-count" id="notification-count">3</span>
</div>

<div class="notification-dropdown" id="notification-dropdown">
    <!-- Dropdown content -->
</div>
```

### Notification Item

```html
<div class="notification-item unread" data-id="{{ $notification->id }}">
    <div class="notification-icon">
        <i class="fas fa-calendar-check text-primary"></i>
    </div>
    <div class="notification-content">
        <div class="notification-title">{{ $notification->title }}</div>
        <div class="notification-message">{{ $notification->message }}</div>
        <div class="notification-time">{{ $notification->created_at->diffForHumans() }}</div>
    </div>
    <div class="notification-actions">
        <button class="btn btn-sm btn-link mark-read-btn" data-id="{{ $notification->id }}">
            <i class="fas fa-check"></i>
        </button>
        <button class="btn btn-sm btn-link delete-notification-btn" data-id="{{ $notification->id }}">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>
```

### Notification Settings

```html
<div class="notification-settings">
    <div class="setting-card">
        <h3>Notification Preferences</h3>
        
        <div class="setting-item">
            <div class="setting-info">
                <h4>Appointment Notifications</h4>
                <p>Receive notifications about appointments</p>
            </div>
            <div class="setting-controls">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="email-appointment" checked>
                    <label class="form-check-label" for="email-appointment">Email</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sms-appointment">
                    <label class="form-check-label" for="sms-appointment">SMS</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="app-appointment" checked>
                    <label class="form-check-label" for="app-appointment">In-App</label>
                </div>
            </div>
        </div>
        
        <!-- More settings -->
    </div>
</div>
```

## 🧪 Testing

### Running Tests

```bash
# Run all notification tests
php artisan test tests/Feature/NotificationTest.php

# Run with coverage
php artisan test --coverage tests/Feature/NotificationTest.php
```

### Test Coverage

The test suite covers:
- Notification creation and delivery
- Read/unread status management
- Notification deletion
- Pagination functionality
- Authentication requirements
- Settings updates
- Real-time event handling

### Test Seeder

Use the `NotificationTestSeeder` to create test data:

```bash
php artisan db:seed --class=NotificationTestSeeder
```

This creates:
- Test doctor and patient users
- Sample appointments, diagnoses, and reviews
- Various notification types
- Notification preferences
- Test data for pagination

## 🔧 Configuration

### Environment Variables

```env
# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

# Notification Settings
NOTIFICATION_SOUND_ENABLED=true
NOTIFICATION_BADGE_ENABLED=true
NOTIFICATION_TOAST_ENABLED=true
DEFAULT_NOTIFICATION_PAGINATION=10
```

### Customization

#### Adding New Notification Types

1. Create a new notification class:
```php
php artisan make:notification CustomNotification
```

2. Add to `NotificationTypeSeeder`:
```php
DB::table('notification_types')->insert([
    'type' => 'custom',
    'name' => 'Custom Notification',
    'description' => 'Description of custom notification',
    'icon' => 'fas fa-custom',
    'color' => '#007bff',
]);
```

3. Update routes and views as needed.

#### Customizing UI

- Modify `resources/views/notifications/_styles.blade.php` for custom styles
- Update `resources/js/notifications.js` for custom behavior
- Adjust Blade templates in `resources/views/notifications/`

## 🚀 Deployment

### Production Considerations

1. **WebSocket Setup**: Configure Pusher or similar service for production
2. **Database Optimization**: Add indexes for notification queries
3. **Caching**: Implement caching for notification preferences
4. **Monitoring**: Set up monitoring for notification delivery
5. **Performance**: Optimize database queries for large notification volumes

### Performance Tips

- Use database indexing on `notifications` table
- Implement pagination for large notification lists
- Use lazy loading for notification dropdowns
- Cache user notification preferences
- Optimize real-time connections

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Check the documentation
- Review the test cases
- Create an issue in the repository
- Contact the development team

---

**Built with ❤️ for MedcuraAI**
