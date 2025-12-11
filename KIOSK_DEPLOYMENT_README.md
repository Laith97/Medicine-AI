# Medicine-AI Kiosk System - Management and Deployment

This document provides comprehensive instructions for implementing Phase 5: Management and Deployment features for the Medicine-AI Kiosk System.

## Overview

The kiosk system provides self-service check-in, payment processing, and appointment management capabilities for healthcare facilities. This phase implements comprehensive management and deployment features.

## Features Implemented

### 1. Admin Interface for Kiosk Monitoring
- **Real-time dashboard** with live statistics
- **Individual kiosk details** with session history and performance metrics
- **Online/offline status monitoring** with automatic alerts
- **Remote command execution** (restart, shutdown, update, diagnostics)

### 2. Remote Update Mechanism
- **Software version management** with automatic update checks
- **Remote command system** for kiosk control
- **Update status reporting** and progress tracking
- **Configuration management** via API

### 3. Kiosk Registration and Configuration
- **Automatic registration** during initial setup
- **Hardware capability detection** and configuration
- **Security settings** and access control
- **Location and naming management**

### 4. Deployment Automation
- **Cross-platform deployment scripts** (Linux/Windows)
- **Automated setup** with dependency installation
- **Service configuration** for reliability
- **Kiosk mode configuration** for security

## Directory Structure

```
Medicine-AI/
├── app/
│   ├── Http/Controllers/Admin/KioskController.php    # Admin management interface
│   ├── Http/Controllers/Api/KioskController.php      # API endpoints for kiosk operations
│   └── Models/Kiosk.php                              # Kiosk model
├── config/kiosk.php                                  # Kiosk configuration
├── resources/views/admin/kiosks/                     # Admin interface views
├── scripts/
│   ├── kiosk-setup.sh                                # Linux deployment script
│   └── kiosk-setup-windows.ps1                       # Windows deployment script
├── routes/
│   ├── web.php                                        # Admin routes
│   └── api.php                                        # API routes
└── storage/app/kiosk/updates/                         # Software update files
```

## Installation and Setup

### Prerequisites

#### For Linux Kiosks:
- Ubuntu/Debian-based distribution
- Root or sudo access
- Internet connection

#### For Windows Kiosks:
- Windows 10/11 Pro or Enterprise
- Administrator privileges
- PowerShell execution enabled

### Linux Deployment

1. **Download the setup script:**
   ```bash
   wget https://your-server.com/scripts/kiosk-setup.sh
   chmod +x kiosk-setup.sh
   ```

2. **Run the setup script:**
   ```bash
   sudo ./kiosk-setup.sh "Main Lobby Kiosk" "Hospital Lobby, Floor 1" "KIOSK-001" "https://your-server.com/api"
   ```

3. **Reboot the system:**
   ```bash
   sudo reboot
   ```

The kiosk will automatically:
- Install required software (Chromium, Node.js, etc.)
- Configure kiosk mode
- Register with the server
- Start the kiosk application

### Windows Deployment

1. **Download the setup script:**
   ```powershell
   Invoke-WebRequest -Uri "https://your-server.com/scripts/kiosk-setup-windows.ps1" -OutFile "kiosk-setup.ps1"
   ```

2. **Run the setup script as Administrator:**
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine
   .\kiosk-setup-windows.ps1 -KioskName "Main Lobby Kiosk" -KioskLocation "Hospital Lobby, Floor 1" -SerialNumber "KIOSK-001" -ApiBaseUrl "https://your-server.com/api"
   ```

3. **Reboot the system:**
   ```powershell
   Restart-Computer
   ```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Kiosk Configuration
KIOSK_SOFTWARE_VERSION=1.0.0
KIOSK_AUTO_UPDATE=true
KIOSK_UPDATE_CHECK_INTERVAL=60
KIOSK_FORCE_UPDATE=false
KIOSK_API_BASE_URL=https://your-server.com/api
KIOSK_SESSION_TIMEOUT=30
KIOSK_RATE_LIMIT_REQUESTS=100
KIOSK_MAX_FAILED_ATTEMPTS=5

# Hardware Defaults
KIOSK_DEFAULT_RESOLUTION=1920x1080
KIOSK_TOUCH_ENABLED=true
KIOSK_VOICE_ASSISTANT=true
KIOSK_HIGH_CONTRAST=false
```

### Kiosk Configuration File

The `config/kiosk.php` file contains all kiosk-related settings. Key sections:

- **software_version**: Current version and update settings
- **hardware**: Default hardware capabilities
- **security**: Session and rate limiting settings
- **monitoring**: Health check and alerting configuration
- **commands**: Allowed remote commands

