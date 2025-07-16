---
description: Repository Information Overview
alwaysApply: true
---

# Medical Assistant Application Information

## Summary
This is a Laravel-based web application designed for medical professionals. It integrates with OpenAI's API to provide AI-assisted medical analysis, patient data management, and clinical decision support. The application includes user authentication, admin functionality, and a dashboard for managing patient cases.

## Structure
- **app/**: Core application code (Controllers, Models, Services)
- **routes/**: Application routes definition
- **resources/**: Frontend assets (views, CSS, JavaScript)
- **database/**: Database migrations and seeders
- **public/**: Publicly accessible files and assets
- **tests/**: Application test suite
- **config/**: Configuration files

## Language & Runtime
**Language**: PHP
**Version**: ^8.2
**Framework**: Laravel ^12.0
**Build System**: Composer
**Frontend**: JavaScript with Vite, TailwindCSS

## Dependencies
**Main Dependencies**:
- laravel/framework: ^12.0
- laravel/tinker: ^2.10.1
- openai-php/laravel: ^0.11.0
- smalot/pdfparser: ^2.12

**Development Dependencies**:
- laravel/breeze: Authentication scaffolding
- pestphp/pest: ^3.8 (Testing framework)
- vite: ^6.2.4 (Frontend build tool)
- tailwindcss: ^3.1.0
- alpinejs: ^3.4.2

## Build & Installation
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate

# Optional: Seed the database
php artisan db:seed

# Build frontend assets
npm run build
```

## Key Features
**OpenAI Integration**:
- Uses OpenAI API for medical analysis
- Supports file uploads for analysis
- Implements thread-based conversations

**User Management**:
- Authentication system with Laravel Breeze
- Admin and regular user roles
- User profile management

**Patient Management**:
- Patient data storage and retrieval
- Visit tracking with sequential numbering
- Symptom analysis and recording

## Testing
**Framework**: PestPHP
**Test Location**: tests/Feature and tests/Unit
**Configuration**: phpunit.xml
**Run Command**:
```bash
php artisan test
# or
composer test
```

## Development Workflow
```bash
# Start development server with hot reloading
composer dev
# This runs: Laravel server, queue worker, logs, and Vite
```

## Database
**Configuration**: SQLite for testing, configurable for production
**Migrations**: Located in database/migrations
**Models**: User, Setting, Symptom, PatientAnalysis
