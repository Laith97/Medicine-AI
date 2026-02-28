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

## 💻 Usage

### For Doctors

#### Starting a Video Call
1. Navigate to appointment details page
2. For video appointments, click "Start Video Call" button
3. Video room opens in new window with full controls

#### Making a Phone Call
1. Navigate to appointment details page
2. For phone appointments, click "Show Patient Phone" button
3. Patient's name and phone number are displayed
4. Call patient from your own phone

### For Patients

#### Joining a Video Call
1. Go to appointment details page
2. Click "Join Video Call" button
3. Video room opens automatically

#### Receiving a Phone Call
1. Ensure phone number is registered in profile
2. Wait for doctor to call at appointment time

## 🔧 API Endpoints

### Get Patient Phone (Doctor Only)
```
GET /api/appointments/{appointment}/patient-phone
```

### Generate Video Token
```
POST /api/appointments/{appointment}/video/token
```

### End Video Call
```
POST /api/appointments/{appointment}/video/end
```

## 💰 Pricing

### Daily.co
- **Free Tier**: 10,000 minutes/month
- **Pro Plan**: $0.12/participant/hour
- **Enterprise**: Custom pricing with HIPAA compliance

### Phone Calls
- **FREE**: Manual calling from doctor's own phone

### Cost Comparison vs Twilio
- **Video**: 50% cheaper ($0.12 vs $0.24/hour)
- **Phone**: 100% savings (FREE vs $0.51/hour)

## 🔒 Security

### Video Calls
- Token-based authentication
- Unique room names per appointment
- Automatic room deletion after call
- Only authorized users can join

### Phone Numbers
- Only doctors can view patient phone
- Displayed only for confirmed appointments
- Requires authentication

## 🐛 Troubleshooting

### Video Call Issues
- Check DAILY_API_KEY in .env
- Verify API key is active
- Check browser console for errors

### Phone Display Issues
- Verify patient has phone number
- Check appointment is confirmed
- Ensure logged in as doctor

## 📚 Documentation

See [DAILY_SETUP.md](DAILY_SETUP.md) for detailed setup instructions.

---

**Built with ❤️ for MedcuraAI - Simple, Practical, Cost-Effective**
