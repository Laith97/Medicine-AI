#!/bin/bash

echo "========================================="
echo "Twilio Phone & Video Call Setup"
echo "========================================="
echo ""

# Install Twilio SDK
echo "📦 Installing Twilio SDK..."
composer require twilio/sdk

echo ""
echo "✅ Twilio SDK installed successfully!"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Please create .env file first"
    exit 1
fi

echo "📝 Adding Twilio configuration to .env..."
echo ""

# Check if Twilio config already exists
if grep -q "TWILIO_ACCOUNT_SID" .env; then
    echo "⚠️  Twilio configuration already exists in .env"
    echo "Please update manually if needed"
else
    # Add Twilio config to .env
    cat >> .env << 'EOF'

# Twilio Configuration
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_API_KEY_SID=your_api_key_sid_here
TWILIO_API_KEY_SECRET=your_api_key_secret_here
EOF
    echo "✅ Twilio configuration added to .env"
fi

echo ""
echo "========================================="
echo "Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Sign up at https://www.twilio.com/try-twilio"
echo "2. Get your credentials from Twilio Console"
echo "3. Update .env with your Twilio credentials"
echo "4. Test with trial account (free $15 credit)"
echo ""
echo "For detailed instructions, see TWILIO_SETUP.md"
echo ""
