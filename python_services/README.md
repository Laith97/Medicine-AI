# Whisper + Pyannote Diarization Service

## Setup

1. Install Python dependencies:
```bash
cd python_services
pip install -r requirements.txt
```

2. Get HuggingFace token:
   - Go to https://huggingface.co/settings/tokens
   - Create a token
   - Accept pyannote terms: https://huggingface.co/pyannote/speaker-diarization-3.1

3. Set environment variable:
```bash
export HUGGINGFACE_TOKEN=your_token_here
```

4. Run the service:
```bash
python whisper_diarization.py
```

Service will run on http://localhost:5001

## Usage from Laravel

The service is already integrated in VoiceAssistantController.
It will automatically use this service if it's running.
