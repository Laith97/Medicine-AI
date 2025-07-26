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
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/sync', [InvoiceController::class, 'sync'])->name('invoices.sync');
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

    // Google integration
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/redirect', [GoogleController::class, 'redirectToGoogle'])->name('redirect');
        Route::get('/callback', [GoogleController::class, 'handleGoogleCallback'])->name('callback');
        Route::post('/disconnect', [GoogleController::class, 'disconnectGoogle'])->name('disconnect');
        Route::get('/accounts', [GoogleController::class, 'getAccounts'])->name('accounts');
        Route::get('/locations', [GoogleController::class, 'getLocations'])->name('locations');
        Route::post('/account-location', [GoogleController::class, 'setAccountLocation'])->name('account-location');
    });
});
// Stripe webhook (outside auth middleware)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');

// Admin authentication routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin routes
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', AdminController::class);
    Route::get('/users/{user}/patient-analyses', [AdminController::class, 'userPatientAnalyses'])->name('users.patient-analyses');

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

    // Contact submission management
    Route::get('/contact-submissions', [ContactController::class, 'adminIndex'])->name('contact-submissions');
    Route::patch('/contact-submissions/{submission}/mark-read', [ContactController::class, 'markAsRead'])->name('contact-submissions.mark-read');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

