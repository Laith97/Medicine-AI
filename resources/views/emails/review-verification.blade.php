<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Review - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .review-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .rating {
            color: #ffd700;
            font-size: 18px;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .token-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            border: 1px dashed #667eea;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⭐ Review Verification Required</h1>
        <p>{{ config('app.name') }} - Patient Review System</p>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>Thank you for taking the time to review your experience with <strong>{{ $doctor->user->name }}</strong> at {{ config('app.name') }}. Your feedback helps us improve our healthcare services.</p>

        <div class="review-card">
            <h3>Your Review Details:</h3>
            <p><strong>Doctor:</strong> {{ $doctor->user->name }}</p>
            <p><strong>Specialty:</strong> {{ $doctor->specialty->name ?? 'General Practice' }}</p>
            <p><strong>Rating:</strong> <span class="rating">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span> ({{ $review->rating }}/5)</p>
            @if($review->comment)
            <p><strong>Comment:</strong> "{{ $review->comment }}"</p>
            @endif
            <p><strong>Submitted:</strong> {{ $review->created_at->format('M j, Y \a\t g:i A') }}</p>
        </div>

        <div class="warning">
            <strong>⚠️ Verification Required:</strong> To publish your review and help other patients make informed decisions, please verify your email address by clicking the button below or using the verification token.
        </div>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="verify-button">✅ Verify & Publish My Review</a>
        </div>

        <p><strong>Alternative verification method:</strong> If the button doesn't work, you can manually verify your review by visiting the verification page and entering this token:</p>

        <div class="token-box">
            <strong>{{ $token }}</strong>
        </div>

        <p>Copy and paste this token when prompted during the verification process.</p>

        <h4>What happens next?</h4>
        <ul>
            <li>Your review will be published on {{ $doctor->user->name }}'s profile</li>
            <li>Other patients will be able to see your feedback</li>
            <li>You'll receive a confirmation email once published</li>
            <li>You can edit or delete your review within 24 hours if needed</li>
        </ul>

        <p>If you didn't submit this review or have any questions, please contact our support team.</p>

        <p>Thank you for helping us maintain quality healthcare standards!</p>
    </div>

    <div class="footer">
        <p>This verification link will expire in 24 hours for security reasons.</p>
        <p>If you're having trouble, contact us at <a href="mailto:support@medcura.ai">support@medcura.ai</a></p>
        <p><small>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small></p>
    </div>
</body>
</html>