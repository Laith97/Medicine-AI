# Ambient Listening Feature - Required Fixes

## Critical Issues to Fix

### 1. Install WebSocket Server Package
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

### 2. Configure WebSocket Routes
Create `routes/websockets.php`:
```php
<?php

use App\WebSockets\MedicalAudioSocket;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

WebSocketsRouter::webSocket('/ws/medical-audio', MedicalAudioSocket::class);
```

### 3. Update WebSocket Configuration
Add to `config/websockets.php`:
```php
'apps' => [
    [
        'id' => env('PUSHER_APP_ID'),
        'name' => env('APP_NAME'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'path' => env('PUSHER_APP_PATH'),
        'capacity' => null,
        'enable_client_messages' => false,
        'enable_statistics' => true,
        'allowed_origins' => ['*'],
        'allowed_ips' => ['*'],
    ],
],
```

### 4. Fix MedicalAmbientRecorder WebSocket Connection
Update the WebSocket URL in `MedicalAmbientRecorder.js`:
```javascript
// Change from:
const wsUrl = `${protocol}//${window.location.host}/ws/medical-audio?token=${authToken}&visit_id=${visitId}`;

// To:
const wsUrl = `${protocol}//${window.location.host}:6001/ws/medical-audio?token=${authToken}&visit_id=${visitId}`;
```

### 5. Add Google Cloud Speech API Configuration
Add to `.env`:
```env
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_KEY_FILE=path/to/service-account.json
GOOGLE_APPLICATION_CREDENTIALS=path/to/service-account.json
```

### 6. Install Google Cloud Speech API
```bash
composer require google/cloud-speech
```

### 7. Fix WebSocket Handler Dependencies
Update `MedicalAudioSocket.php` constructor:
```php
public function __construct()
{
    // Remove service injection from constructor
    // Services will be resolved when needed
}

private function getTranscriptionService()
{
    if (!$this->transcriptionService) {
        $this->transcriptionService = app(MedicalTranscriptionService::class);
    }
    return $this->transcriptionService;
}
```

### 8. Add WebSocket Server Start Command
Add to `package.json` scripts:
```json
{
    "scripts": {
        "websocket": "php artisan websockets:serve",
        "dev-full": "concurrently \"npm run dev\" \"npm run websocket\""
    }
}
```

### 9. Fix React Component Event Handling
Update `RealTimeTranscript.jsx` to handle missing WebSocket gracefully:
```javascript
useEffect(() => {
    const handleTranscriptUpdate = (event) => {
        const data = event.detail;
        if (data && data.type === 'transcript_update') {
            handleWebSocketMessage(data);
        }
    };

    // Add error handling for missing WebSocket
    const handleConnectionError = () => {
        console.warn('WebSocket connection failed, falling back to browser speech recognition');
        // Implement fallback to browser speech recognition
    };

    window.addEventListener('transcriptUpdate', handleTranscriptUpdate);
    window.addEventListener('websocketError', handleConnectionError);

    return () => {
        window.removeEventListener('transcriptUpdate', handleTranscriptUpdate);
        window.removeEventListener('websocketError', handleConnectionError);
    };
}, []);
```

### 10. Add Fallback to Browser Speech Recognition
Create fallback mechanism in `AmbientAudioRecorder.jsx`:
```javascript
const startRecording = async () => {
    setError(null);
    setIsConnecting(true);
    setStatus('connecting');

    try {
        if (recorderRef.current) {
            await recorderRef.current.startRecording(visitId, authToken);
        }
    } catch (err) {
        console.warn('WebSocket recording failed, falling back to browser speech recognition');
        // Fallback to the existing voice-assistant.js implementation
        if (window.voiceAssistant && window.voiceAssistant.startSession) {
            window.voiceAssistant.startSession();
            setStatus('recording');
            setIsRecording(true);
        } else {
            setError('Recording failed: ' + err.message);
        }
    }
};
```

## Testing Steps

1. **Start WebSocket Server:**
   ```bash
   php artisan websockets:serve
   ```

2. **Test WebSocket Connection:**
   ```javascript
   // In browser console
   const ws = new WebSocket('ws://localhost:6001/ws/medical-audio?token=test&visit_id=123');
   ws.onopen = () => console.log('Connected');
   ws.onerror = (e) => console.error('Connection failed', e);
   ```

3. **Test Audio Recording:**
   - Select a patient
   - Click "Start Ambient Listening"
   - Verify audio is being captured
   - Check for real-time transcription

4. **Test Fallback Mechanism:**
   - Stop WebSocket server
   - Try recording again
   - Verify it falls back to browser speech recognition

## Performance Optimizations

1. **Add Audio Compression:**
   ```javascript
   // In MedicalAmbientRecorder.js
   convertFloat32ToInt16(float32Array) {
       // Add compression before sending
       const compressed = this.compressAudio(float32Array);
       return compressed;
   }
   ```

2. **Implement Buffering:**
   ```javascript
   // Buffer audio chunks before sending
   const audioBuffer = [];
   const BUFFER_SIZE = 1024;
   
   if (audioBuffer.length >= BUFFER_SIZE) {
       this.sendAudioChunk(audioBuffer);
       audioBuffer.length = 0;
   }
   ```

3. **Add Connection Retry Logic:**
   ```javascript
   async connectWithRetry(maxRetries = 3) {
       for (let i = 0; i < maxRetries; i++) {
           try {
               await this.connect();
               return;
           } catch (error) {
               if (i === maxRetries - 1) throw error;
               await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
           }
       }
   }
   ```

## Security Considerations

1. **Add Token Validation:**
   ```php
   // In MedicalAudioSocket.php
   protected function validateMedicalContext($params)
   {
       $token = $params['token'] ?? null;
       $visitId = $params['visit_id'] ?? null;
       
       if (!$token || !$visitId) {
           return false;
       }
       
       // Validate JWT token
       try {
           $payload = JWT::decode($token, config('app.key'), ['HS256']);
           return $payload->visit_id === $visitId;
       } catch (Exception $e) {
           return false;
       }
   }
   ```

2. **Add Rate Limiting:**
   ```php
   // Add to WebSocket handler
   protected $rateLimiter = [];
   
   protected function checkRateLimit($connectionId)
   {
       $now = time();
       $this->rateLimiter[$connectionId] = $this->rateLimiter[$connectionId] ?? [];
       $this->rateLimiter[$connectionId] = array_filter(
           $this->rateLimiter[$connectionId],
           fn($timestamp) => $now - $timestamp < 60
       );
       
       if (count($this->rateLimiter[$connectionId]) > 100) {
           return false;
       }
       
       $this->rateLimiter[$connectionId][] = $now;
       return true;
   }
   ```

## Monitoring and Logging

1. **Add Performance Metrics:**
   ```php
   // Track WebSocket performance
   Log::info('WebSocket metrics', [
       'active_connections' => count($this->activeConnections),
       'audio_chunks_processed' => $this->audioChunksProcessed,
       'average_processing_time' => $this->averageProcessingTime,
   ]);
   ```

2. **Add Health Checks:**
   ```php
   // Add health check endpoint
   Route::get('/health/websocket', function () {
       return response()->json([
           'status' => 'healthy',
           'websocket_server' => 'running',
           'timestamp' => now(),
       ]);
   });
   ```