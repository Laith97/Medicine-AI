<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Form Submission</title>
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
            background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .field {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #DE6262;
        }
        .field-label {
            font-weight: bold;
            color: #DE6262;
            margin-bottom: 5px;
        }
        .field-value {
            color: #333;
            word-wrap: break-word;
        }
        .message-field {
            background: white;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #DE6262;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Contact Form Message</h1>
        <p>MedCura AI - Medical Assistant</p>
    </div>
    
    <div class="content">
        <h2>Contact Details</h2>
        
        <div class="field">
            <div class="field-label">Full Name:</div>
            <div class="field-value">{{ $contactName }}</div>
        </div>
        
        <div class="field">
            <div class="field-label">Email Address:</div>
            <div class="field-value">{{ $contactEmail }}</div>
        </div>
        
        @if($contactPhone)
        <div class="field">
            <div class="field-label">Phone Number:</div>
            <div class="field-value">{{ $contactPhone }}</div>
        </div>
        @endif
        
        @if($contactService)
        <div class="field">
            <div class="field-label">Inquiry Type:</div>
            <div class="field-value">{{ $contactService }}</div>
        </div>
        @endif
        
        <div class="field">
            <div class="field-label">Subject:</div>
            <div class="field-value">{{ $contactSubject }}</div>
        </div>
        
        <div class="message-field">
            <div class="field-label">Message:</div>
            <div class="field-value">{!! nl2br(e($messageContent)) !!}</div>
        </div>
    </div>
    
    <div class="footer">
        <p>This message was sent from the MedCura AI contact form.</p>
        <p>Reply directly to this email to respond to the sender.</p>
        <p><small>This email was sent to: info@medcuraai.com, malikqattom@gmail.com, laythfares99@gmail.com</small></p>
    </div>
</body>
</html>