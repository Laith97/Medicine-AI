#!/bin/bash

# Kiosk Setup Script for Medicine-AI System
# This script sets up a kiosk device with the necessary software and configuration

set -e

# Configuration
KIOSK_NAME=${1:-"Kiosk-$(hostname)"}
KIOSK_LOCATION=${2:-"Default Location"}
SERIAL_NUMBER=${3:-"AUTO-$(date +%s)"}
API_BASE_URL=${4:-"http://localhost:8000/api"}
INSTALL_DIR="/opt/medicine-ai-kiosk"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✓${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root"
   exit 1
fi

log "Starting kiosk setup for: $KIOSK_NAME"

# Update system
log "Updating system packages..."
apt update && apt upgrade -y
success "System updated"

# Install required packages
log "Installing required packages..."
apt install -y \
    chromium-browser \
    xorg \
    openbox \
    lightdm \
    curl \
    wget \
    jq \
    nodejs \
    npm \
    unclutter \
    xdotool \
    xautomation \
    scrot \
    imagemagick \
    alsa-utils \
    pulseaudio \
    pavucontrol \
    x11-xserver-utils

success "Required packages installed"

# Create kiosk user
log "Creating kiosk user..."
if ! id -u kiosk >/dev/null 2>&1; then
    useradd -m -s /bin/bash kiosk
    usermod -a -G audio,video,input kiosk
fi
success "Kiosk user created"

# Create installation directory
log "Creating installation directory..."
mkdir -p $INSTALL_DIR
chown kiosk:kiosk $INSTALL_DIR
success "Installation directory created"

# Download kiosk application
log "Downloading kiosk application..."
cd $INSTALL_DIR

# For demo purposes, create a basic kiosk application
# In production, this would download from your deployment server
cat > kiosk-app.js << 'EOF'
const express = require('express');
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3000;

// Kiosk configuration
const config = {
    name: process.env.KIOSK_NAME || 'Kiosk',
    location: process.env.KIOSK_LOCATION || 'Default',
    serialNumber: process.env.SERIAL_NUMBER || 'AUTO',
    apiBaseUrl: process.env.API_BASE_URL || 'http://localhost:8000/api'
};

let browser;
let page;

// Register kiosk with server
async function registerKiosk() {
    try {
        const response = await fetch(`${config.apiBaseUrl}/kiosks/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                serial_number: config.serialNumber,
                name: config.name,
                location: config.location,
                configuration: {
                    screen_resolution: '1920x1080',
                    touch_enabled: true,
                    printer_connected: false,
                    card_reader: false,
                    biometric_scanner: false,
                    voice_assistant: true,
                    high_contrast_mode: false,
                    auto_logout_minutes: 30
                }
            })
        });

        if (response.ok) {
            console.log('Kiosk registered successfully');
        } else {
            console.error('Failed to register kiosk');
        }
    } catch (error) {
        console.error('Registration error:', error);
    }
}

// Start kiosk application
async function startKiosk() {
    browser = await puppeteer.launch({
        headless: false,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--kiosk',
            '--fullscreen'
        ]
    });

    page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Navigate to kiosk interface
    await page.goto('http://localhost:8000/kiosk/welcome');

    console.log('Kiosk started');
}

// Health check and ping
setInterval(async () => {
    try {
        await fetch(`${config.apiBaseUrl}/kiosks/${config.serialNumber}/ping`, {
            method: 'POST'
        });
    } catch (error) {
        console.error('Ping failed:', error);
    }
}, 60000); // Ping every minute

// Check for pending commands
setInterval(async () => {
    try {
        const response = await fetch(`${config.apiBaseUrl}/kiosks/${config.serialNumber}/commands/pending`);
        if (response.ok) {
            const data = await response.json();
            if (data.data.commands.length > 0) {
                // Process commands
                for (let i = 0; i < data.data.commands.length; i++) {
                    const command = data.data.commands[i];
                    await executeCommand(command, i);
                }
            }
        }
    } catch (error) {
        console.error('Command check failed:', error);
    }
}, 30000); // Check every 30 seconds

async function executeCommand(command, index) {
    console.log(`Executing command: ${command.command}`);

    try {
        let result = 'success';
        let output = '';

        switch (command.command) {
            case 'restart':
                output = 'Restarting kiosk...';
                setTimeout(() => process.exit(0), 2000);
                break;
            case 'shutdown':
                output = 'Shutting down kiosk...';
                setTimeout(() => process.exit(0), 2000);
                break;
            case 'update':
                output = 'Checking for updates...';
                // Implement update logic here
                break;
            case 'diagnostics':
                output = 'Running diagnostics...';
                // Implement diagnostics here
                break;
            default:
                result = 'failed';
                output = `Unknown command: ${command.command}`;
        }

        // Acknowledge command execution
        await fetch(`${config.apiBaseUrl}/kiosks/${config.serialNumber}/commands/acknowledge`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                command_index: index,
                result: result,
                output: output
            })
        });

    } catch (error) {
        console.error('Command execution failed:', error);
    }
}

app.listen(PORT, async () => {
    console.log(`Kiosk application running on port ${PORT}`);
    await registerKiosk();
    await startKiosk();
});

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('Shutting down kiosk...');
    if (browser) {
        await browser.close();
    }
    process.exit(0);
});
EOF

success "Kiosk application created"

# Install Node.js dependencies
log "Installing Node.js dependencies..."
npm init -y
npm install express puppeteer node-fetch
success "Dependencies installed"

# Create systemd service
log "Creating systemd service..."
cat > /etc/systemd/system/medicine-ai-kiosk.service << EOF
[Unit]
Description=Medicine AI Kiosk
After=network.target

[Service]
Type=simple
User=kiosk
WorkingDirectory=$INSTALL_DIR
ExecStart=/usr/bin/node $INSTALL_DIR/kiosk-app.js
Restart=always
RestartSec=5
Environment=KIOSK_NAME=$KIOSK_NAME
Environment=KIOSK_LOCATION=$KIOSK_LOCATION
Environment=SERIAL_NUMBER=$SERIAL_NUMBER
Environment=API_BASE_URL=$API_BASE_URL

[Install]
WantedBy=multi-user.target
EOF

success "Systemd service created"

# Configure LightDM for auto-login
log "Configuring auto-login..."
cat > /etc/lightdm/lightdm.conf << EOF
[Seat:*]
autologin-user=kiosk
autologin-user-timeout=0
user-session=openbox
EOF

success "Auto-login configured"

# Create Openbox configuration for kiosk mode
log "Creating Openbox configuration..."
mkdir -p /home/kiosk/.config/openbox
cat > /home/kiosk/.config/openbox/autostart << EOF
# Disable screen blanking
xset s off
xset -dpms
xset s noblank

# Hide mouse cursor after 5 seconds
unclutter -idle 5 &

# Start kiosk application
cd $INSTALL_DIR
/usr/bin/node kiosk-app.js &
EOF

chown -R kiosk:kiosk /home/kiosk/.config
success "Openbox configuration created"

# Configure Chromium for kiosk mode
log "Configuring Chromium..."
mkdir -p /home/kiosk/.config/chromium
cat > /home/kiosk/.config/chromium/Default/Preferences << EOF
{
  "browser": {
    "show_home_button": false,
    "check_default_browser": false
  },
  "download": {
    "directory_upgrade": true,
    "prompt_for_download": false
  },
  "homepage": "http://localhost:8000/kiosk/welcome",
  "session": {
    "restore_on_startup": 4,
    "startup_urls": ["http://localhost:8000/kiosk/welcome"]
  }
}
EOF

chown -R kiosk:kiosk /home/kiosk/.config/chromium
success "Chromium configured"

# Set proper permissions
log "Setting permissions..."
chown -R kiosk:kiosk $INSTALL_DIR
chmod +x $INSTALL_DIR/kiosk-app.js
success "Permissions set"

# Enable and start services
log "Enabling and starting services..."
systemctl enable medicine-ai-kiosk
systemctl enable lightdm
success "Services enabled"

warning "Setup complete! Please reboot the system to start the kiosk."
warning "After reboot, the kiosk will automatically start and register with the server."
echo ""
echo "Kiosk Details:"
echo "  Name: $KIOSK_NAME"
echo "  Location: $KIOSK_LOCATION"
echo "  Serial Number: $SERIAL_NUMBER"
echo "  API URL: $API_BASE_URL"
echo ""
warning "Make sure the Medicine-AI server is running and accessible at $API_BASE_URL"
