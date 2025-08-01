# Diagnosis System Implementation

## Overview
This document outlines the comprehensive diagnosis system that has been implemented for the Medicine-AI application. The system allows doctors to create manual diagnoses for patients with both text and voice input, automatically creates patient accounts, sends notifications, and enables patient follow-up questions with AI assistance.

## Features Implemented

### 1. Manual Diagnosis Creation
- **Text Input**: Doctors can type diagnosis directly
- **Voice Input**: Doctors can record voice diagnoses using OpenAI Whisper for transcription
- **Combined Input**: Support for both text and voice input simultaneously
- **Patient Data**: Optional fields for symptoms, medical history, medications, and allergies

### 2. Automatic Patient Account Creation
- **Email-based Detection**: System checks if patient exists by email
- **Auto-registration**: Creates new patient accounts automatically if they don't exist
- **Temporary Password**: Generates 8-character alphanumeric passwords that don't expire
- **Role Assignment**: Automatically assigns 'patient' role to new accounts

### 3. Notification System
- **Email Notifications**: Comprehensive email with login credentials and diagnosis information
- **SMS Notifications**: Configurable SMS service with generic interface supporting Twilio, Nexmo, or log-only mode
- **Professional Templates**: Well-designed email templates with medical branding

### 4. Patient Follow-up System
- **AI-powered Q&A**: Patients can ask up to 5 follow-up questions per diagnosis
- **Context-aware Responses**: AI uses original diagnosis and patient data for relevant answers
- **Usage Tracking**: Tracks token usage and limits follow-up questions
- **Real-time Interface**: AJAX-powered interface for seamless question submission

### 5. Review System
- **Star Ratings**: 1-5 star rating system for diagnoses
- **Written Reviews**: Optional text reviews from patients
- **One-time Reviews**: Prevents duplicate reviews per diagnosis
- **Public Reviews**: Reviews are marked as public and linked to doctors

## Database Schema

### Diagnoses Table
- `id`: Primary key
- `doctor_id`: Foreign key to users table
- `patient_id`: Foreign key to users table
- `type`: Enum ('manual', 'ai')
- `diagnosis_text`: Main diagnosis content
- `voice_transcript`: Transcribed voice content
- `voice_file_path`: Path to original voice file
- `patient_data`: JSON field for additional patient information
- `ai_response`: AI analysis (for AI diagnoses)
- `follow_up_count`: Number of follow-up questions asked
- `patient_notified`: Boolean flag for notification status
- `patient_viewed_at`: Timestamp when patient viewed diagnosis
- `patient_reviewed`: Boolean flag for review completion

### Diagnosis Follow-ups Table
- `id`: Primary key
- `diagnosis_id`: Foreign key to diagnoses table
- `patient_id`: Foreign key to users table
- `question`: Patient's follow-up question
- `ai_response`: AI's response to the question
- `usage_data`: JSON field for OpenAI usage tracking

## Routes Structure

### Doctor Routes (Protected by 'doctor' middleware)
- `GET /diagnosis` - List all diagnoses created by doctor
- `GET /diagnosis/create` - Show diagnosis creation form
- `POST /diagnosis` - Store new diagnosis
- `GET /diagnosis/{diagnosis}` - Show diagnosis details

### Patient Routes (Protected by 'patient' middleware)
- `GET /diagnosis/my-diagnoses` - List patient's diagnoses
- `GET /diagnosis/{diagnosis}/view` - View specific diagnosis
- `POST /diagnosis/{diagnosis}/follow-up` - Submit follow-up question
- `POST /diagnosis/{diagnosis}/review` - Submit diagnosis review

## Controllers

### DiagnosisController
- **create()**: Shows diagnosis creation form
- **store()**: Processes diagnosis creation, handles voice transcription, creates patient accounts, sends notifications
- **show()**: Shows diagnosis details for doctors
- **patientView()**: Shows diagnosis for patients with follow-up interface
- **storeFollowUp()**: Handles AI-powered follow-up questions
- **storeReview()**: Processes patient reviews
- **index()**: Lists diagnoses for doctors
- **patientIndex()**: Lists diagnoses for patients

