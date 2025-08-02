# Doctor Landing Page System

This document explains how to use the Doctor Landing Page System in MedCuraAI.

## Overview

The Doctor Landing Page System allows doctors to create personalized, professional landing pages that can be accessed via:
- Standard URL: `medcuraai.com/doctor/{username}`
- Subdomain: `{username}.medcuraai.com` (if enabled)
- Custom Domain: `yourdomain.com` (via CNAME setup)

## Features

### For Doctors
- **Landing Page Management**: Create and customize professional landing pages
- **Template Selection**: Choose from 2 responsive Bootstrap templates
- **Color Customization**: Customize colors for branding
- **Section Control**: Show/hide different sections (hero, about, appointments, reviews, contact)
- **Hero Image Upload**: Upload custom hero images
- **Real-time Preview**: See changes instantly
- **Domain Options**: Use subdomain or connect custom domain
- **Publish Control**: Publish/unpublish landing pages

### For Patients
- **Professional Presentation**: View doctor information in a clean, professional layout
- **Direct Booking**: Book appointments directly from the landing page
- **Reviews**: Read and submit reviews (if enabled by doctor)
- **Contact Information**: Easy access to doctor's contact details
- **Responsive Design**: Works on all devices

## Getting Started

### 1. Database Setup

Run the migration to create the required table:
```bash
php artisan migrate
```

### 2. Create Sample Data (Optional)

Run the seeder to create a sample doctor with a landing page:
```bash
php artisan db:seed --class=DoctorLandingPageSeeder
```

This creates:
- Doctor login: `dr.smith@example.com` / `password`
- Landing page URL: `/doctor/drjohnsmith`

### 3. Environment Configuration

Add to your `.env` file:
```env
APP_DOMAIN=medcuraai.com
```

## Usage Guide

### For Doctors

1. **Access Landing Page Management**
   - Login to your doctor account
   - Navigate to "Landing Page" from the dashboard or main navigation

2. **Customize Your Landing Page**
   - **Basic Settings**: Set username, template, page title, description, tagline, and about text
   - **Design Settings**: Customize colors for branding
   - **Section Visibility**: Toggle which sections appear on your page
   - **Domain Settings**: Enable subdomain or set up custom domain

3. **Upload Hero Image**
   - Click on the hero image upload field
   - Select an image (recommended: 1200x600px, max 2MB)
   - Image will be automatically uploaded and preview updated

4. **Preview and Publish**
   - Use the live preview panel to see changes in real-time
   - Test different device views (desktop, tablet, mobile)
   - Click "Publish" when ready to make your page live

### Domain Setup

#### Subdomain Setup
1. Enable "Enable Subdomain" in the Domain Settings tab
2. Your page will be accessible at `{username}.medcuraai.com`

#### Custom Domain Setup
1. Go to your domain registrar's DNS settings
2. Add a CNAME record pointing your domain to `medcuraai.com`
3. Enter your domain in the "Custom Domain" field
4. Save changes
5. Wait up to 24 hours for DNS propagation

## Templates

### Template 1: Modern Professional
- Gradient hero section with overlay
- Card-based layout
- Professional color scheme
- Suitable for established practices

### Template 2: Clean Minimal
- Clean, minimal design
- Rounded elements
- Soft color palette
- Great for modern, approachable doctors

## Technical Details

### Database Schema
The `doctor_landing_pages` table stores:
- Basic information (username, template, titles)
- JSON columns for flexible data (colors, section visibility)
- Domain settings (subdomain, custom domain)
- Publishing status

### File Structure
```
app/
├── Http/Controllers/
│   ├── Doctor/LandingPageController.php
│   └── PublicLandingPageController.php
├── Http/Middleware/HandleDoctorDomains.php
└── Models/DoctorLandingPage.php

resources/views/doctor/landing-page/
├── index.blade.php
└── templates/
    ├── template1.blade.php
    └── template2.blade.php

database/
├── migrations/create_doctor_landing_pages_table.php
└── seeders/DoctorLandingPageSeeder.php
```

### Routes
- **Public**: `/doctor/{username}` - Public landing page
- **Doctor Admin**: `/doctor/landing-page/*` - Management interface
- **Preview**: `/doctor/landing-page/preview/{username}` - Preview mode

### Middleware
- `HandleDoctorDomains`: Handles subdomain and custom domain routing
- Applied to all web routes to intercept domain-based requests

## Integration with Existing Systems

### Appointments
- Reuses existing appointment booking system
- Integrates with doctor availability
- Maintains all existing appointment logic

### Reviews
- Displays existing reviews from the database
- Allows new review submissions (if enabled)
- Maintains review verification system

### Doctor Profiles
- Pulls data from existing doctor profiles
- Uses profile images, contact info, specialties
- Maintains data consistency

## Customization

### Adding New Templates
1. Create a new Blade template in `resources/views/doctor/landing-page/templates/`
2. Follow the existing template structure
3. Add the template option to the dropdown in the management interface

### Adding New Color Options
1. Update the color inputs in `index.blade.php`
2. Add corresponding CSS variables in templates
3. Update the default colors in the reset function

### Adding New Sections
1. Add section visibility option in the management interface
2. Create the section in both templates
3. Update the section visibility logic

## Troubleshooting

### Landing Page Not Loading
- Check if the landing page is published
- Verify the username is correct
- Ensure the doctor profile is complete

### Custom Domain Not Working
- Verify CNAME record is set correctly
- Wait for DNS propagation (up to 24 hours)
- Check domain spelling in settings

### Images Not Uploading
- Check file size (max 2MB)
- Verify file format (jpg, png, gif)
- Ensure storage permissions are correct

### Preview Not Updating
- Clear browser cache
- Check for JavaScript errors in console
- Verify CSRF token is valid

## Security Considerations

- All file uploads are validated for type and size
- CSRF protection on all forms
- Input sanitization for all user data
- Published status controls public access
- Domain validation prevents malicious redirects

## Performance

- Images are optimized and cached
- Templates use CDN resources (Bootstrap, jQuery)
- Minimal database queries with eager loading
- Responsive images for different screen sizes

## SEO Features

- Proper meta tags (title, description)
- Open Graph tags for social sharing
- Structured data for search engines
- Clean, semantic HTML structure
- Mobile-friendly responsive design
