import base64
import io
from flask import Flask, request, jsonify
import boto3
from PIL import Image

app = Flask(__name__)

# Initialize AWS Rekognition client (assumes credentials are configured)
rekognition = boto3.client('rekognition', region_name='us-east-1')  # Adjust region as needed

@app.route('/track_engagement', methods=['POST'])
def track_engagement():
    try:
        # Get JSON payload
        data = request.get_json()
        if not data or 'frame_base64' not in data:
            return jsonify({'error': 'Missing frame_base64 in payload'}), 400

        frame_base64 = data['frame_base64']

        # Decode base64 string to bytes
        try:
            image_bytes = base64.b64decode(frame_base64)
        except Exception as e:
            return jsonify({'error': f'Invalid base64 encoding: {str(e)}'}), 400

        # Convert bytes to PIL Image for potential processing (though Rekognition can handle bytes directly)
        try:
            image = Image.open(io.BytesIO(image_bytes))
            # Ensure it's in RGB format
            if image.mode != 'RGB':
                image = image.convert('RGB')
            # Convert back to bytes for Rekognition
            buffer = io.BytesIO()
            image.save(buffer, format='JPEG')
            image_bytes = buffer.getvalue()
        except Exception as e:
            return jsonify({'error': f'Invalid image data: {str(e)}'}), 400

        # Call AWS Rekognition detect_faces
        try:
            response = rekognition.detect_faces(
                Image={'Bytes': image_bytes},
                Attributes=['ALL']  # Get all attributes including pose and eye direction
            )
        except Exception as e:
            return jsonify({'error': f'AWS Rekognition error: {str(e)}'}), 500

        # Process faces (assuming first face if multiple)
        faces = response.get('FaceDetails', [])
        if not faces:
            return jsonify({'attention_score': 0.0, 'eye_contact': 0.0, 'participation': 'low'}), 200

        face = faces[0]  # Take the first detected face

        # Extract pose angles
        pose = face.get('Pose', {})
        pitch = pose.get('Pitch', 0)
        yaw = pose.get('Yaw', 0)
        roll = pose.get('Roll', 0)

        # Calculate attention_score (0-1): lower absolute angles indicate better attention
        # Normalize based on typical ranges (-90 to 90 degrees)
        attention_score = max(0, 1 - (abs(pitch) + abs(yaw) + abs(roll)) / 270)

        # Extract eye direction from AWS Rekognition
        eye_contact = 0.0
        if 'EyeDirection' in face:
            eye_direction = face['EyeDirection']
            # Calculate average eye direction confidence
            left_eye_confidence = eye_direction.get('LeftEye', {}).get('Confidence', 0)
            right_eye_confidence = eye_direction.get('RightEye', {}).get('Confidence', 0)
            eye_contact = (left_eye_confidence + right_eye_confidence) / 2
        else:
            # Fallback to pose-based estimation
            eye_yaw = abs(yaw)
            eye_contact = max(0, 100 - eye_yaw * 2)

        # Calculate participation based on combined metrics
        combined_score = (attention_score + eye_contact / 100) / 2
        if combined_score >= 0.7:
            participation = 'high'
        elif combined_score >= 0.4:
            participation = 'medium'
        else:
            participation = 'low'

        return jsonify({
            'attention_score': round(attention_score, 2),
            'eye_contact': round(eye_contact, 2),
            'participation': participation
        }), 200

    except Exception as e:
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5002)
