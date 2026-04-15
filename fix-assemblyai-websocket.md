# Fix AssemblyAI WebSocket Error

## Issue
AssemblyAI WebSocket error occurs when stopping recording: "Check your internet connection and microphone permissions"

## Root Cause
1. WebSocket server not running on ports 6001/6002
2. AssemblyAI connection timeout
3. Missing fallback mechanism

## Quick Solutions

### Option 1: Start WebSocket Server
```bash
# Install WebSocket package if not installed
composer require beyondcode/laravel-websockets

# Start the WebSocket server
php artisan websockets:serve --port=6001
```

### Option 2: Fix Environment Configuration
Add to `.env`:
```env
# AssemblyAI Configuration
ASSEMBLYAI_API_KEY=416794df07f34ec58a9b811223a89193
ASSEMBLYAI_WEBSOCKET_URL=wss://api.assemblyai.com/v2/realtime/ws

# WebSocket Configuration
WEBSOCKET_HOST=localhost
WEBSOCKET_PORT=6001
WEBSOCKET_SSL_PORT=6002
```

### Option 3: Disable AssemblyAI Temporarily
In `MedicalAmbientRecorder.js`, modify the `startRecording` method:

```javascript
// Skip AssemblyAI connection for now
if (false && assemblyConfig) { // Temporarily disabled
    console.log('🔗 Connecting to AssemblyAI...', assemblyConfig);
    // ... AssemblyAI connection code
}
```

### Option 4: Improve Error Handling
Update the error handling in `AmbientAudioRecorder.jsx`:

```javascript
} catch (err) {
    console.warn('Recording failed, trying fallback...', err);
    
    // Better fallback mechanism
    if (window.voiceAssistant?.startSession) {
        await window.voiceAssistant.startSession();
        setStatus('recording');
        setIsRecording(true);
        setIsConnecting(false);
        setError(null);
    } else {
        setError('Microphone access denied. Please check permissions.');
    }
}
```

## Immediate Fix for Your Issue

1. **Check if WebSocket server is running:**
```bash
netstat -an | grep 6001
```

2. **If not running, start it:**
```bash
php artisan websockets:serve --port=6001 &
```

3. **Or disable AssemblyAI temporarily** by editing the JavaScript file to skip the WebSocket connection.

## Long-term Solution

Follow the complete setup in `AMBIENT_LISTENING_FIXES.md` to properly configure:
- WebSocket server
- AssemblyAI integration
- Fallback mechanisms
- Error handling

The appointment ID 34 exists and is accessible at `http://127.0.0.1:8000/doctor/appointments/34`. The WebSocket error is a separate issue with the ambient listening feature.