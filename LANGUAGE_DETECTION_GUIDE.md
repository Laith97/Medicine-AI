# Language Detection & Transcription Guide

## How Auto-Detect Works

### When you select "Auto Detect" language:

1. **AssemblyAI Language Detection** (Primary for English)
   - AssemblyAI automatically detects the language
   - If English is detected → Uses AssemblyAI (best quality for English medical transcription)
   - Supports real-time transcription with speaker diarization

2. **Arabic Detection** (Automatic GPT-4o Routing)
   - If Arabic is detected → Automatically routes to GPT-4o Audio API
   - GPT-4o provides high-quality Arabic transcription with speaker diarization
   - No real-time display (processes after recording stops for best quality)

3. **Fallback Chain**
   - If primary method fails → Falls back to OpenAI Whisper
   - Ensures transcription always completes

## Language Selection Options

### ✨ Auto Detect (Recommended)
- **Best for**: Mixed language environments or uncertain language
- **How it works**: 
  - System detects language automatically
  - Routes to best transcription service for that language
  - English → AssemblyAI (real-time)
  - Arabic → GPT-4o (post-processing)

### 🇸🇦 Arabic
- **Service**: GPT-4o Audio API
- **Features**: 
  - High-quality transcription
  - Speaker diarization (identifies different speakers)
  - Post-processing (no real-time display)
- **Display**: Shows "Arabic Recording Active" message during recording

### 🇺🇸 English
- **Service**: AssemblyAI
- **Features**:
  - Real-time transcription
  - Medical terminology recognition
  - Speaker diarization
  - Live display of transcript

### 🇫🇷 French / 🇪🇸 Spanish / 🇩🇪 German
- **Service**: AssemblyAI with language detection
- **Features**: Similar to English with language-specific optimization

## Technical Implementation

### Backend Logic (VoiceAssistantController.php)

```php
// Auto-detect logic
if (!empty($language) && $language !== 'auto') {
    $langCode = substr($language, 0, 2);
    $config['language_code'] = $langCode;
} else {
    $config['language_detection'] = true; // Enable auto-detection
}

// Arabic routing
if (isset($language) && strpos($language, 'ar') === 0) {
    // Use GPT-4o Audio for Arabic
    $result = $this->processWithGPT4oAudio($audioPath, $language);
    // Fallback to Whisper if needed
}

// English and other languages
$result = $this->processWithAssemblyAI($audioPath, $language);
```

### Frontend Components

1. **AmbientAudioRecorder.jsx**
   - Sends selected language to backend
   - Handles recording state

2. **RealTimeTranscript.jsx**
   - Shows real-time transcript for English (AssemblyAI)
   - Shows "Arabic Recording Active" for Arabic (no real-time)
   - Displays final transcript after processing

## User Experience

### English Recording:
1. Select patient
2. Choose "English" or "Auto Detect"
3. Click Start
4. See real-time transcript appear
5. Click Stop
6. Transcript is finalized

### Arabic Recording:
1. Select patient
2. Choose "Arabic" or "Auto Detect"
3. Click Start
4. See "Arabic Recording Active" message (no real-time text)
5. Click Stop
6. Wait for processing (10-30 seconds)
7. High-quality diarized transcript appears

## Why No Real-Time for Arabic?

- GPT-4o Audio API processes complete audio files (not streaming)
- Provides superior quality and speaker identification
- Worth the wait for accurate medical documentation
- Real-time would require lower-quality streaming service

## Troubleshooting

### Auto-Detect Not Working?
- Ensure microphone permissions are granted
- Check internet connection
- Verify patient is selected before recording

### Wrong Language Detected?
- Manually select the language instead of "Auto Detect"
- Speak clearly and avoid background noise
- Ensure primary language is spoken first

### No Transcript Appearing?
- Check browser console for errors
- Verify API keys are configured in .env
- Ensure audio is being recorded (check microphone indicator)

## Configuration

### Environment Variables (.env)
```env
# AssemblyAI (for English real-time)
ASSEMBLYAI_API_KEY=your_key_here

# OpenAI (for Arabic GPT-4o and Whisper fallback)
OPENAI_API_KEY=your_key_here

# Transcription Provider
TRANSCRIPTION_PROVIDER=assemblyai
```

## Best Practices

1. **Always select a patient first** before starting recording
2. **Use Auto Detect** unless you're certain of the language
3. **Speak clearly** for better transcription accuracy
4. **Minimize background noise** for optimal results
5. **Wait for processing** to complete before navigating away
6. **Review transcript** before generating clinical documentation

## Summary

✅ **Auto-Detect is Smart**: Automatically routes to the best service for each language
✅ **English = Real-Time**: See transcript as you speak
✅ **Arabic = High-Quality**: Wait for processing, get superior results
✅ **Fallback Protection**: Multiple services ensure transcription always works
