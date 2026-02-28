# Kiosk Setup Script for Medicine-AI System (Windows)
# This script sets up a Windows kiosk device with the necessary software and configuration

param(
    [string]$KioskName = "Kiosk-$env:COMPUTERNAME",
    [string]$KioskLocation = "Default Location",
    [string]$SerialNumber = "AUTO-$(Get-Date -Format 'yyyyMMddHHmmss')",
    [string]$ApiBaseUrl = "http://localhost:8000/api",
    [string]$InstallDir = "$env:ProgramFiles\MedicineAI-Kiosk"
)

# Configuration
$ErrorActionPreference = "Stop"

# Colors for output
$Green = "Green"
$Red = "Red"
$Yellow = "Yellow"
$Blue = "Cyan"
$White = "White"

function Write-Log {
    param([string]$Message, [string]$Color = $White)
    Write-Host "[$((Get-Date).ToString('yyyy-MM-dd HH:mm:ss'))] $Message" -ForegroundColor $Color
}

function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor $Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor $Red
}

function Write-Warning {
    param([string]$Message)
    Write-Host "⚠ $Message" -ForegroundColor $Yellow
}

# Check if running as administrator
if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "This script must be run as Administrator"
    exit 1
}

Write-Log "Starting kiosk setup for: $KioskName" $Blue

# Create installation directory
Write-Log "Creating installation directory..."
if (-not (Test-Path $InstallDir)) {
    New-Item -ItemType Directory -Path $InstallDir -Force | Out-Null
}
Write-Success "Installation directory created"