## API Endpoints

### Management Endpoints (Admin Only)

```
GET    /api/kiosks/{kiosk}/status           # Get kiosk status
POST   /api/kiosks/{kiosk}/ping             # Update kiosk ping
PUT    /api/kiosks/{kiosk}/configuration    # Update configuration
POST   /api/kiosks/{kiosk}/command          # Send remote command
GET    /api/kiosks/{kiosk}/commands/pending # Get pending commands
POST   /api/kiosks/{kiosk}/commands/acknowledge # Acknowledge command
GET    /api/kiosks/{kiosk}/software/update  # Check for updates
GET    /api/kiosks/{kiosk}/software/download # Download update
POST   /api/kiosks/{kiosk}/software/status  # Report update status
```

### Kiosk Endpoints (Kiosk Access)

```
POST   /api/kiosk-sessions/start/{kiosk}     # Start session
POST   /api/kiosk-sessions/{session}/end     # End session
```

## Admin Interface

### Dashboard (`/admin/kiosks`)
- Real-time statistics (total, active, online kiosks)
- Today's session and revenue data
- Individual kiosk status and controls
- Quick actions (restart, update, diagnostics)

### Kiosk Details (`/admin/kiosks/{id}`)
- Comprehensive kiosk information
- Session history and statistics
- Configuration management
- Remote control panel

### Kiosk Management (`/admin/kiosks/{id}/edit`)
- Edit kiosk properties
- Configuration updates
- Status management

## Remote Commands

Supported commands that can be sent to kiosks:

- **restart**: Restart the kiosk application
- **shutdown**: Shutdown the kiosk (dangerous)
- **update**: Check for and install software updates
- **diagnostics**: Run system diagnostics
- **status**: Request status update

## Software Updates

### Preparing Updates

1. **Create update package:**
   ```bash
   # Linux
   tar -czf kiosk-update.tar.gz update-files/
   mv kiosk-update.tar.gz storage/app/kiosk/updates/

   # Windows
   Compress-Archive -Path "update-files" -DestinationPath "storage/app/kiosk/updates/kiosk-update.zip"
   ```

2. **Update version in config:**
   ```php
   // config/kiosk.php
   'software_version' => '1.1.0',
   ```

3. **Deploy update via admin interface**

### Update Process

1. Admin initiates update command
2. Kiosk downloads update package
3. Kiosk reports progress via API
4. Kiosk installs update and restarts
5. Kiosk reports successful update

## Monitoring and Alerts

### Real-time Monitoring
- Online/offline status (ping-based)
- Active session count
- Performance metrics
- Error reporting

### Automated Alerts
- Kiosk goes offline
- Failed update attempts
- Security events
- Performance issues

### Logging
- All kiosk activities logged
- Command execution tracked
- Update progress monitored
- Security events recorded

## Security Considerations

### Kiosk Isolation
- Session isolation middleware
- Rate limiting on API calls
- Command validation and authorization
- Secure update package verification

### Network Security
- HTTPS required for all communications
- API authentication and authorization
- Command encryption and validation
- Update package integrity checks

### Physical Security
- Kiosk mode prevents OS access
- Auto-logout after inactivity
- Emergency shutdown capabilities
- Tamper detection (future enhancement)

## Troubleshooting

### Common Issues

1. **Kiosk won't start:**
   - Check system logs: `journalctl -u medicine-ai-kiosk`
   - Verify dependencies are installed
   - Check network connectivity

2. **Registration fails:**
   - Verify API URL is correct
   - Check server connectivity
   - Review server logs for authentication issues

3. **Updates fail:**
   - Check update file exists in storage
   - Verify file permissions
   - Check kiosk has write access

4. **Commands not executing:**
   - Verify kiosk is online
   - Check command permissions
   - Review kiosk logs

### Logs and Debugging

- **Server logs:** `storage/logs/laravel.log`
- **Kiosk application logs:** Check kiosk application output
- **System logs:** `journalctl` (Linux) or Event Viewer (Windows)

## Future Enhancements

- **Bulk operations** for multiple kiosks
- **Advanced analytics** and reporting
- **Remote desktop** capabilities
- **Automated deployment** via USB/network
- **Integration with asset management** systems
- **Advanced security features** (biometrics, encryption)

## Support

For technical support or questions about kiosk deployment:

1. Check this documentation first
2. Review server and kiosk logs
3. Contact the development team
4. Create an issue in the project repository

---

**Version:** 1.0.0
**Last Updated:** November 2025
**Authors:** Medicine-AI Development Team
