<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\AvailabilityController;
use App\Http\Controllers\Auth\PatientRegistrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Doctor\GoogleController;
use App\Http\Controllers\Doctor\LandingPageController;
use App\Http\Controllers\PublicLandingPageController;
use App\Http\Controllers\Doctor\BlogController;
use App\Http\Controllers\Doctor\ChatController;
use App\Http\Controllers\Doctor\TestimonialController;
use App\Http\Controllers\Doctor\AnalyticsController;
use App\Http\Controllers\PublicChatController;
use App\Http\Controllers\Admin\MonthlyInvoiceController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $showPricingSection = SystemSetting::get('show_pricing_section', true);
    return view('main', compact('showPricingSection'));
});

// Patient registration routes
Route::get('/register/patient', [PatientRegistrationController::class, 'create'])->name('patient.register');
Route::post('/register/patient', [PatientRegistrationController::class, 'store'])->name('patient.register');

// Public doctor routes
Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
Route::get('/doctors/search', [DoctorController::class, 'search'])->name('doctors.search');
Route::get('/doctors/{doctor}/slots', [DoctorController::class, 'getAvailableSlots'])->name('doctors.slots');
Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
Route::get('/doctors/{doctor}/reviews', [ReviewController::class, 'doctorReviews'])->name('doctors.reviews');
Route::get('/doctors/{doctor}/reviews/ajax', [ReviewController::class, 'getDoctorReviews'])->name('doctors.reviews.ajax');

// Public appointment booking (for guests)
Route::get('/appointments/{doctor}/create', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');

// Guest appointment management
Route::prefix('appointments/guest')->name('appointments.guest.')->group(function () {
    Route::get('/lookup', [AppointmentController::class, 'guestLookup'])->name('lookup');
    Route::post('/search', [AppointmentController::class, 'guestSearch'])->name('search');
    Route::get('/{appointment}', [AppointmentController::class, 'guestShow'])->name('show');
    Route::post('/{appointment}/verify', [AppointmentController::class, 'guestVerify'])->name('verify');
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'guestCancel'])->name('cancel');
});

// Guest review management
Route::prefix('reviews/guest')->name('reviews.guest.')->group(function () {
    Route::get('/{appointment}/create', [ReviewController::class, 'guestCreate'])->name('create');
    Route::post('/store', [ReviewController::class, 'guestStore'])->name('store');
    Route::get('/{review}/verify', [ReviewController::class, 'guestVerify'])->name('verify');
    Route::post('/{review}/verify', [ReviewController::class, 'guestVerifyToken'])->name('verify.token');
    Route::get('/{appointment}/show', [ReviewController::class, 'guestShow'])->name('show');
});

