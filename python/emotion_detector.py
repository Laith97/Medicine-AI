from flask import Flask, request, jsonify
import boto3
import base64
import json
from io import BytesIO
from PIL import Image

app = Flask(__name__)

# Initialize AWS Rekognition client
rekognition = boto3.client('rekognition')

@app.route('/detect_emotion', methods=['POST'])
def detect_emotion():
    try:
        # Get JSON payload
        data = request.get_json()
        if not data or 'frame_base64' not in data:
            return jsonify({'error': 'Invalid input: frame_base64 required'}), 400

        frame_base64 = data['frame_base64']

        # Decode base64 image
        try:
            image_data = base64.b64decode(frame_base64)
            image = Image.open(BytesIO(image_data))
            # Convert to bytes for Rekognition
            image_bytes = BytesIO()
            image.save(image_bytes, format='JPEG')
            image_bytes = image_bytes.getvalue()
        except Exception as e:
            return jsonify({'error': f'Invalid base64 image: {str(e)}'}), 400

        # Call AWS Rekognition detect_faces
        try:
            response = rekognition.detect_faces(
                Image={'Bytes': image_bytes},
                Attributes=['EMOTIONS']
            )
        except Exception as e:
            return jsonify({'error': f'AWS Rekognition API error: {str(e)}'}), 500

        # Process response to find dominant emotion
        if not response['FaceDetails']:
            return jsonify({'error': 'No faces detected in the image'}), 400

        face = response['FaceDetails'][0]  # Take the first face
        emotions = face.get('Emotions', [])

        if not emotions:
            return jsonify({'error': 'No emotions detected'}), 400

        # Find dominant emotion
        dominant_emotion = max(emotions, key=lambda e: e['Confidence'])
        emotion = dominant_emotion['Type']
        confidence = dominant_emotion['Confidence']

        return jsonify({'emotion': emotion, 'confidence': confidence})

    except json.JSONDecodeError:
        return jsonify({'error': 'Invalid JSON payload'}), 400
    except Exception as e:
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001)
