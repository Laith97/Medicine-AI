from flask import Flask, request, jsonify
import base64
import numpy as np
import rnnoise

app = Flask(__name__)

# Initialize RNNoise state
rnnoise_state = rnnoise.RNNoise()

@app.route('/filter_audio', methods=['POST'])
def filter_audio():
    try:
        # Parse JSON payload
        data = request.get_json()
        if not data or 'audio_base64' not in data:
            return jsonify({'error': 'Missing audio_base64 in payload'}), 400

        audio_base64 = data['audio_base64']

        # Decode base64 to bytes
        try:
            audio_bytes = base64.b64decode(audio_base64)
        except Exception as e:
            return jsonify({'error': 'Invalid base64 encoding'}), 400

        # Convert bytes to numpy array (PCM 16-bit)
        audio_np = np.frombuffer(audio_bytes, dtype=np.int16)

        # Ensure audio is in float32 for RNNoise (normalized to [-1, 1])
        audio_float = audio_np.astype(np.float32) / 32768.0

        # Process audio in frames (RNNoise expects 480 samples per frame at 48kHz)
        frame_size = 480
        cleaned_frames = []

        for i in range(0, len(audio_float), frame_size):
            frame = audio_float[i:i + frame_size]
            if len(frame) < frame_size:
                # Pad with zeros if necessary
                frame = np.pad(frame, (0, frame_size - len(frame)), 'constant')

            # Apply RNNoise
            cleaned_frame = rnnoise_state.process_frame(frame)
            cleaned_frames.append(cleaned_frame)

        # Concatenate cleaned frames
        cleaned_audio = np.concatenate(cleaned_frames)

        # Convert back to int16
        cleaned_int16 = (cleaned_audio * 32768.0).astype(np.int16)

        # Encode to base64
        cleaned_bytes = cleaned_int16.tobytes()
        cleaned_base64 = base64.b64encode(cleaned_bytes).decode('utf-8')

        return jsonify({'cleaned_audio_base64': cleaned_base64})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5003, debug=False)
