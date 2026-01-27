# Daily.co Video Call Setup Guide

## Overview
This system uses Daily.co for video consultations and manual phone calls for voice appointments.

## Features
- **Video Calls**: Full-featured video consultations using Daily.co
- **Phone Calls**: Manual calling - doctors see patient phone number and call from their own device
- Simple, cost-effective, and practical for medical practices

## Installation

### 1. Install Daily.co (No Package Required)
Daily.co works via their REST API and JavaScript SDK - no Composer package needed!

### 2. Get Daily.co API Key

1. Sign up at https://dashboard.daily.co/
2. Go to Developers → API Keys
3. Copy your API key

### 3. Configure Environment

Add to your `.env` file:

```env
DAILY_API_KEY=your_daily_api_key_here
DAILY_DOMAIN=your-domain.daily.co
```

### 4. Test the Setup

```bash
# Test video call creation
php artisan tinker
>>> $service = new App\Services\DailyService();
>>> $room = $service->createRoom('test-room-123');
>>> print_r($room);
```

## Usage

### For Doctors

#### Video Appointments
1. Go to appointment details page
2. Click "Start Video Call" button
3. Video room opens in new window with full controls

#### Phone Appointments
1. Go to appointment details page
2. Click "Show Patient Phone" button
3. Patient's phone number is displayed
4. Call patient from your own phone

### For Patients

#### Video Appointments
1. Go to appointment details page
2. Click "Join Video Call" button
3. Video room opens automatically

#### Phone Appointments
- Wait for doctor to call your registered phone number

## API Endpoints

### Get Patient Phone (Doctor Only)
```
GET /api/appointments/{appointment}/patient-phone
```

Response:
```json
{
  "success": true,
  "phone": "+1234567890",
  "patient_name": "John Doe"
}
```

### Generate Video Token
```
POST /api/appointments/{appointment}/video/token
```

Response:
```json
{
  "token": "eyJhbGc...",
  "roomName": "appointment_123",
  "userName": "Dr. Smith",
  "roomUrl": "https://your-domain.daily.co/appointment_123"
}
```

### End Video Call
```
POST /api/appointments/{appointment}/video/end
```

## Daily.co Features

### Included in Video Calls
- HD video and audio
- Screen sharing
- Built-in controls (mute, camera, leave)
- Mobile responsive
- No downloads required
- HIPAA compliant (with paid plan)

### Room Settings
- Max 2 participants (doctor + patient)
- 60-minute default duration
- Auto-cleanup after call ends
- Secure token-based access

## Pricing

### Daily.co
- **Free Tier**: 10,000 minutes/month
- **Pro Plan**: $0.12/participant/hour
- **Enterprise**: Custom pricing with HIPAA compliance

### Cost Comparison
- **Video**: $0.12/hour (Daily.co) vs $0.24/hour (Twilio)
- **Phone**: FREE (manual calling) vs $0.51/hour (Twilio automated)

## File Structure

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

## Security

### Video Calls
- Token-based authentication
- Room names include appointment ID
- Automatic room deletion after call
- Only authorized users can join

### Phone Numbers
- Only doctors can view patient phone numbers
- Phone numbers displayed only for confirmed appointments
- Requires authentication

## Troubleshooting

### Video Call Issues

**Problem**: "Failed to connect"
- Check DAILY_API_KEY in .env
- Verify API key is active in Daily.co dashboard
- Check browser console for errors

**Problem**: "Room not found"
- Room may have expired (60-minute default)
- Try refreshing and starting new call

### Phone Display Issues

**Problem**: "Patient phone number not available"
- Verify patient has phone number in profile
- Check appointment is confirmed
- Ensure you're logged in as the doctor

## Best Practices

### For Video Calls
1. Test video/audio before patient joins
2. Use good lighting and quiet environment
3. Have backup phone number ready
4. End call properly to mark appointment complete

### For Phone Calls
1. Call from professional phone number
2. Verify patient identity before discussing medical info
3. Document call in appointment notes
4. Follow up with video if needed

## Migration from Twilio

If you previously used Twilio:

1. Old Twilio files removed:
   - `app/Services/TwilioService.php`
   - `config/twilio.php`
   - Twilio webhook routes

2. Update .env:
   - Remove TWILIO_* variables
   - Add DAILY_* variables

3. Existing appointments:
   - Video appointments work immediately
   - Phone appointments now show patient phone

## Support

### Daily.co Resources
- Documentation: https://docs.daily.co/
- API Reference: https://docs.daily.co/reference/rest-api
- Support: https://help.daily.co/

### Common Questions

**Q: Do patients need to install anything?**
A: No, Daily.co works in any modern web browser.

**Q: Can I record video calls?**
A: Yes, with Daily.co paid plans. Enable in room settings.

**Q: What about HIPAA compliance?**
A: Daily.co offers HIPAA-compliant plans. Contact their sales team.

**Q: Can I use my own phone for calls?**
A: Yes! That's the whole point - manual calling is simpler and free.

---

**Built for MedcuraAI - Simple, Practical, Cost-Effective**