Route::middleware('auth')->group(function () {
    Route::get('/ask-ai', [OpenAIController::class, 'showForm'])->name('ask-ai');
    Route::post('/openai/respond', [OpenAIController::class, 'getResponse'])->name('openai.respond');
    Route::post('/openai/follow-up', [OpenAIController::class, 'followUp'])->name('openai.follow-up');
    Route::post('/patient/summary', [OpenAIController::class, 'generatePatientSummary'])->name('patient.summary');

    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings');
    Route::put('/user/settings/update', [UserSettingsController::class, 'update'])->name('settings.update');
    Route::get('/cases', [OpenAIController::class, 'getCases'])->name('cases');
    Route::get('/dashboard', [OpenAIController::class, 'dashboard'])->name('dashboard');

    // Appointment routes for patients
    Route::resource('appointments', AppointmentController::class)->except(['edit', 'update', 'create', 'store']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments/calendar/events', [AppointmentController::class, 'getCalendarEvents'])->name('appointments.calendar.events');

    // Review routes for patients
    Route::resource('reviews', ReviewController::class);
    Route::get('/appointments/{appointment}/review', [ReviewController::class, 'create'])->name('appointments.review');

    // Subscription routes
    Route::get('/pricing', [SubscriptionController::class, 'pricing'])->name('subscription.pricing');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
        ->middleware('stripe.configured')
        ->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
    Route::get('/subscription/portal', [SubscriptionController::class, 'customerPortal'])->name('subscription.portal');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->middleware('stripe.configured')
        ->name('subscription.cancel');

    // Invoice routes for doctors
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::get('/invoices/{invoice}/manual-payment', [InvoiceController::class, 'manualPayment'])->name('invoices.manual-payment');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/sync', [InvoiceController::class, 'sync'])->name('invoices.sync');

    // Debug route for testing payment redirects
    Route::get('/debug/payment/{invoice}', function($invoiceId) {
        $invoice = \App\Models\StripeInvoice::findOrFail($invoiceId);
        $service = new \App\Services\StripeInvoiceService();
        $paymentUrl = $service->getPaymentUrl($invoice);

        return response()->json([
            'invoice_id' => $invoice->id,
            'payment_url' => $paymentUrl,
            'is_stripe' => strpos($paymentUrl, 'stripe.com') !== false,
            'url_length' => strlen($paymentUrl)
        ]);
    })->name('debug.payment');

    // Test payment page
    Route::get('/test-payment', function() {
        $invoices = \App\Models\StripeInvoice::where('status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('test-payment', compact('invoices'));
    })->name('test.payment');

    // Test grace period notification
    Route::get('/test-grace-period', function() {
        $user = auth()->user();
        $setting = $user->monthlyInvoiceSetting;

        if (!$setting) {
            return response()->json([
                'error' => 'No monthly invoice setting found for user',
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'subscription' => [
                'starts_at' => $setting->subscription_starts_at?->format('Y-m-d H:i:s'),
                'ends_at' => $setting->subscription_ends_at?->format('Y-m-d H:i:s'),
                'period_months' => $setting->subscription_period_months,
                'grace_period_days' => $setting->grace_period_days,
                'warning_period_days' => $setting->warning_period_days,
                'is_restricted' => $setting->is_restricted,
                'is_active' => $setting->is_active,
            ],
            'status_checks' => [
                'is_subscription_expired' => $setting->isSubscriptionExpired(),
                'is_in_grace_period' => $user->isInGracePeriod(),
                'is_in_warning_period' => $user->isInWarningPeriod(),
                'is_restricted' => $user->isRestricted(),
                'subscription_status' => $user->getSubscriptionStatus(),
                'days_remaining' => $user->getDaysRemainingInCurrentPeriod(),
            ],
            'notification_data' => [
                'should_show_grace_notification' => $user->isInGracePeriod(),
                'should_show_warning_notification' => $user->isInWarningPeriod(),
                'should_show_restriction_notification' => $user->isRestricted(),
                'subscription_end_formatted' => $user->getSubscriptionEndDate()?->format('M d, Y'),
            ]
        ]);
    })->name('test.grace-period');

    // Access restriction routes
    Route::get('/access/restricted', [App\Http\Controllers\AccessRestrictionController::class, 'restricted'])->name('access.restricted');
    Route::get('/access/check-status', [App\Http\Controllers\AccessRestrictionController::class, 'checkStatus'])->name('access.check-status');
});



Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Contact submission routes moved to admin middleware group below
Route::get('/about', [UserSettingsController::class, 'about'])->name('about');



// Doctor routes
Route::middleware(['auth', 'doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    // Redirect doctor dashboard to main dashboard
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard');
    })->name('dashboard');

    // Appointment management
    Route::get('/appointments', [DoctorDashboardController::class, 'appointments'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [DoctorDashboardController::class, 'showAppointment'])->name('appointments.show');
    Route::post('/appointments/{appointment}/confirm', [DoctorDashboardController::class, 'confirmAppointment'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/cancel', [DoctorDashboardController::class, 'cancelAppointment'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/complete', [DoctorDashboardController::class, 'completeAppointment'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/no-show', [DoctorDashboardController::class, 'markNoShow'])->name('appointments.no-show');
    Route::get('/appointments/calendar/events', [DoctorDashboardController::class, 'getCalendarEvents'])->name('appointments.calendar.events');

    // Availability management
    Route::resource('availability', AvailabilityController::class);
    Route::post('/availability/{availabilitySlot}/toggle', [AvailabilityController::class, 'toggle'])->name('availability.toggle');
    Route::post('/availability/bulk', [AvailabilityController::class, 'bulkStore'])->name('availability.bulk');

    // Reviews
    Route::get('/reviews', [DoctorDashboardController::class, 'reviews'])->name('reviews.index');

    // Profile management
    Route::get('/profile', [DoctorDashboardController::class, 'profile'])->name('profile.edit');
    Route::patch('/profile', [DoctorDashboardController::class, 'updateProfile'])->name('profile.update');

    // Appointment Settings
    Route::get('/settings/appointments', [App\Http\Controllers\Doctor\AppointmentSettingsController::class, 'index'])->name('settings.appointments');
    Route::put('/settings/appointments', [App\Http\Controllers\Doctor\AppointmentSettingsController::class, 'updateAppointmentTypes'])->name('settings.appointments.update');

    // Google integration
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/redirect', [GoogleController::class, 'redirectToGoogle'])->name('redirect');
        Route::get('/callback', [GoogleController::class, 'handleGoogleCallback'])->name('callback');
        Route::post('/disconnect', [GoogleController::class, 'disconnectGoogle'])->name('disconnect');
        Route::get('/accounts', [GoogleController::class, 'getAccounts'])->name('accounts');
        Route::get('/locations', [GoogleController::class, 'getLocations'])->name('locations');
        Route::post('/account-location', [GoogleController::class, 'setAccountLocation'])->name('account-location');
    });

    // Landing Page Management
    Route::prefix('landing-page')->name('landing-page.')->group(function () {
        Route::get('/', [LandingPageController::class, 'index'])->name('index');
        Route::post('/update', [LandingPageController::class, 'update'])->name('update');
        Route::post('/upload-hero-image', [LandingPageController::class, 'uploadHeroImage'])->name('upload-hero-image');
        Route::post('/toggle-publish', [LandingPageController::class, 'togglePublish'])->name('toggle-publish');
        Route::get('/preview/{username}', [LandingPageController::class, 'preview'])->name('preview');
    });

    // Blog Management
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/create', [BlogController::class, 'create'])->name('blog.create');
    Route::post('/blog', [BlogController::class, 'store'])->name('blog.store');
    Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/blog/{post}/edit', [BlogController::class, 'edit'])->name('blog.edit');
    Route::put('/blog/{post}', [BlogController::class, 'update'])->name('blog.update');
    Route::delete('/blog/{post}', [BlogController::class, 'destroy'])->name('blog.destroy');
    Route::post('/blog/{post}/toggle-publish', [BlogController::class, 'togglePublish'])->name('blog.toggle-publish');

    // Chat Management
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/{sessionId}', [ChatController::class, 'show'])->name('show');
        Route::post('/{sessionId}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::get('/unread/count', [ChatController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/mark-all-read', [ChatController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // Testimonials Management
    Route::prefix('testimonials')->name('testimonials.')->group(function () {
        Route::get('/', [TestimonialController::class, 'index'])->name('index');
        Route::post('/{review}/toggle-public', [TestimonialController::class, 'togglePublic'])->name('toggle-public');
        Route::post('/{review}/case-study', [TestimonialController::class, 'updateCaseStudy'])->name('case-study');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/data', [AnalyticsController::class, 'getData'])->name('data');
    });
});

// Public Doctor Landing Pages (must be after doctor middleware group to avoid conflicts)
Route::get('/doctor/{username}', [PublicLandingPageController::class, 'show'])->name('doctor.landing');
Route::get('/doctor/{username}/blogs', [PublicLandingPageController::class, 'showBlogs'])->name('doctor.blogs');
Route::get('/doctor/{username}/blog/{slug}', [PublicLandingPageController::class, 'showBlogPost'])->name('doctor.blog.post');

// Public Chat Routes
Route::post('/doctor/{username}/chat/init', [PublicChatController::class, 'initializeChat'])->name('doctor.chat.init');
Route::post('/doctor/{username}/chat/send', [PublicChatController::class, 'sendMessage'])->name('doctor.chat.send');
Route::get('/doctor/{username}/chat/history', [PublicChatController::class, 'getChatHistory'])->name('doctor.chat.history');

// Public Testimonials API
Route::get('/doctor/{username}/testimonials', [TestimonialController::class, 'getPublicTestimonials'])->name('doctor.testimonials.public');

// Stripe webhook (outside auth middleware)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');










// Admin authentication routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin routes
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', AdminController::class);
    Route::get('/users/{user}/patient-analyses', [AdminController::class, 'userPatientAnalyses'])->name('users.patient-analyses');
    Route::post('/users/{user}/toggle-doctor-status', [AdminController::class, 'toggleDoctorStatus'])->name('users.toggle-doctor-status');

    // Billing and subscription management
    Route::get('/billing', [AdminController::class, 'billing'])->name('billing');
    Route::get('/billing/export', [AdminController::class, 'exportBilling'])->name('billing.export');
    Route::get('/usage-analytics', [AdminController::class, 'usageAnalytics'])->name('usage-analytics');

    // System settings
    Route::get('/system-settings', [AdminController::class, 'systemSettings'])->name('system-settings');
    Route::post('/system-settings', [AdminController::class, 'updateSystemSettings'])->name('system-settings.update');

    // Invoice management for admin
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [AdminInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [AdminInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/mark-paid', [AdminInvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/void', [AdminInvoiceController::class, 'void'])->name('invoices.void');
    Route::get('/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('/invoices/generate-monthly', [AdminInvoiceController::class, 'generateMonthlyInvoices'])->name('invoices.generate-monthly');
    Route::get('/invoices/export', [AdminInvoiceController::class, 'export'])->name('invoices.export');

    // Monthly invoice management
    Route::get('/monthly-invoices', [MonthlyInvoiceController::class, 'index'])->name('monthly-invoices.index');
    Route::get('/monthly-invoices/{user}/edit', [MonthlyInvoiceController::class, 'edit'])->name('monthly-invoices.edit');
    Route::put('/monthly-invoices/{user}', [MonthlyInvoiceController::class, 'update'])->name('monthly-invoices.update');
    Route::post('/monthly-invoices/{user}/restrict', [MonthlyInvoiceController::class, 'restrict'])->name('monthly-invoices.restrict');
    Route::post('/monthly-invoices/{user}/unrestrict', [MonthlyInvoiceController::class, 'unrestrict'])->name('monthly-invoices.unrestrict');
    Route::post('/monthly-invoices/process-overdue', [MonthlyInvoiceController::class, 'processOverdue'])->name('monthly-invoices.process-overdue');
    Route::post('/monthly-invoices/process-payments', [MonthlyInvoiceController::class, 'processPayments'])->name('monthly-invoices.process-payments');
    Route::post('/monthly-invoices/bulk-update', [MonthlyInvoiceController::class, 'bulkUpdate'])->name('monthly-invoices.bulk-update');
    Route::post('/monthly-invoices/generate', [MonthlyInvoiceController::class, 'generate'])->name('monthly-invoices.generate');

    // Contact submission management
    Route::get('/contact-submissions', [ContactController::class, 'adminIndex'])->name('contact-submissions');
    Route::patch('/contact-submissions/{submission}/mark-read', [ContactController::class, 'markAsRead'])->name('contact-submissions.mark-read');

    // Manual reminder routes
    Route::post('/send-reminders', [AdminController::class, 'sendManualReminders'])->name('send-reminders');
    Route::get('/send-reminders', [AdminController::class, 'showSendRemindersForm'])->name('send-reminders.form');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

