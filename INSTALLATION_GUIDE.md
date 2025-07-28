# MedCura AI Landing Page Features - Installation Guide

## Prerequisites
- Laravel application already set up
- PHP 8.2+
- MySQL/PostgreSQL database
- Composer and NPM installed

## Step 1: Run Database Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `blog_posts` - For doctor blog posts
- `chat_sessions` - For chat session management
- `chat_messages` - For individual chat messages
- `landing_page_visits` - For visit tracking
- Updates to `reviews` table for testimonials
- Updates to `doctors` table for verification

## Step 2: Register Service Provider

Add the service provider to `config/app.php`:

```php
'providers' => [
    // ... existing providers
    App\Providers\LandingPageServiceProvider::class,
],
```

## Step 3: Register Middleware

Add the visit tracking middleware to `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\TrackLandingPageVisit::class,
    ],
];
```

## Step 4: Create Storage Link

If not already done, create the storage link for file uploads:

```bash
php artisan storage:link
```

## Step 5: Install Frontend Dependencies

If you need to install Chart.js for analytics:

```bash
npm install chart.js
npm run build
```

## Step 6: Configure File Permissions

Ensure the storage directory is writable:

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Step 7: Seed Sample Data (Optional)

You can create a seeder to add sample blog posts and testimonials:

```bash
php artisan make:seeder LandingPageFeaturesSeeder
php artisan db:seed --class=LandingPageFeaturesSeeder
```

## Step 8: Test the Features

### For Doctors:
1. Login as a doctor
2. Visit `/doctor/blog` to manage blog posts
3. Visit `/doctor/chat` to view chat messages
4. Visit `/doctor/testimonials` to manage testimonials
5. Visit `/doctor/analytics` to view analytics

### For Visitors:
1. Visit `/doctor/{username}` to see the enhanced landing page
2. Click the chat widget to test the chat functionality
3. View blog posts at `/doctor/{username}/blog/{slug}`

## Features Overview

### ✅ Blog Management
- Create, edit, publish/unpublish blog posts
- SEO-friendly URLs and meta tags
- Featured image upload
- Reading time calculation
- View tracking

### ✅ Live Chat Widget
- jQuery-based expandable chat interface
- Intelligent bot responses
- Visitor information collection
- Chat history management
- Mobile-responsive design

### ✅ Testimonials Management
- Mark reviews as public/private
- Add case studies to testimonials
- Patient privacy protection (initials only)
- Verified badges

### ✅ Verification Badge
- "Verified by MedCura AI" badge
- Conditional display based on doctor verification status
- Professional styling

### ✅ Visit Tracking
- Comprehensive visitor analytics
- Device, browser, and OS detection
- Referrer tracking
- Daily visit statistics
- Analytics dashboard

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Doctor/
│   │   │   ├── BlogController.php
│   │   │   ├── ChatController.php
│   │   │   ├── TestimonialController.php
│   │   │   └── AnalyticsController.php
│   │   └── PublicChatController.php
│   ├── Middleware/
│   │   └── TrackLandingPageVisit.php
│   └── Policies/
│       └── BlogPostPolicy.php
├── Models/
│   ├── BlogPost.php
│   ├── ChatSession.php
│   ├── ChatMessage.php
│   └── LandingPageVisit.php
└── Providers/
    └── LandingPageServiceProvider.php

resources/views/
├── components/
│   └── chat-widget.blade.php
├── doctor/
│   ├── analytics/
│   │   └── index.blade.php
│   ├── blog/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── chat/
│   │   └── index.blade.php
│   ├── landing-page/
│   │   ├── blog-post.blade.php
│   │   └── templates/
│   │       └── template1.blade.php (updated)
│   └── testimonials/
       └── index.blade.php

database/migrations/
├── 2024_01_15_000001_create_blog_posts_table.php
├── 2024_01_15_000002_create_chat_sessions_table.php
├── 2024_01_15_000003_create_chat_messages_table.php
├── 2024_01_15_000004_create_landing_page_visits_table.php
├── 2024_01_15_000005_add_testimonial_fields_to_reviews_table.php
└── 2024_01_15_000006_add_is_verified_to_doctors_table.php
```

## Routes Added

```php
// Public routes
Route::get('/doctor/{username}', [PublicLandingPageController::class, 'show']);
Route::get('/doctor/{username}/blog/{slug}', [PublicLandingPageController::class, 'showBlogPost']);
Route::post('/doctor/{username}/chat/init', [PublicChatController::class, 'initializeChat']);
Route::post('/doctor/{username}/chat/send', [PublicChatController::class, 'sendMessage']);

// Doctor routes (auth + doctor middleware)
Route::resource('doctor/blog', BlogController::class);
Route::get('doctor/chat', [ChatController::class, 'index']);
Route::get('doctor/testimonials', [TestimonialController::class, 'index']);
Route::get('doctor/analytics', [AnalyticsController::class, 'index']);
```

## Security Features

- CSRF protection on all forms
- Authorization policies for blog posts
- Input validation and sanitization
- File upload security
- XSS protection in chat messages

## Performance Considerations

- Database indexes on frequently queried columns
- Lazy loading of relationships
- Efficient query optimization
- Image optimization ready
- Caching-friendly structure

## Troubleshooting

### Common Issues:

1. **Storage link not working**: Run `php artisan storage:link`
2. **Migrations failing**: Check database connection and permissions
3. **Chat widget not appearing**: Ensure jQuery is loaded before the widget
4. **Images not displaying**: Check storage permissions and public disk configuration
5. **Routes not working**: Clear route cache with `php artisan route:clear`

### Debug Commands:

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list | grep doctor

# Check migrations
php artisan migrate:status
```

## Next Steps

1. **Customize Styling**: Modify the CSS in templates to match your brand
2. **Add Real-time Chat**: Integrate with WebSockets for real-time messaging
3. **Email Notifications**: Set up notifications for new chat messages
4. **Advanced Analytics**: Add more detailed visitor analytics
5. **SEO Optimization**: Add structured data and improve meta tags
6. **Social Media Integration**: Add social sharing and login options

## Support

For issues or questions about this implementation, please refer to the Laravel documentation or create an issue in your project repository.
