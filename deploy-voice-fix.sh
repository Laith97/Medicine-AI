#!/bin/bash

# Deploy voice assistant speaker separation fix to production

echo "🚀 Deploying voice assistant speaker separation fix..."

# Production server details
PROD_USER="u741521150"
PROD_HOST="srv1.medcuraai.com"
PROD_PATH="/home/u741521150/domains/medcuraai.com/public_html"

# Files to deploy
FILES=(
    "app/Http/Controllers/VoiceAssistantController.php"
)

echo "📦 Uploading files to production..."
for file in "${FILES[@]}"; do
    echo "  - Uploading $file"
    scp "$file" "${PROD_USER}@${PROD_HOST}:${PROD_PATH}/$file"
done

echo "🔄 Running production commands..."
ssh "${PROD_USER}@${PROD_HOST}" << 'ENDSSH'
cd /home/u741521150/domains/medcuraai.com/public_html

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Production deployment complete!"
ENDSSH

echo "✅ Deployment finished!"
echo ""
echo "📝 Changes deployed:"
echo "  - Fixed speaker separation detection"
echo "  - Added AI-based speaker separation for poor diarization"
echo "  - Improved transcription formatting"
echo ""
echo "🧪 Test in production by recording a conversation"
