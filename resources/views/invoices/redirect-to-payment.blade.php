<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url={{ $paymentUrl }}">
    <title>Redirecting to Payment...</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
        }
        .redirect-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 2rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="spinner"></div>
        <h2>Redirecting to Secure Payment</h2>
        <p>Please wait while we redirect you to our secure payment processor...</p>
        <p><strong>Invoice #{{ $invoice->id }}</strong> - ${{ number_format($invoice->amount_due, 2) }}</p>
        
        <div style="margin-top: 2rem;">
            <a href="{{ $paymentUrl }}" class="btn" id="payBtn">
                <i class="fas fa-credit-card"></i> Continue to Payment
            </a>
        </div>
        
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">
            If you are not redirected automatically, click the button above.
        </p>
    </div>

    <script>
        // Multiple redirect methods for maximum compatibility
        
        // Method 1: Immediate redirect (fastest)
        setTimeout(function() {
            window.location.replace('{{ $paymentUrl }}');
        }, 1000);
        
        // Method 2: Standard redirect as backup
        setTimeout(function() {
            window.location.href = '{{ $paymentUrl }}';
        }, 2000);
        
        // Method 3: Force redirect as final fallback
        setTimeout(function() {
            window.top.location.href = '{{ $paymentUrl }}';
        }, 3000);
        
        // Manual button click
        document.getElementById('payBtn').addEventListener('click', function(e) {
            e.preventDefault();
            // Try multiple methods
            try {
                window.location.replace('{{ $paymentUrl }}');
            } catch(e1) {
                try {
                    window.location.href = '{{ $paymentUrl }}';
                } catch(e2) {
                    window.open('{{ $paymentUrl }}', '_self');
                }
            }
        });
        
        // Debug information
        // console.log('Payment URL:', '{{ $paymentUrl }}');
        // console.log('Invoice ID:', '{{ $invoice->id }}');
        // console.log('URL Length:', '{{ $paymentUrl }}'.length);
        
        // Check if URL is valid
        if ('{{ $paymentUrl }}'.indexOf('stripe.com') === -1) {
            // console.warn('Warning: This does not appear to be a Stripe URL');
        }
    </script>
</body>
</html>