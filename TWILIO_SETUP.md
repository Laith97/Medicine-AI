# Twilio Phone & Video Call Implementation

## Setup Instructions

### 1. Install Twilio SDK

```bash
composer require twilio/sdk
```

### 2. Configure Environment Variables

Add these to your `.env` file:

```env
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_API_KEY_SID=your_api_key_sid_here
TWILIO_API_KEY_SECRET=your_api_key_secret_here
```

### 3. Get Twilio Credentials

1. Sign up at https://www.twilio.com/try-twilio (Free $15 credit)
2. Get your Account SID and Auth Token from the console
3. Get a phone number from Twilio
4. Create API Key for video: Console → Account → API Keys → Create new API Key

### 4. Database Migration

The appointment table already has `meeting_link` and `meeting_id` fields, so no migration needed.

## Features Implemented

### Phone Calls
- **Initiate Call**: Doctor can call patient directly from appointment
- **Call Status Tracking**: Real-time call status updates
- **Call Logs**: Stored in appointment `meeting_id`

### Video Calls
- **Video Room**: Full-featured video consultation room
- **Controls**: Mute/unmute, camera on/off, end call
- **Real-time**: Twilio Video SDK for low-latency video
- **Recording**: Can be enabled in Twilio console

## Usage

### For Phone Calls

```javascript
// From appointment page
fetch(`/api/appointments/${appointmentId}/call/initiate`, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json'
    }
}).then(response => response.json())
  .then(data => console.log('Call initiated:', data.call_sid));
```

### For Video Calls

```javascript
// Redirect to video room
window.location.href = `/video/room/${appointmentId}`;
```

## Adding Buttons to Appointment Page

Add these buttons to your appointment show/details page:

```html
@if($appointment->appointment_type === 'phone_call')
    <button onclick="initiatePhoneCall({{ $appointment->id }})" class="btn btn-success">
        <i class="fas fa-phone"></i> Call Patient
    </button>
@endif

@if($appointment->appointment_type === 'video_call')
    <a href="{{ route('video.room', $appointment->id) }}" class="btn btn-primary">
        <i class="fas fa-video"></i> Start Video Call
    </a>
@endif

<script>
function initiatePhoneCall(appointmentId) {
    fetch(`/api/appointments/${appointmentId}/call/initiate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Calling patient...');
        } else {
            alert('Error: ' + data.error);
        }
    });
}
</script>
```

## Testing with Free Trial

### Phone Calls
1. Verify your phone number in Twilio console
2. Test calls will have a trial message
3. Only verified numbers can receive calls

### Video Calls
- No restrictions on video during trial
- Works exactly like production
- $15 credit = ~3,750 minutes of video

## Upgrade to Production

When ready to go live:
1. Add payment method to Twilio
2. Upgrade account (removes trial restrictions)
3. No code changes needed!

## API Endpoints

### Phone
- `POST /api/appointments/{id}/call/initiate` - Start call
- `POST /twilio/call/status` - Webhook for call status

### Video
- `POST /api/appointments/{id}/video/token` - Get video token
- `GET /video/room/{id}` - Video consultation room
- `POST /api/appointments/{id}/video/end` - End video call

## Security

- All routes require authentication
- Users can only access their own appointments
- Video tokens expire after 1 hour
- CSRF protection on all endpoints

## Troubleshooting

### Phone calls not working
- Check phone number is verified in Twilio console
- Verify TWILIO_PHONE_NUMBER format: +1234567890
- Check Twilio logs in console

### Video not connecting
- Verify API Key credentials
- Check browser permissions for camera/microphone
- Test on HTTPS (required for WebRTC)

## Cost Estimates

### After Free Trial ($15)
- **Voice**: $0.0085/minute (~$0.51/hour)
- **Video**: $0.004/minute (~$0.24/hour)
- **SMS**: $0.0079/message

### Example Monthly Cost (100 appointments)
- 50 phone calls (10 min each): $4.25
- 50 video calls (20 min each): $40.00
- **Total**: ~$44.25/month

## Next Steps

1. Install Twilio SDK: `composer require twilio/sdk`
2. Add credentials to `.env`
3. Test with trial account
4. Add buttons to appointment pages
5. Go live when ready!