# Install Chocolatey if not present
Write-Log "Checking for Chocolatey..."
if (-not (Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Log "Installing Chocolatey..."
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
    Write-Success "Chocolatey installed"
} else {
    Write-Success "Chocolatey already installed"
}

# Install required software
Write-Log "Installing required software..."
choco install -y nodejs googlechrome firefox

# Install additional tools
Write-Log "Installing additional tools..."
choco install -y git vscode autohotkey

Write-Success "Required software installed"

# Create kiosk application
Write-Log "Creating kiosk application..."
$appCode = @"
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
                    auto_logout_minutes: 30,
                    platform: 'windows'
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
            '--disable-gpu',
            '--disable-web-security',
            '--kiosk',
            '--fullscreen',
            '--disable-features=VizDisplayCompositor'
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
"@

$appCode | Out-File -FilePath "$InstallDir\kiosk-app.js" -Encoding UTF8
Write-Success "Kiosk application created"

# Create package.json
Write-Log "Creating package.json..."
$packageJson = @"
{
  "name": "medicine-ai-kiosk",
  "version": "1.0.0",
  "description": "Medicine AI Kiosk Application",
  "main": "kiosk-app.js",
  "scripts": {
    "start": "node kiosk-app.js",
    "dev": "nodemon kiosk-app.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "puppeteer": "^21.0.0",
    "node-fetch": "^3.3.2"
  },
  "keywords": ["kiosk", "medicine", "healthcare"],
  "author": "Medicine AI",
  "license": "MIT"
}
"@

$packageJson | Out-File -FilePath "$InstallDir\package.json" -Encoding UTF8
Write-Success "package.json created"

# Install Node.js dependencies
Write-Log "Installing Node.js dependencies..."
Push-Location $InstallDir
npm install
Pop-Location
Write-Success "Dependencies installed"

# Create kiosk startup script
Write-Log "Creating startup script..."
$startupScript = @"
@echo off
echo Starting Medicine AI Kiosk...

REM Set environment variables
set KIOSK_NAME=$KioskName
set KIOSK_LOCATION=$KioskLocation
set SERIAL_NUMBER=$SerialNumber
set API_BASE_URL=$ApiBaseUrl

REM Change to installation directory
cd /d "$InstallDir"

REM Start the kiosk application
npm start

pause
"@

$startupScript | Out-File -FilePath "$InstallDir\start-kiosk.bat" -Encoding ASCII
Write-Success "Startup script created"

# Create kiosk service (using NSSM for Windows service)
Write-Log "Setting up kiosk as a Windows service..."
$nssmPath = "$InstallDir\nssm.exe"

# Download NSSM if not present
if (-not (Test-Path $nssmPath)) {
    Write-Log "Downloading NSSM..."
    Invoke-WebRequest -Uri "https://nssm.cc/release/nssm-2.24.zip" -OutFile "$InstallDir\nssm.zip"
    Expand-Archive -Path "$InstallDir\nssm.zip" -DestinationPath "$InstallDir\nssm-temp"
    Copy-Item "$InstallDir\nssm-temp\nssm-2.24\win64\nssm.exe" $nssmPath
    Remove-Item "$InstallDir\nssm.zip", "$InstallDir\nssm-temp" -Recurse -Force
    Write-Success "NSSM downloaded"
}

# Install service
Write-Log "Installing kiosk service..."
& $nssmPath install MedicineAI-Kiosk "$InstallDir\start-kiosk.bat"
& $nssmPath set MedicineAI-Kiosk AppDirectory $InstallDir
& $nssmPath set MedicineAI-Kiosk DisplayName "Medicine AI Kiosk"
& $nssmPath set MedicineAI-Kiosk Description "Medicine AI Kiosk Application Service"
& $nssmPath set MedicineAI-Kiosk Start SERVICE_AUTO_START

Write-Success "Kiosk service installed"

# Configure Windows for kiosk mode
Write-Log "Configuring Windows kiosk mode..."

# Disable Windows Defender real-time monitoring temporarily for setup
Set-MpPreference -DisableRealtimeMonitoring $true

# Create kiosk user account (optional)
Write-Log "Creating kiosk user account..."
$password = ConvertTo-SecureString "Kiosk@123!" -AsPlainText -Force
if (-not (Get-LocalUser -Name "kiosk" -ErrorAction SilentlyContinue)) {
    New-LocalUser -Name "kiosk" -Password $password -FullName "Kiosk User" -Description "Medicine AI Kiosk User"
    Add-LocalGroupMember -Group "Users" -Member "kiosk"
}
Write-Success "Kiosk user created"

# Configure auto-login (optional - requires registry changes)
Write-Log "Configuring auto-login..."
$regPath = "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon"
Set-ItemProperty -Path $regPath -Name "AutoAdminLogon" -Value "1" -Type String
Set-ItemProperty -Path $regPath -Name "DefaultUserName" -Value "kiosk" -Type String
Set-ItemProperty -Path $regPath -Name "DefaultPassword" -Value "Kiosk@123!" -Type String
Write-Success "Auto-login configured"

# Create desktop shortcut
Write-Log "Creating desktop shortcut..."
$WshShell = New-Object -comObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut("$([Environment]::GetFolderPath('Desktop'))\Medicine AI Kiosk.lnk")
$Shortcut.TargetPath = "$InstallDir\start-kiosk.bat"
$Shortcut.WorkingDirectory = $InstallDir
$Shortcut.Description = "Start Medicine AI Kiosk"
$Shortcut.Save()
Write-Success "Desktop shortcut created"

# Re-enable Windows Defender
Set-MpPreference -DisableRealtimeMonitoring $false

Write-Success "Setup complete!"
Write-Host ""
Write-Host "Kiosk Details:" -ForegroundColor $Blue
Write-Host "  Name: $KioskName"
Write-Host "  Location: $KioskLocation"
Write-Host "  Serial Number: $SerialNumber"
Write-Host "  API URL: $ApiBaseUrl"
Write-Host "  Install Directory: $InstallDir"
Write-Host ""
Write-Warning "Please restart the computer to complete the kiosk setup."
Write-Warning "The kiosk service will start automatically after restart."
Write-Host ""
Write-Host "To start manually, run: $InstallDir\start-kiosk.bat"
Write-Host "To manage the service, use: services.msc"
