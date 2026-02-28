# Ambient Listening Feature - Setup Instructions

## Quick Setup Guide

### 1. Install Required Packages

```bash
# Install Laravel WebSockets
composer require beyondcode/laravel-websockets

# Install Google Cloud Speech API
composer require google/cloud-speech

# Publish WebSocket configuration
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"

# Publish and run migrations
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
```

### 2. Environment Configuration

Add to your `.env` file:

```env
# WebSocket Configuration
LARAVEL_WEBSOCKETS_PORT=6001
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1

# Google Cloud Speech API (Optional - for enhanced transcription)
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=path/to/service-account.json
MEDICAL_TRANSCRIPTION_PROVIDER=google

# Broadcasting
BROADCAST_DRIVER=pusher
```

### 3. Start the Services

```bash
# Terminal 1: Start Laravel application
php artisan serve

# Terminal 2: Start WebSocket server
php artisan websockets:serve

# Terminal 3: Start frontend development server
npm run dev
```

### 4. Test the Feature

1. Navigate to `/ai/voice-assistant`
2. Select a patient
3. Click "Start Ambient Listening"
4. Speak into your microphone
5. Verify real-time transcription appears

## Troubleshooting

### WebSocket Connection Issues

If WebSocket connection fails:

1. **Check WebSocket server is running:**
   ```bash
   php artisan websockets:serve
   ```

2. **Verify port is not blocked:**
   ```bash
   netstat -an | grep 6001
   ```

3. **Check browser console for errors:**
   - Open Developer Tools (F12)
   - Look for WebSocket connection errors
   - Verify the WebSocket URL is correct

### Fallback to Browser Speech Recognition

If WebSocket fails, the system automatically falls back to browser-based speech recognition:

1. **Chrome/Edge:** Uses Web Speech API
2. **Firefox:** Limited support
3. **Safari:** Partial support

### Audio Permission Issues

If microphone access is denied:

1. **Chrome:** Click the microphone icon in address bar
2. **Firefox:** Go to Preferences > Privacy & Security > Permissions
3. **Safari:** Go to Safari > Preferences > Websites > Microphone

### Performance Issues

If transcription is slow or inaccurate:

1. **Check network connection**
2. **Verify microphone quality**
3. **Reduce background noise**
4. **Use Chrome for best performance**

## Feature Status

### ✅ Working Features:
- Real-time audio capture
- Browser-based speech recognition
- Language detection (Arabic/English)
- Medical terminology recognition
- Session management
- Patient selection
- Diagnosis creation
- Fallback mechanisms

### 🔧 Requires Setup:
- WebSocket server (needs package installation)
- Google Cloud Speech API (optional enhancement)
- Real-time speaker diarization (needs WebSocket)

### 📋 Manual Testing Checklist:

1. **Basic Functionality:**
   - [ ] Patient selection works
   - [ ] Recording starts/stops properly
   - [ ] Audio permissions granted
   - [ ] Transcription appears in real-time

2. **Language Detection:**
   - [ ] Arabic speech detected correctly
   - [ ] English speech detected correctly
   - [ ] Language switching works

3. **Medical Features:**
   - [ ] Medical terms highlighted
   - [ ] Chart fields auto-populated
   - [ ] Diagnosis creation works
   - [ ] Session persistence

4. **Error Handling:**
   - [ ] Microphone permission denied handled
   - [ ] Network errors handled gracefully
   - [ ] WebSocket failures fall back to browser API
   - [ ] Empty transcription handled

5. **Performance:**
   - [ ] Real-time transcription responsive
   - [ ] No memory leaks during long sessions
   - [ ] Audio quality acceptable
   - [ ] UI remains responsive

## Advanced Configuration

### Custom Medical Dictionary

Add medical terms to improve recognition:

```javascript
// In voice-assistant.js
const medicalTerms = [
    'hypertension', 'diabetes', 'myocardial infarction',
    'cerebrovascular accident', 'pneumonia', 'bronchitis',
    // Add more terms as needed
];
```

### Speaker Diarization Settings

Configure speaker detection:

```php
// In config/websockets.php
'medical_audio' => [
    'enable_speaker_diarization' => true,
    'max_speakers' => 3, // Doctor, Patient, Nurse
    'min_speaker_count' => 2,
    'speaker_change_threshold' => 0.15,
],
```

### Audio Quality Settings

Optimize for medical consultations:

```javascript
// In MedicalAmbientRecorder.js
const audioConstraints = {
    echoCancellation: true,
    noiseSuppression: true,
    autoGainControl: true,
    sampleRate: 16000, // Optimal for speech
    channelCount: 1,   // Mono for voice
};
```

## Security Considerations

### Token Validation

Implement proper JWT token validation:

```php
// In MedicalAudioSocket.php
protected function validateMedicalContext($params)
{
    $token = $params['token'] ?? null;
    
    try {
        $payload = JWT::decode($token, config('app.key'), ['HS256']);
        return $payload->user_id && $payload->visit_id;
    } catch (Exception $e) {
        return false;
    }
}
```

### Rate Limiting

Prevent abuse:

```php
// Add rate limiting to WebSocket handler
protected function checkRateLimit($connectionId)
{
    // Implement rate limiting logic
    return true; // Allow for now
}
```

### Data Encryption

Ensure HIPAA compliance:

```php
// Encrypt sensitive data before storage
$encryptedTranscript = encrypt($transcriptData);
```

## Monitoring and Logging

### Performance Metrics

Track system performance:

```php
Log::info('Ambient listening metrics', [
    'session_duration' => $sessionDuration,
    'audio_chunks_processed' => $audioChunks,
    'transcription_accuracy' => $accuracy,
    'speaker_changes' => $speakerChanges,
]);
```

### Health Checks

Monitor system health:

```bash
# Check WebSocket server status
curl http://localhost:6001/health

# Check Laravel application
php artisan health:check
```

## Support

For issues or questions:

1. Check the Code Issues Panel for detailed findings
2. Review the AMBIENT_LISTENING_FIXES.md file
3. Test with the manual checklist above
4. Check browser console for JavaScript errors
5. Verify WebSocket server is running on port 6001