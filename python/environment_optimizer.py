from flask import Flask, request, jsonify
import cv2
import numpy as np
import base64
from PIL import Image
import io

app = Flask(__name__)

def decode_base64_image(base64_string):
    """Decode base64 string to OpenCV image."""
    try:
        image_data = base64.b64decode(base64_string)
        image = Image.open(io.BytesIO(image_data))
        return cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)
    except Exception as e:
        raise ValueError(f"Failed to decode base64 image: {str(e)}")

def encode_image_to_base64(image):
    """Encode OpenCV image to base64 string."""
    try:
        _, buffer = cv2.imencode('.jpg', image)
        return base64.b64encode(buffer).decode('utf-8')
    except Exception as e:
        raise ValueError(f"Failed to encode image to base64: {str(e)}")

def apply_background_blur(image):
    """Apply background blur using body segmentation or fallback mask."""
    try:
        # Attempt body segmentation using OpenCV's background subtractor
        subtractor = cv2.createBackgroundSubtractorMOG2(history=100, varThreshold=50, detectShadows=True)
        fg_mask = subtractor.apply(image)

        # Refine mask (simple morphological operations)
        kernel = np.ones((5, 5), np.uint8)
        fg_mask = cv2.morphologyEx(fg_mask, cv2.MORPH_OPEN, kernel)
        fg_mask = cv2.morphologyEx(fg_mask, cv2.MORPH_CLOSE, kernel)

        # If mask is mostly empty, fallback to simple center mask
        if np.sum(fg_mask) < (fg_mask.size * 0.1):
            height, width = image.shape[:2]
            center_mask = np.zeros((height, width), dtype=np.uint8)
            cv2.ellipse(center_mask, (width//2, height//2), (width//4, height//3), 0, 0, 360, 255, -1)
            fg_mask = center_mask

        # Blur background
        blurred = cv2.GaussianBlur(image, (21, 21), 0)
        foreground = cv2.bitwise_and(image, image, mask=fg_mask)
        background = cv2.bitwise_and(blurred, blurred, mask=cv2.bitwise_not(fg_mask))
        return cv2.add(foreground, background)
    except Exception as e:
        # Fallback: simple blur if segmentation fails
        return cv2.GaussianBlur(image, (15, 15), 0)

def adjust_lighting(image):
    """Adjust lighting/brightness if detected as poor."""
    try:
        # Calculate average brightness
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        avg_brightness = np.mean(gray)

        # If brightness is below threshold, increase it
        if avg_brightness < 100:
            alpha = 1.5  # Contrast control
            beta = 30    # Brightness control
            return cv2.convertScaleAbs(image, alpha=alpha, beta=beta)
        return image
    except Exception as e:
        return image

@app.route('/optimize_environment', methods=['POST'])
def optimize_environment():
    try:
        data = request.get_json()
        if not data or 'frame_base64' not in data:
            return jsonify({'error': 'Missing frame_base64 in request'}), 400

        # Decode image
        image = decode_base64_image(data['frame_base64'])

        # Apply background blur
        blurred_image = apply_background_blur(image)

        # Adjust lighting
        optimized_image = adjust_lighting(blurred_image)

        # Encode back to base64
        optimized_base64 = encode_image_to_base64(optimized_image)

        return jsonify({'optimized_frame_base64': optimized_base64})

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 400
    except Exception as e:
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5004, debug=False)
