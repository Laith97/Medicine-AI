#!/usr/bin/env python3
"""
Whisper + Pyannote Speaker Diarization Service
Run: pip install flask openai-whisper pyannote.audio torch
Then: python whisper_diarization.py
"""

from flask import Flask, request, jsonify
import whisper
import torch
from pyannote.audio import Pipeline
import os
import tempfile

app = Flask(__name__)

# Load models
whisper_model = whisper.load_model("base")
HF_TOKEN = os.getenv("HUGGINGFACE_TOKEN", "hf_your_token")
diarization_pipeline = Pipeline.from_pretrained(
    "pyannote/speaker-diarization-3.1",
    use_auth_token=HF_TOKEN
)

@app.route('/transcribe-diarize', methods=['POST'])
def transcribe_diarize():
    audio_file = request.files['audio']
    language = request.form.get('language', 'ar')
    
    with tempfile.NamedTemporaryFile(delete=False, suffix='.wav') as tmp:
        audio_file.save(tmp.name)
        audio_path = tmp.name
    
    try:
        result = whisper_model.transcribe(audio_path, language=language)
        diarization = diarization_pipeline(audio_path)
        
        aligned_segments = []
        for segment in result["segments"]:
            speaker = None
            for turn, _, speaker_label in diarization.itertracks(yield_label=True):
                if turn.start <= segment["start"] <= turn.end:
                    speaker = int(speaker_label.split('_')[-1]) + 1
                    break
            
            aligned_segments.append({
                "speaker_tag": speaker or 1,
                "text": segment["text"].strip(),
                "start_time": segment["start"]
            })
        
        formatted = "\n".join([f"[Speaker {s['speaker_tag']}]: {s['text']}" for s in aligned_segments])
        
        return jsonify({
            "success": True,
            "transcription": formatted,
            "segments": aligned_segments
        })
    finally:
        os.remove(audio_path)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001)