## Services

### SmsService
- **Generic Interface**: Supports multiple SMS providers
- **Provider Support**: Twilio, Nexmo, and log-only modes
- **Configuration**: Environment-based configuration
- **Password Generation**: Secure temporary password generation

## Models

### Diagnosis Model
- **Relationships**: Belongs to doctor and patient, has many follow-ups
- **Helper Methods**: 
  - `canAskFollowUp()`: Checks if patient can ask more questions
  - `markAsViewed()`: Marks diagnosis as viewed by patient
  - `markAsReviewed()`: Marks diagnosis as reviewed
  - `isAiDiagnosis()` / `isManualDiagnosis()`: Type checking

### DiagnosisFollowUp Model
- **Relationships**: Belongs to diagnosis and patient
- **Data Storage**: Stores questions, AI responses, and usage data

## Views

### Doctor Views
- **create.blade.php**: Comprehensive form with voice recording capabilities
- **index.blade.php**: Professional list view with status indicators
- **show.blade.php**: Detailed diagnosis view with patient activity tracking

### Patient Views
- **patient-index.blade.php**: User-friendly diagnosis list with preview
- **patient-view.blade.php**: Full diagnosis view with follow-up interface and review system

## Configuration

### SMS Configuration (config/sms.php)
```php
'default_provider' => env('SMS_PROVIDER', 'log'),
'providers' => [
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],
    'nexmo' => [
        'api_key' => env('NEXMO_API_KEY'),
        'api_secret' => env('NEXMO_API_SECRET'),
        'from_number' => env('NEXMO_FROM_NUMBER'),
    ],
]
```

### Environment Variables
```env
# SMS Configuration
SMS_PROVIDER=log
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_FROM_NUMBER=your_twilio_number
```

## Navigation Integration
- Added "Diagnoses" link to doctor navigation
- Added "My Diagnoses" link to patient navigation
- Both desktop and mobile navigation updated

## Security Features
- **Role-based Access**: Separate middleware for doctors and patients
- **Ownership Verification**: Users can only access their own diagnoses
- **CSRF Protection**: All forms protected with CSRF tokens
- **Input Validation**: Comprehensive validation for all inputs

## AI Integration
- **OpenAI Whisper**: For voice transcription
- **GPT-4**: For follow-up question responses
- **Context Awareness**: AI responses consider original diagnosis and patient data
- **Usage Tracking**: Monitors token usage for billing/limits

## File Storage
- **Voice Files**: Stored in private storage with secure access
- **File Validation**: Supports MP3, WAV, M4A, OGG formats up to 10MB

## Email Templates
- **Professional Design**: Medical-themed email templates
- **Responsive**: Mobile-friendly email layouts
- **Comprehensive Information**: Includes login credentials, diagnosis details, and instructions

## Future Enhancements
1. **Doctor-Patient Chat**: Direct messaging between doctors and patients
2. **Voice Playback**: Ability to play original voice recordings
3. **Diagnosis Templates**: Pre-defined diagnosis templates for common conditions
4. **Bulk Operations**: Batch diagnosis creation and management
5. **Analytics**: Diagnosis statistics and reporting
6. **Integration**: Connect with existing AI diagnosis system
7. **Appointment Integration**: Link diagnoses to appointments
8. **PDF Export**: Generate PDF reports of diagnoses

## Installation & Setup
1. Run migrations: `php artisan migrate`
2. Configure SMS provider in `.env` file
3. Ensure OpenAI API key is configured
4. Set up email configuration for notifications
5. Configure file storage permissions for voice files

## Testing
The system includes comprehensive validation and error handling:
- Form validation for all inputs
- File upload validation
- API error handling for OpenAI services
- Email/SMS delivery error handling
- Database transaction safety

This diagnosis system provides a complete solution for manual diagnosis creation, patient management, and follow-up care with modern web technologies and AI integration.
