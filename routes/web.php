<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\NotificationController;
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
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\AdminWaitlistController;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Broadcasting authentication route - handled by BroadcastServiceProvider
// DO NOT duplicate Broadcast::routes() here as it causes duplicate route registration

// Debug authentication routes (temporary)
if (config('app.debug')) {
    Route::middleware(['web'])->group(function () {
        Route::get('/debug-broadcasting-auth', function (\Illuminate\Http\Request $request) {
            $user = Auth::user();

            return response()->json([
                'authenticated' => Auth::check(),
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : null,
                'user_role' => $user ? $user->role : null,
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token(),
                'pusher_auth_key' => env('VITE_PUSHER_APP_KEY'),
                'expected_channel' => 'private-App.User.' . ($user ? $user->id : 'null'),
            ]);
        });

        // Debug the actual broadcasting auth requests
        Route::post('/debug-broadcasting-auth-post', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::info('Broadcasting Auth Debug', [
                'authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'channel_name' => $request->input('channel_name'),
                'socket_id' => $request->input('socket_id'),
                'headers' => $request->headers->all(),
                'request_data' => $request->all(),
                'session_id' => session()->getId(),
            ]);

            $user = Auth::user();

            return response()->json([
                'debug' => true,
                'authenticated' => Auth::check(),
                'user_id' => $user ? $user->id : null,
                'channel_name' => $request->input('channel_name'),
                'socket_id' => $request->input('socket_id'),
            ]);
        });
    });
}

Route::get('/', function () {
    // Redirect authenticated users to dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    // Show homepage for guests only
    // Billing is disabled - hide pricing section
    $showPricingSection = false;

    // Get dynamic pricing from system settings
    $professionalMonthly = SystemSetting::get('saas_professional_monthly', 30);
    $professionalYearly = SystemSetting::get('saas_professional_yearly', 300);

    // Get real statistics from database
    $stats = [
        'doctors' => User::where('role', 'doctor')->count(),
        'appointments' => Appointment::count(),
        'patients' => Appointment::distinct('patient_id')->count('patient_id'),
        'avg_rating' => number_format(Review::avg('rating') ?? 0, 1),
    ];

    // Define SaaS pricing plans (no free plan)
    $pricingPlans = [
        'professional' => [
            'name' => 'Professional',
            'price_monthly' => $professionalMonthly,
            'price_yearly' => $professionalYearly,
            'description' => 'Most popular for growing practices',
            'features' => [
                'Unlimited AI consultations',
                'Advanced patient management',
                'Voice assistant & transcription',
                'Professional landing page',
                'Priority email support',
                'Export capabilities',
                'Practice analytics',
                'Electronic Medical Records (EMR)',
                'Telehealth & video consultations',
                'Digital prescriptions',
                'Automated billing & invoicing',
                'HIPAA-compliant security',
                'Real-time appointment scheduling',
                'Patient testimonials & reviews',
                'Blog management system',
                'Sub-user management',
                'Practice performance analytics',
                'Kiosk check-in system',
                'Predictive analytics & risk assessment'
            ],
            'is_featured' => true,
            'button_text' => 'Start Free Trial',
            'button_url' => '/register?plan=professional',
            'plan_id' => 'professional'
        ]
    ];

    return view('main', compact('showPricingSection', 'pricingPlans', 'stats'));
});

// Registration choice page
Route::get('/register', function () {
    return view('auth.register-choice');
})->name('register');

// Doctor registration route - redirect to the actual doctor registration
Route::get('/register-doctor', function (\Illuminate\Http\Request $request) {
    // Since we removed the GET route from auth.php, we need to create it here
    return app(\App\Http\Controllers\Auth\RegisteredUserController::class)->create($request);
})->name('register.doctor');

// Patient registration routes
Route::get('/register/patient', [PatientRegistrationController::class, 'create'])->name('patient.register');
Route::post('/register/patient', [PatientRegistrationController::class, 'store'])->name('patient.register.store');

// Public doctor routes
Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
Route::get('/doctors/search', [DoctorController::class, 'search'])->name('doctors.search');
Route::get('/doctors/{doctor}/slots', [DoctorController::class, 'getAvailableSlots'])->name('doctors.slots');
Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
Route::get('/doctors/{doctor}/reviews', [ReviewController::class, 'doctorReviews'])->name('doctors.reviews');
Route::get('/doctors/{doctor}/reviews/ajax', [ReviewController::class, 'getDoctorReviews'])->name('doctors.reviews.ajax');

// Notification API routes
Route::middleware(['auth', \App\Http\Middleware\EnsureJsonResponse::class])->group(function () {
    Route::get('/api/notifications', [NotificationController::class, 'apiIndex'])->name('api.notifications.index');
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread-count');
    Route::post('/api/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::post('/api/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');

    // Offline notification sync routes
    Route::post('/api/notifications/sync', [NotificationController::class, 'sync'])->name('api.notifications.sync');
    Route::get('/api/notifications/check', [NotificationController::class, 'check'])->name('api.notifications.check');

    // Login redirect check API
    Route::get('/api/auth/check-redirect', [\App\Http\Controllers\Auth\LoginRedirectController::class, 'checkRedirect'])->name('api.auth.check-redirect');
});

// Test all notification types page
Route::get('/test-all-notifications', [App\Http\Controllers\TestAllNotificationsController::class, 'index'])
    ->name('test.all.notifications')
    ->middleware(['auth']);

// Send test notification of specific type
Route::post('/test-all-notifications/send', [App\Http\Controllers\TestAllNotificationsController::class, 'send'])
    ->name('test.all.notifications.send')
    ->middleware(['auth']);

// Enhanced notification testing page
Route::get('/test-enhanced-notifications', function () {
    return view('test-enhanced-notifications');
})->name('test.enhanced.notifications');

// Comprehensive notification diagnostics
Route::get('/notification-diagnostics', function () {
    return view('notification-diagnostics');
})->name('notification.diagnostics');

// Offline notifications test page
Route::get('/test-offline-notifications', function () {
    return view('test-offline-notifications');
})->name('test.offline.notifications')->middleware(['auth']);

// Quick diagnostic route for API checks
Route::get('/api/notification-diagnostics', function () {
    return response()->json([
        'echo_available' => false,
        'echo_connector' => false,
        'pusher_available' => false,
        'pusher_state' => 'unknown',
        'notification_system' => false,
        'notification_initialized' => false,
        'user_id' => Auth::id(),
        'user_role' => Auth::user()?->role,
        'sound_enabled' => true,
        'toast_enabled' => true,
        'broadcast_driver' => config('broadcasting.default'),
        'queue_driver' => config('queue.default'),
        'pusher_app_key' => config('broadcasting.connections.pusher.key'),
    ]);
})->middleware(['auth']);

// Notification debug test page
Route::get('/notification-debug', function () {
    return view('test-notification-debug');
})->middleware(['auth'])->name('notification.debug');

// Test notification endpoint
Route::get('/notifications/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Test notification sent successfully',
        'timestamp' => now()->toISOString(),
        'user_id' => Auth::id(),
        'user_role' => Auth::user()?->role
    ]);
})->middleware(['auth']);

// Temporary test endpoint without auth for debugging - COMMENTED OUT FOR PRODUCTION
/*
Route::get('/notifications/test-debug', function () {
    try {
        // Use the first user from the database for testing
        $testUser = User::first();

        if (!$testUser) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in database for testing',
                'timestamp' => now()->toISOString()
            ], 404);
        }

        // Send a test notification
        $testUser->notify(new \App\Notifications\TestNotification([
            'type' => 'debug-test',
            'title' => 'Debug Test Notification',
            'message' => 'This is a debug test notification sent without authentication',
            'icon' => 'bug',
            'link' => '/notification-debug',
            'link_text' => 'View Debug Page'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Debug test notification sent successfully',
            'timestamp' => now()->toISOString(),
            'user_id' => $testUser->id,
            'user_name' => $testUser->name,
            'notification_data' => [
                'type' => 'debug-test',
                'title' => 'Debug Test Notification',
                'message' => 'This is a debug test notification sent without authentication'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send debug test notification',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
});
*/

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

Route::middleware(['auth', 'sub.user.permissions'])->group(function () {

    // Sub-user management routes (only for main doctor users)
    Route::prefix('sub-users')->name('sub-users.')->middleware('role:doctor')->group(function () {
        Route::get('/', [App\Http\Controllers\SubUserController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\SubUserController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\SubUserController::class, 'store'])->name('store');
        Route::get('/{subUser}', [App\Http\Controllers\SubUserController::class, 'show'])->name('show');
        Route::get('/{subUser}/edit', [App\Http\Controllers\SubUserController::class, 'edit'])->name('edit');
        Route::put('/{subUser}', [App\Http\Controllers\SubUserController::class, 'update'])->name('update');
        Route::delete('/{subUser}', [App\Http\Controllers\SubUserController::class, 'destroy'])->name('destroy');
        Route::patch('/{subUser}/toggle-status', [App\Http\Controllers\SubUserController::class, 'toggleStatus'])->name('toggle-status');
    });


    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings');
    Route::put('/user/settings/update', [UserSettingsController::class, 'update'])->name('settings.update');
    // Diagnosed Cases (AI analysis results)
    Route::get('/doctor/cases-overview', [OpenAIController::class, 'getCases'])->name('doctor.cases.overview');
    // Route::get('/openai/form', [OpenAIController::class, 'showForm'])->name('openai.form');
    Route::post('/patient/summary', [OpenAIController::class, 'generatePatientSummary'])->name('patient.summary');
    Route::get('/dashboard', [OpenAIController::class, 'dashboard'])->name('dashboard');
    Route::get('/clinical/monitoring', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'dashboard'])->name('clinical.monitoring');

    // Appointment routes for patients
    Route::resource('appointments', AppointmentController::class)->except(['edit', 'update', 'create', 'store']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments/calendar/events', [AppointmentController::class, 'getCalendarEvents'])->name('appointments.calendar.events');

    // Diagnosis creation from appointment page (for doctors) - DUPLICATE REMOVED - kept in doctor group only
    // Route::post('/appointments/{appointment}/create-diagnosis', [DiagnosisController::class, 'createFromAppointment'])->name('appointments.create-diagnosis');

    // Review routes for patients
    Route::resource('reviews', ReviewController::class);
    Route::get('/appointments/{appointment}/review', [ReviewController::class, 'create'])->name('appointments.review');

    // Diagnosis routes
    Route::prefix('diagnosis')->name('diagnosis.')->group(function () {

        // Patient routes
        Route::middleware('role:patient')->group(function () {
            // Specific routes first
            Route::get('/my-diagnoses', [DiagnosisController::class, 'patientIndex'])->name('patient.index');
            Route::get('/{diagnosis}/view', [DiagnosisController::class, 'patientView'])->name('patient.view');
            Route::post('/{diagnosis}/review', [DiagnosisController::class, 'storeReview'])->name('review.store');
        });

        // Doctor routes
        Route::middleware('role:doctor')->group(function () {
            // Redirect index to cases-overview (consolidated page)
            Route::get('/', function() {
                return redirect()->route('doctor.cases.overview');
            })->name('index');
            Route::get('/create', [DiagnosisController::class, 'create'])->name('create');
            Route::post('/', [DiagnosisController::class, 'store'])->name('store');

            // Moved after /create to prevent route clash
            Route::get('/{diagnosis}', [DiagnosisController::class, 'show'])->name('show');
        });

        // Routes accessible to both doctors and patients
        Route::post('/{diagnosis}/follow-up', [DiagnosisController::class, 'storeFollowUp'])->name('follow-up.store');

        // Voice file serving route (secure)
        Route::get('/{diagnosis}/voice', [DiagnosisController::class, 'serveVoiceFile'])->name('voice');
    });

    // Prescription routes
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');

    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/settings', [NotificationController::class, 'settings'])->name('settings');
        Route::put('/settings', [NotificationController::class, 'updateSettings'])->name('settings.update');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');

        // Test route for notifications
        Route::get('/test', function () {
            /** @var User $user */
            $user = Auth::user();

            // Send a real-time test notification
            $user->notify(new App\Notifications\TestNotification([
                'type' => 'test',
                'title' => 'Real-time Test Notification',
                'message' => 'This is a test to verify real-time notifications are working correctly!',
                'icon' => 'bell',
                'link' => '/dashboard',
                'link_text' => 'View Dashboard'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent!'
            ]);
        })->name('test');

        // Notification testing page
        Route::get('/test-page', function () {
            return view('test-notifications');
        })->name('notifications.test-page');

        // Appointment notification testing page
        Route::get('/test-appointment', function () {
            return view('test-appointment-notifications');
        })->name('notifications.test-appointment');

        // Broadcasting auth testing page
        Route::get('/test-auth', function () {
            return view('test-auth');
        })->name('broadcasting.test-auth');

        // Asset debug page
        Route::get('/debug-assets', function () {
            return view('debug-assets');
        })->name('debug.assets');
    });

    // Subscription routes (only for payment responsible users)
    Route::middleware('payment.responsible')->group(function () {
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
    });

    // Invoice routes (only for payment responsible users)
    Route::middleware('payment.responsible')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::get('/invoices/{invoice}/manual-payment', [InvoiceController::class, 'manualPayment'])->name('invoices.manual-payment');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::post('/invoices/{invoice}/sync', [InvoiceController::class, 'sync'])->name('invoices.sync');
    });

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

    // Test voice notes functionality
    Route::get('/test-notes', function() {
        return view('test-notes');
    })->name('test.notes');



    // Test diagnosis system access
    Route::get('/test-diagnosis-access', function() {
        return view('test-diagnosis-access');
    })->name('test.diagnosis.access');

    // Test sub-user permissions
    Route::get('/test-sub-user-permissions', function() {
        /** @var User $user */
        $user = Auth::user();
        $menuItems = \App\Helpers\MenuHelper::getMenuItems($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_sub_user' => $user->isSubUser(),
                'sub_user_role' => $user->sub_user_role,
                'parent_user_id' => $user->parent_user_id,
            ],
            'permissions' => $user->permissions->pluck('name'),
            'menu_items' => $menuItems,
            'can_access' => [
                'dashboard' => $user->canAccessRoute('dashboard'),
                // 'ai.ask-ai' => $user->canAccessRoute('ai.ask-ai'), // Temporarily disabled
                'ai.ambient-listening.index' => $user->canAccessRoute('ai.ambient-listening.index'),
                'diagnosis.index' => $user->canAccessRoute('diagnosis.index'),
                'doctor.cases.overview' => $user->canAccessRoute('doctor.cases.overview'),
                'sub-users.index' => $user->canAccessRoute('sub-users.index'),
            ],
        ]);
    })->name('test.sub-user.permissions');

    // Test sub-user access page
    Route::get('/test-sub-user-access', function() {
        return view('test-sub-user-access');
    })->name('test.sub-user.access');

    // Debug sub-user middleware
    Route::get('/debug-sub-user', function() {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'is_sub_user_field' => $user->is_sub_user,
            'parent_user_id' => $user->parent_user_id,
            'sub_user_role' => $user->sub_user_role,
            'isSubUser_method' => $user->isSubUser(),
            'isDoctor_method' => $user->isDoctor(),
            'parent_user' => $user->parentUser ? [
                'id' => $user->parentUser->id,
                'name' => $user->parentUser->name,
                'email' => $user->parentUser->email,
                'role' => $user->parentUser->role,
                'has_doctor_profile' => $user->parentUser->doctor ? true : false,
                'doctor_is_active' => $user->parentUser->doctor ? $user->parentUser->doctor->is_active : null,
            ] : null,
            'user_doctor_profile' => $user->doctor ? [
                'id' => $user->doctor->id,
                'is_active' => $user->doctor->is_active,
            ] : null,
        ]);
    })->name('debug.sub-user');

    // Simple test route without middleware
    Route::get('/simple-test', function() {
        if (!Auth::check()) {
            return 'Not logged in';
        }

        /** @var User $user */
        $user = Auth::user();
        return "Hello {$user->name}! You are logged in as a " . ($user->isSubUser() ? 'sub-user' : 'main user');
    })->name('simple.test');

    // Sub-user success page
    Route::get('/sub-user-success', function() {
        if (!Auth::check()) {
            return redirect()->route('dashboard');
        }

        /** @var User $user */
        $user = Auth::user();
        if (!$user->isSubUser()) {
            return redirect()->route('dashboard');
        }
        return view('sub-user-success');
    })->name('sub-user.success');

    // Test dashboard access for sub-users
    Route::get('/test-dashboard-access', function() {
        if (!Auth::check()) {
            return 'Please login first';
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->isSubUser()) {
            return 'This test is only for sub-users';
        }

        try {
            // Test effective doctor access
            $effectiveDoctor = $user->getEffectiveDoctor();
            $appointmentsCount = $effectiveDoctor ? $effectiveDoctor->appointments()->count() : 0;
            $reviewsCount = $effectiveDoctor ? $effectiveDoctor->reviews()->count() : 0;

            return response()->json([
                'status' => 'success',
                'message' => 'Sub-user can access dashboard successfully!',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_sub_user' => $user->isSubUser(),
                    'parent_doctor' => $user->parentUser ? $user->parentUser->name : null,
                ],
                'effective_doctor' => [
                    'id' => $effectiveDoctor ? $effectiveDoctor->id : null,
                    'appointments_count' => $appointmentsCount,
                    'reviews_count' => $reviewsCount,
                ],
                'permissions' => $user->permissions->pluck('display_name')->toArray(),
                'accessible_routes' => [
                    'dashboard' => $user->canAccessRoute('dashboard'),
                    'appointments' => $user->canAccessRoute('doctor.appointments.index'),
                    'doctor.cases.overview' => $user->canAccessRoute('doctor.cases.overview'),
                    'settings' => $user->canAccessRoute('settings'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error accessing dashboard: ' . $e->getMessage()
            ], 500);
        }
    })->name('test.dashboard.access');

    // Test blog controller access
    Route::get('/test-blog-access', function() {
        if (!Auth::check()) {
            return 'Please login first';
        }

        /** @var User $user */
        $user = Auth::user();
        if (!$user->isSubUser()) {
            return 'Please login as sub-user first';
        }

        try {
            $controller = new BlogController();
            $doctor = $user->getEffectiveDoctor();

            if (!$doctor) {
                return 'No effective doctor found';
            }

            $blogCount = $doctor->blogPosts()->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Blog controller works!',
                'doctor_id' => $doctor->id,
                'blog_posts_count' => $blogCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->name('test.blog.access');

    // Test diagnosis system
    Route::get('/test-diagnosis', function() {
        /** @var User $user */
        $user = Auth::user();
        return response()->json([
            'user_role' => $user->role,
            'is_doctor' => $user->isDoctor(),
            'is_patient' => $user->isPatient(),
            'diagnosis_routes' => [
                'doctor_index' => route('diagnosis.index'),
                'doctor_create' => route('diagnosis.create'),
                'patient_index' => route('diagnosis.patient.index'),
            ],
            'diagnosis_count' => [
                'doctor_diagnoses' => $user->isDoctor() ? $user->doctorDiagnoses()->count() : 'N/A',
                'patient_diagnoses' => $user->isPatient() ? $user->patientDiagnoses()->count() : 'N/A',
            ]
        ]);
    })->name('test.diagnosis');

    // Test grace period notification
    Route::get('/test-grace-period', function() {
        /** @var User $user */
        $user = Auth::user();
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

    // Test route to verify restriction system
    Route::get('/test/restriction-status', function() {
        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        $setting = $user->monthlyInvoiceSetting;
        $testRoutes = ['doctor.cases.overview', 'dashboard', 'appointments', 'reviews', 'settings', 'profile.edit']; // Removed ai.ask-ai temporarily

        $results = [];
        foreach ($testRoutes as $route) {
            $results[$route] = [
                'is_restricted' => $user->isPageRestricted($route),
                'user_is_restricted' => $user->isRestricted(),
                'configured_pages' => $setting ? $setting->restricted_pages : null,
            ];
        }

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'is_restricted' => $user->isRestricted(),
            'setting_exists' => !!$setting,
            'restriction_active' => $setting ? $setting->is_restricted : false,
            'configured_restricted_pages' => $setting ? $setting->restricted_pages : null,
            'route_tests' => $results,
        ]);

    })->name('test.restriction-status');
});


Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Contact submission routes moved to admin middleware group below
Route::get('/about', [UserSettingsController::class, 'about'])->name('about');



// Doctor routes (accessible by doctors and their sub-users with permissions)
Route::middleware(['auth', 'admin.impersonation', 'doctor', 'sub.user.permissions'])->prefix('doctor')->name('doctor.')->group(function () {
    // Redirect doctor dashboard to main dashboard
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard');
    })->name('dashboard');

    // Appointment management
    Route::get('/appointments', [DoctorDashboardController::class, 'appointments'])->name('appointments.index');
    Route::get('/appointments/create', [DoctorDashboardController::class, 'createAppointment'])->name('appointments.create');
    Route::post('/appointments', [DoctorDashboardController::class, 'storeAppointment'])->name('appointments.store');
    Route::get('/appointments/{appointment}', [DoctorDashboardController::class, 'showAppointment'])->name('appointments.show');
    Route::post('/appointments/{appointment}/confirm', [DoctorDashboardController::class, 'confirmAppointment'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/cancel', [DoctorDashboardController::class, 'cancelAppointment'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/complete', [DoctorDashboardController::class, 'completeAppointment'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/no-show', [DoctorDashboardController::class, 'markNoShow'])->name('appointments.no-show');
    Route::get('/appointments/calendar/events', [DoctorDashboardController::class, 'getCalendarEvents'])->name('appointments.calendar.events');
    Route::get('/appointments/{appointment}/completed', [DoctorDashboardController::class, 'showCompletedAppointment'])->name('appointments.completed');
    Route::post('/appointments/toggle-auto-approve', [DoctorDashboardController::class, 'toggleAutoApprove'])->name('appointments.toggle-auto-approve');
    Route::get('/patients/search', [DoctorDashboardController::class, 'searchPatients'])->name('patients.search');

    // Follow-up appointment routes
    Route::get('/appointments/{appointment}/follow-ups/create', [DoctorDashboardController::class, 'createFollowUp'])->name('follow-ups.create');
    Route::post('/appointments/{appointment}/follow-ups', [DoctorDashboardController::class, 'storeFollowUp'])->name('follow-ups.store');

    Route::post('/appointments/{appointment}/status', [DoctorDashboardController::class, 'updateAppointmentStatus'])->name('appointments.status');
    Route::post('/appointments/reorder', [DoctorDashboardController::class, 'reorderAppointments'])->name('appointments.reorder');

    // Prescription routes for appointments
    Route::post('/prescriptions/{appointment}', [PrescriptionController::class, 'store'])->name('prescriptions.store');

    // Diagnosis creation from appointment
    Route::post('/appointments/{appointment}/create-diagnosis', [DiagnosisController::class, 'createFromAppointment'])->name('appointments.create-diagnosis');

    // Availability management
    Route::resource('availability', AvailabilityController::class);
    Route::post('/availability/{availability}/toggle', [AvailabilityController::class, 'toggle'])->name('availability.toggle');
    Route::post('/availability/bulk', [AvailabilityController::class, 'bulkStore'])->name('availability.bulk');

    // Reviews
    Route::get('/reviews', [DoctorDashboardController::class, 'reviews'])->name('reviews.index');

    // Profile management
    Route::get('/profile', [DoctorDashboardController::class, 'profile'])->name('profile.edit');
    Route::patch('/profile', [DoctorDashboardController::class, 'updateProfile'])->name('profile.update');

    // Patient Management
    Route::get('/patients', [App\Http\Controllers\PatientManagementController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [App\Http\Controllers\PatientManagementController::class, 'create'])->name('patients.create');
    Route::post('/patients', [App\Http\Controllers\PatientManagementController::class, 'store'])->name('patients.store');
    Route::get('/patients/{id}', [App\Http\Controllers\PatientManagementController::class, 'show'])->name('patients.show');
    Route::get('/patients/{id}/edit', [App\Http\Controllers\PatientManagementController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{id}', [App\Http\Controllers\PatientManagementController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{id}', [App\Http\Controllers\PatientManagementController::class, 'destroy'])->name('patients.destroy');

    // Appointment Settings
    Route::get('/settings/appointments', [App\Http\Controllers\Doctor\AppointmentSettingsController::class, 'index'])->name('settings.appointments');
    Route::put('/settings/appointments', [App\Http\Controllers\Doctor\AppointmentSettingsController::class, 'updateAppointmentTypes'])->name('settings.appointments.update');

    // SMS Provider Settings
    Route::get('/sms-settings', [App\Http\Controllers\Doctor\DashboardController::class, 'smsSettings'])->name('sms-settings');

    // Google integration
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('/redirect', [GoogleController::class, 'redirectToGoogle'])->name('redirect');
        Route::get('/callback', [GoogleController::class, 'handleGoogleCallback'])->name('callback');
        Route::post('/disconnect', [GoogleController::class, 'disconnectGoogle'])->name('disconnect');
        Route::get('/accounts', [GoogleController::class, 'getAccounts'])->name('accounts');
        Route::get('/locations', [GoogleController::class, 'getLocations'])->name('locations');
        Route::post('/account-location', [GoogleController::class, 'setAccountLocation'])->name('account-location');
    });

    // Doctor Notes routes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'store'])->name('store');
        Route::get('/{note}', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'show'])->name('show');
        Route::get('/{note}/edit', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'edit'])->name('edit');
        Route::put('/{note}', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'update'])->name('update');
        Route::delete('/{note}', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'destroy'])->name('destroy');
        Route::post('/transcribe-audio', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'transcribeAudio'])->name('transcribe-audio');
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
        Route::get('/settings', [ChatController::class, 'settings'])->name('settings');
        Route::post('/settings', [ChatController::class, 'updateSettings'])->name('update-settings');
        Route::get('/unread/count', [ChatController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/mark-all-read', [ChatController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/{sessionId}', [ChatController::class, 'show'])->name('show');
        Route::post('/{sessionId}/send', [ChatController::class, 'sendMessage'])->name('send');
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

    // HEP Program Management
    Route::prefix('hep')->name('hep.')->group(function () {
        Route::get('/patients-list', [App\Http\Controllers\Doctor\HEPController::class, 'getPatients'])->name('patients-list');
        Route::get('/', [App\Http\Controllers\Doctor\HEPController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Doctor\HEPController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Doctor\HEPController::class, 'store'])->name('store');
        Route::get('/{program}', [App\Http\Controllers\Doctor\HEPController::class, 'show'])->name('show');
        Route::get('/{program}/edit', [App\Http\Controllers\Doctor\HEPController::class, 'edit'])->name('edit');
        Route::put('/{program}', [App\Http\Controllers\Doctor\HEPController::class, 'update'])->name('update');
        Route::delete('/{program}', [App\Http\Controllers\Doctor\HEPController::class, 'destroy'])->name('destroy');
        Route::post('/{program}/assign', [App\Http\Controllers\Doctor\HEPController::class, 'assign'])->name('assign');
        Route::get('/{program}/progress', [App\Http\Controllers\Doctor\HEPController::class, 'progress'])->name('progress');
        Route::post('/generate-ai', [App\Http\Controllers\Doctor\HEPController::class, 'generateAI'])->name('generate-ai');
    });

    // Claims Management
    Route::resource('claims', App\Http\Controllers\Doctor\ClaimsController::class);
    Route::post('/claims/{claim}/submit-to-clearinghouse', [App\Http\Controllers\Doctor\ClaimsController::class, 'submitToClearinghouse'])->name('claims.submit-to-clearinghouse');
    Route::post('/claims/{claim}/approve', [App\Http\Controllers\Doctor\ClaimsController::class, 'markApproved'])->name('claims.approve');
    Route::post('/claims/{claim}/deny', [App\Http\Controllers\Doctor\ClaimsController::class, 'markDenied'])->name('claims.deny');

    // Kiosk Management
    Route::prefix('kiosk')->name('kiosk.')->group(function () {
        Route::get('/setup', [App\Http\Controllers\Doctor\KioskController::class, 'setup'])->name('setup');
        Route::post('/setup', [App\Http\Controllers\Doctor\KioskController::class, 'storeSetup'])->name('setup.store');
        Route::get('/management', [App\Http\Controllers\Doctor\KioskController::class, 'management'])->name('management');
        Route::get('/analytics', [App\Http\Controllers\Doctor\KioskController::class, 'analytics'])->name('analytics');
        Route::get('/access-url', [App\Http\Controllers\Doctor\KioskController::class, 'getAccessUrl'])->name('access-url');
        Route::post('/activate', [App\Http\Controllers\Doctor\KioskController::class, 'activate'])->name('activate');
        Route::post('/deactivate', [App\Http\Controllers\Doctor\KioskController::class, 'deactivate'])->name('deactivate');
        Route::post('/regenerate-token', [App\Http\Controllers\Doctor\KioskController::class, 'regenerateToken'])->name('regenerate-token');
    });
});

// Hospital Admin routes
Route::middleware(['auth', 'admin.impersonation', 'hospital.admin'])->prefix('hospital-admin')->name('hospital-admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\HospitalAdmin\DashboardController::class, 'index'])->name('dashboard');

    // Doctor Management
    Route::prefix('doctors')->name('doctors.')->group(function () {
        Route::get('/', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'store'])->name('store');
        Route::get('/statistics', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'statistics'])->name('statistics');
        Route::get('/{doctor}', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'show'])->name('show');
        Route::get('/{doctor}/edit', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'edit'])->name('edit');
        Route::put('/{doctor}', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'update'])->name('update');
        Route::patch('/{doctor}/toggle-status', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{doctor}/login-as', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'loginAs'])->name('login-as');
        Route::delete('/{doctor}', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'destroy'])->name('destroy');
    });

    // Hospital Settings
    Route::prefix('hospital')->name('hospital.')->group(function () {
        Route::get('/profile', [App\Http\Controllers\HospitalAdmin\HospitalController::class, 'profile'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\HospitalAdmin\HospitalController::class, 'updateProfile'])->name('update-profile');
    });

    // Departments Management
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'show'])->name('show');
        Route::get('/{department}/edit', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [App\Http\Controllers\HospitalAdmin\DepartmentController::class, 'destroy'])->name('destroy');
    });

    // Subscription Management (using HospitalAdmin subscription controller)
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/manage', [App\Http\Controllers\HospitalAdmin\SubscriptionController::class, 'manage'])->name('manage');
        Route::get('/pricing', [App\Http\Controllers\HospitalAdmin\SubscriptionController::class, 'pricing'])->name('pricing');
        Route::post('/update-plan', [App\Http\Controllers\HospitalAdmin\SubscriptionController::class, 'updatePlan'])->name('update-plan');
        Route::post('/checkout', [App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::get('/customer-portal', [App\Http\Controllers\SubscriptionController::class, 'customerPortal'])->name('customer-portal');
        Route::get('/success', [App\Http\Controllers\SubscriptionController::class, 'success'])->name('success');
    });

    // Invoice Management (using HospitalAdmin invoice controller)
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [App\Http\Controllers\HospitalAdmin\InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [App\Http\Controllers\HospitalAdmin\InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [App\Http\Controllers\HospitalAdmin\InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/sync', [App\Http\Controllers\HospitalAdmin\InvoiceController::class, 'sync'])->name('sync');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/overview', [App\Http\Controllers\HospitalAdmin\AnalyticsController::class, 'overview'])->name('overview');
        Route::get('/doctors', [App\Http\Controllers\HospitalAdmin\AnalyticsController::class, 'doctors'])->name('doctors');
        Route::get('/financial', [App\Http\Controllers\HospitalAdmin\AnalyticsController::class, 'financial'])->name('financial');
    });

    // Usage Reports
    Route::prefix('usage')->name('usage.')->group(function () {
        Route::get('/', [App\Http\Controllers\HospitalAdmin\UsageController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\HospitalAdmin\UsageController::class, 'export'])->name('export');
    });

    // Claims Management
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'store'])->name('store');
        Route::get('/{claim}', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'show'])->name('show');
        Route::get('/{claim}/edit', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'edit'])->name('edit');
        Route::put('/{claim}', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'update'])->name('update');
        Route::delete('/{claim}', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'destroy'])->name('destroy');

        // Clearinghouse operations
        Route::post('/submit-to-clearinghouse', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'submitToClearinghouse'])->name('submit-to-clearinghouse');
        Route::get('/clearinghouse-accounts', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getClearinghouseAccounts'])->name('clearinghouse-accounts');
        Route::get('/submissions', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getSubmissions'])->name('submissions');
        Route::get('/submissions/{submission}/status', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getSubmissionStatus'])->name('submission-status');

        // Error handling and reconciliation
        Route::get('/failed-submissions', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getFailedSubmissions'])->name('failed-submissions');
        Route::post('/submissions/{submission}/manual-resubmit', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'manualResubmit'])->name('manual-resubmit');

        // Compliance reporting
        Route::get('/compliance-report', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'generateComplianceReport'])->name('compliance-report');
        Route::get('/violation-report', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'generateViolationReport'])->name('violation-report');
        Route::get('/audit-trail/export', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'exportAuditTrail'])->name('audit-trail.export');
    });
});

// Public Doctor Landing Pages (must be after doctor middleware group to avoid conflicts)
Route::get('/doctor/{username}', [PublicLandingPageController::class, 'show'])->name('doctor.landing');
Route::get('/doctor/{username}/blogs', [PublicLandingPageController::class, 'showBlogs'])->name('doctor.blogs');
Route::get('/doctor/{username}/blog/{slug}', [PublicLandingPageController::class, 'showBlogPost'])->name('doctor.blog.post');

// Doctor Landing Page Management Routes - Protected by auth middleware
Route::prefix('doctor/landing-page')->name('doctor.landing-page.')->middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/index', [LandingPageController::class, 'index'])->name('index');
    Route::get('/page-builder', [LandingPageController::class, 'pageBuilder'])->name('page-builder');
    Route::get('/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::post('/update', [LandingPageController::class, 'update'])->name('update');
    Route::post('/update-sections', [LandingPageController::class, 'updateSections'])->name('update-sections');
    Route::post('/upload-hero-image', [LandingPageController::class, 'uploadHeroImage'])->name('upload-hero-image');
    Route::post('/upload-section-image', [LandingPageController::class, 'uploadSectionImage'])->name('upload-section-image');
    Route::post('/toggle-publish', [LandingPageController::class, 'togglePublish'])->name('toggle-publish');
    Route::get('/preview/{username}', [LandingPageController::class, 'preview'])->name('preview');
    Route::get('/animation-presets', [LandingPageController::class, 'getAnimationPresets'])->name('animation-presets');
    Route::get('/analytics', [AnalyticsController::class, 'landingPageAnalytics'])->name('analytics');
    Route::get('/analytics/data', [AnalyticsController::class, 'getLandingPageAnalyticsData'])->name('analytics.data');
});

// Public Chat Routes
Route::post('/doctor/{username}/chat/init', [PublicChatController::class, 'initializeChat'])->name('doctor.public-chat.init');
Route::post('/doctor/{username}/chat/send', [PublicChatController::class, 'sendMessage'])->name('doctor.public-chat.send');
Route::get('/doctor/{username}/chat/history', [PublicChatController::class, 'getChatHistory'])->name('doctor.public-chat.history');
Route::get('/doctor/{username}/chat/check-new', [PublicChatController::class, 'checkNewMessages'])->name('doctor.public-chat.check-new');

// Public Testimonials API
Route::get('/doctor/{username}/testimonials', [TestimonialController::class, 'getPublicTestimonials'])->name('doctor.testimonials.public');

// Video room route
Route::middleware(['auth'])->get('/video/room/{appointment}', function($appointmentId) {
    $appointment = \App\Models\Appointment::findOrFail($appointmentId);
    
    if (Auth::id() !== $appointment->doctor->user_id && Auth::id() !== $appointment->patient_id) {
        abort(403);
    }
    
    // Create room if not exists
    $roomName = 'appointment-' . $appointmentId;
    if (!$appointment->meeting_id) {
        $dailyService = app(\App\Services\DailyService::class);
        try {
            $room = $dailyService->createRoom($roomName, 120);
            $appointment->update(['meeting_id' => $roomName]);
            \Log::info('Video room created successfully', ['room' => $roomName]);
        } catch (\Exception $e) {
            \Log::error('Failed to create video room', [
                'room' => $roomName,
                'error' => $e->getMessage()
            ]);
            return view('video.room-error', [
                'appointment' => $appointment,
                'error' => 'Unable to create video room. Please contact support.'
            ]);
        }
    }
    
    return view('video.room', compact('appointment'));
})->name('video.room');

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
    Route::post('/users/{user}/login-as', [AdminController::class, 'loginAs'])->name('login-as');

    // Doctor verification routes
    Route::post('/doctors/{doctor}/verify', [AdminController::class, 'verifyDoctor'])->name('doctors.verify');
    Route::post('/doctors/{doctor}/unverify', [AdminController::class, 'unverifyDoctor'])->name('doctors.unverify');

    // Hospital Admin Management
    Route::get('/hospital-admins/{user}/manage', [AdminController::class, 'manageHospitalAdmin'])->name('hospital-admins.manage');
    Route::post('/hospital-admins/{user}/create-hospital', [AdminController::class, 'createHospitalForAdmin'])->name('hospital-admins.create-hospital');
    Route::put('/hospital-admins/{user}/update-hospital', [AdminController::class, 'updateHospitalForAdmin'])->name('hospital-admins.update-hospital');
    Route::get('/hospital-admins/{user}/doctors', [AdminController::class, 'manageHospitalDoctors'])->name('hospital-admins.doctors');
    Route::post('/hospital-admins/{user}/doctors/{doctor}/toggle-status', [AdminController::class, 'toggleHospitalDoctorStatus'])->name('hospital-admins.doctors.toggle-status');

    // Billing and subscription management
    Route::get('/billing', [AdminController::class, 'billing'])->name('billing');
    Route::get('/billing/export', [AdminController::class, 'exportBilling'])->name('billing.export');
    Route::get('/usage-analytics', [AdminController::class, 'usageAnalytics'])->name('usage-analytics');

    // System settings
    Route::get('/system-settings', [AdminController::class, 'systemSettings'])->name('system-settings');
    Route::post('/system-settings', [AdminController::class, 'updateSystemSettings'])->name('system-settings.update');

    // Subscription plan management
    Route::resource('subscription-plans', SubscriptionPlanController::class);
    Route::post('/subscription-plans/{subscriptionPlan}/toggle-active', [SubscriptionPlanController::class, 'toggleActive'])->name('subscription-plans.toggle-active');

    // User pricing management
    Route::get('/user-pricing', [App\Http\Controllers\Admin\UserPricingController::class, 'index'])->name('user-pricing.index');
    Route::get('/user-pricing/{user}/edit', [App\Http\Controllers\Admin\UserPricingController::class, 'edit'])->name('user-pricing.edit');
    Route::put('/user-pricing/{user}', [App\Http\Controllers\Admin\UserPricingController::class, 'update'])->name('user-pricing.update');
    Route::post('/user-pricing/bulk-update', [App\Http\Controllers\Admin\UserPricingController::class, 'bulkUpdate'])->name('user-pricing.bulk-update');

    // SMS settings with country-based provider management
    Route::get('/sms-settings', [AdminController::class, 'smsSettings'])->name('sms-settings');
    Route::post('/sms-settings/assign-countries', [AdminController::class, 'assignCountriesToProvider'])->name('sms-settings.assign-countries');
    Route::post('/sms-settings/remove-assignments', [AdminController::class, 'removeProviderCountryAssignments'])->name('sms-settings.remove-assignments');
    Route::post('/sms-settings/test', [AdminController::class, 'sendTestSms'])->name('sms-settings.test');

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

    // Kiosk Management
    Route::prefix('kiosks')->name('kiosks.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\KioskController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\KioskController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\KioskController::class, 'store'])->name('store');
        Route::get('/{kiosk}', [App\Http\Controllers\Admin\KioskController::class, 'show'])->name('show');
        Route::get('/{kiosk}/edit', [App\Http\Controllers\Admin\KioskController::class, 'edit'])->name('edit');
        Route::put('/{kiosk}', [App\Http\Controllers\Admin\KioskController::class, 'update'])->name('update');
        Route::delete('/{kiosk}', [App\Http\Controllers\Admin\KioskController::class, 'destroy'])->name('destroy');
        Route::get('/statistics', [App\Http\Controllers\Admin\KioskController::class, 'statistics'])->name('statistics');
    });

    // Exercise Library Management
    Route::prefix('exercises')->name('exercises.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminExerciseController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminExerciseController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminExerciseController::class, 'store'])->name('store');
        Route::get('/{exercise}', [App\Http\Controllers\Admin\AdminExerciseController::class, 'show'])->name('show');
        Route::get('/{exercise}/edit', [App\Http\Controllers\Admin\AdminExerciseController::class, 'edit'])->name('edit');
        Route::put('/{exercise}', [App\Http\Controllers\Admin\AdminExerciseController::class, 'update'])->name('update');
        Route::delete('/{exercise}', [App\Http\Controllers\Admin\AdminExerciseController::class, 'destroy'])->name('destroy');
        Route::get('/export', [App\Http\Controllers\Admin\AdminExerciseController::class, 'export'])->name('export');
        Route::get('/import', [App\Http\Controllers\Admin\AdminExerciseController::class, 'importForm'])->name('import');
        Route::post('/import', [App\Http\Controllers\Admin\AdminExerciseController::class, 'import'])->name('import.store');
        Route::get('/template/download', [App\Http\Controllers\Admin\AdminExerciseController::class, 'downloadTemplate'])->name('template.download');
    });

    // HEP Program Templates Management
    Route::prefix('hep-templates')->name('hep-templates.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/toggle-active', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{template}/duplicate', [App\Http\Controllers\Admin\AdminHepTemplateController::class, 'duplicate'])->name('duplicate');
    });

    // Real-time Performance Monitoring Dashboard
    Route::prefix('realtime-performance')->name('realtime-performance.')->group(function () {
        Route::get('/', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'index'])->name('dashboard');
        Route::get('/metrics', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getMetrics'])->name('metrics');
        Route::get('/analytics', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getAnalytics'])->name('analytics');
        Route::get('/load-stats', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getLoadStats'])->name('load-stats');
        Route::get('/connection-stats', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getConnectionStats'])->name('connection-stats');
        Route::get('/alerts', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getAlerts'])->name('alerts');
        Route::get('/health-overview', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'getHealthOverview'])->name('health-overview');
        Route::delete('/clear-metrics', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'clearMetrics'])->name('clear-metrics');
        Route::get('/export/{type}', [App\Http\Controllers\RealtimePerformanceDashboardController::class, 'exportData'])->name('export');
    });

    // Payer Rules Engine Management
    Route::prefix('payers')->name('payers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminPayerController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminPayerController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminPayerController::class, 'store'])->name('store');
        Route::get('/{payer}', [App\Http\Controllers\Admin\AdminPayerController::class, 'show'])->name('show');
        Route::get('/{payer}/edit', [App\Http\Controllers\Admin\AdminPayerController::class, 'edit'])->name('edit');
        Route::put('/{payer}', [App\Http\Controllers\Admin\AdminPayerController::class, 'update'])->name('update');
        Route::delete('/{payer}', [App\Http\Controllers\Admin\AdminPayerController::class, 'destroy'])->name('destroy');

        // Rules management for each payer
        Route::prefix('{payer}/rules')->name('rules.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'store'])->name('store');
            Route::get('/{rule}', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'show'])->name('show');
            Route::get('/{rule}/edit', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'edit'])->name('edit');
            Route::put('/{rule}', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'update'])->name('update');
            Route::delete('/{rule}', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'destroy'])->name('destroy');
            Route::post('/{rule}/test', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'test'])->name('test');
            Route::get('/export', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'export'])->name('export');
            Route::get('/import', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'importForm'])->name('import');
            Route::post('/import', [App\Http\Controllers\Admin\AdminPayerRuleController::class, 'import'])->name('import.store');
        });
    });

    // Compliance Dashboard
    Route::prefix('compliance')->name('compliance.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'index'])->name('dashboard');
        Route::get('/metrics', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'metrics'])->name('metrics');
        Route::get('/rule-effectiveness', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'ruleEffectiveness'])->name('rule-effectiveness');
        Route::get('/rules-needing-attention', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'rulesNeedingAttention'])->name('rules-needing-attention');
        Route::get('/rule-report/{ruleId}', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'ruleReport'])->name('rule-report');
        Route::get('/hipaa-compliance', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'hipaaCompliance'])->name('hipaa-compliance');
        Route::get('/audit-trail', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'auditTrail'])->name('audit-trail');
        Route::get('/export', [App\Http\Controllers\Admin\ComplianceDashboardController::class, 'export'])->name('export');
    });

    // Advanced Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [App\Http\Controllers\AlertController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\AlertController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\AlertController::class, 'store'])->name('store');
        Route::get('/{alert}', [App\Http\Controllers\AlertController::class, 'show'])->name('show');
        Route::get('/{alert}/edit', [App\Http\Controllers\AlertController::class, 'edit'])->name('edit');
        Route::put('/{alert}', [App\Http\Controllers\AlertController::class, 'update'])->name('update');
        Route::delete('/{alert}', [App\Http\Controllers\AlertController::class, 'destroy'])->name('destroy');

        // Alert lifecycle management
        Route::post('/{alert}/acknowledge', [App\Http\Controllers\AlertController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{alert}/resolve', [App\Http\Controllers\AlertController::class, 'resolve'])->name('resolve');
        Route::post('/bulk-acknowledge', [App\Http\Controllers\AlertController::class, 'bulkAcknowledge'])->name('bulk-acknowledge');
        Route::post('/bulk-resolve', [App\Http\Controllers\AlertController::class, 'bulkResolve'])->name('bulk-resolve');

        // API endpoints
        Route::get('/api/alerts', [App\Http\Controllers\AlertController::class, 'apiIndex'])->name('api.index');
        Route::get('/api/rules', [App\Http\Controllers\AlertController::class, 'rules'])->name('api.rules');
        Route::get('/api/statistics', [App\Http\Controllers\AlertController::class, 'statistics'])->name('api.statistics');
    });

    // System Monitoring Dashboard
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\MonitoringController::class, 'showDashboard'])->name('dashboard');
    });

    // Clearinghouse Management
    Route::prefix('clearinghouse')->name('clearinghouse.')->group(function () {
        Route::get('/accounts', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getClearinghouseAccounts'])->name('accounts');
        Route::get('/monitoring', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getSubmissions'])->name('monitoring');
        Route::get('/errors', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getFailedSubmissions'])->name('errors');
        Route::get('/providers', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'getClearinghouseAccounts'])->name('providers');
        Route::get('/metrics', [App\Http\Controllers\Admin\ClearinghouseMetricsController::class, 'index'])->name('metrics');
        Route::get('/metrics/data', [App\Http\Controllers\Admin\ClearinghouseMetricsController::class, 'getData'])->name('metrics.data');
        Route::get('/metrics/export', [App\Http\Controllers\Admin\ClearinghouseMetricsController::class, 'export'])->name('metrics.export');
    });

    // Waitlist Management
    Route::get('/waitlist/dashboard', [AdminWaitlistController::class, 'dashboard'])->name('waitlist.dashboard');
    Route::get('/waitlist/analytics', [AdminWaitlistController::class, 'analytics'])->name('waitlist.analytics');

    // Data Migration Management
    Route::prefix('data-migration')->name('data-migration.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DataMigrationController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\DataMigrationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\DataMigrationController::class, 'store'])->name('store');
        Route::get('/{dataMigration}', [App\Http\Controllers\Admin\DataMigrationController::class, 'show'])->name('show');
        Route::get('/{dataMigration}/preview', [App\Http\Controllers\Admin\DataMigrationController::class, 'preview'])->name('preview');
        Route::post('/{dataMigration}/start', [App\Http\Controllers\Admin\DataMigrationController::class, 'start'])->name('start');
        Route::post('/{dataMigration}/cancel', [App\Http\Controllers\Admin\DataMigrationController::class, 'cancel'])->name('cancel');
        Route::delete('/{dataMigration}', [App\Http\Controllers\Admin\DataMigrationController::class, 'destroy'])->name('destroy');
        Route::get('/{dataMigration}/export-errors', [App\Http\Controllers\Admin\DataMigrationController::class, 'exportErrors'])->name('export-errors');
        Route::get('/download-template/{entityType}', [App\Http\Controllers\Admin\DataMigrationController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/field-options/{entityType}', [App\Http\Controllers\Admin\DataMigrationController::class, 'getFieldOptions'])->name('field-options');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Return to hospital admin from doctor impersonation
    Route::post('/return-to-hospital-admin', [App\Http\Controllers\HospitalAdmin\DoctorController::class, 'returnToHospitalAdmin'])->name('return-to-hospital-admin');

    // Return to admin from user impersonation - requires web auth (impersonated user)
    Route::post('/return-to-admin', [AdminController::class, 'returnToAdmin'])->name('return-to-admin');
});

// Debug route to test if routes are working - COMMENTED OUT FOR PRODUCTION
/*
Route::get('/test-return-admin', function() {
    return response()->json([
        'message' => 'Route is accessible',
        'session_data' => [
            'impersonating_admin_id' => session('impersonating_admin_id'),
            'impersonating_user_id' => session('impersonating_user_id'),
        ],
        'auth_status' => [
            'web_check' => auth('web')->check(),
            'web_user_id' => auth('web')->id(),
            'admin_check' => auth('admin')->check(),
            'admin_user_id' => auth('admin')->id(),
        ]
    ]);
});
*/

// Security dashboard routes
Route::middleware('auth:admin')->prefix('security')->name('security.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Security\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/audit-logs/{auditLog}', [App\Http\Controllers\Security\DashboardController::class, 'show'])->name('audit-logs.show');
    Route::get('/export', [App\Http\Controllers\Security\DashboardController::class, 'export'])->name('export');
});

// Dropdown test route
Route::get('/test-dropdown', function () {
    return view('test-dropdown');
})->name('test.dropdown');

// Dropdown fix test route
Route::get('/test-dropdown-fix', function () {
    return view('test-dropdown-fix');
})->name('test.dropdown.fix');

require __DIR__.'/auth.php';

// Authenticated Pusher test
Route::get('/pusher-auth-test', function () {
    return view('pusher-auth-test');
})->middleware('auth');

// Test notification route
Route::get('/test-notifications', function () {
    return view('test-notifications');
})->middleware('auth');

Route::post('/api/test-notification', function () {
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        \Log::info('Sending test notification to user: ' . $user->id);
        
        $user->notify(new \App\Notifications\SystemAlertNotification(
            'API Test Notification',
            'This is a test notification sent via API at ' . now()->format('H:i:s'),
            'info',
            ['user_id' => $user->id] // Add user_id for broadcasting
        ));
        
        \Log::info('Test notification sent successfully');
        
        return response()->json(['success' => true, 'message' => 'Notification sent']);
    } catch (\Exception $e) {
        \Log::error('Test notification error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware('auth');

// Broadcasting test route
Route::get('/test-broadcasting-auth', function () {
    return view('test-broadcasting-auth');
})->middleware('auth')->name('test.broadcasting.auth');

// Broadcasting test route
Route::get('/test-broadcasting', function () {
    return view('test-broadcasting');
})->name('test.broadcasting');

// Authenticated broadcasting test page
Route::get('/test-authenticated-broadcasting-page', function () {
    return view('test-authenticated-broadcasting');
})->name('test.authenticated.broadcasting.page');

// Comprehensive authenticated broadcasting test
Route::get('/test-authenticated-broadcasting', function (\Illuminate\Http\Request $request) {
    try {
        // Get the first user from database for testing
        $testUser = User::first();

        if (!$testUser) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in database for testing',
                'timestamp' => now()->toISOString()
            ], 404);
        }

        // Authenticate the user for this request
        Auth::login($testUser);

        // Send authenticated test notification
        $testUser->notify(new \App\Notifications\TestNotification([
            'type' => 'authenticated-broadcast-test',
            'title' => 'Authenticated Broadcasting Test',
            'message' => 'This notification was sent through an authenticated user session to test private channel broadcasting',
            'icon' => 'shield-check',
            'link' => '/notification-debug',
            'link_text' => 'View Test Results'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Authenticated broadcasting test completed successfully',
            'timestamp' => now()->toISOString(),
            'user' => [
                'id' => $testUser->id,
                'name' => $testUser->name,
                'email' => $testUser->email,
                'role' => $testUser->role,
                'authenticated' => Auth::check(),
                'current_user_id' => Auth::id()
            ],
            'notification' => [
                'type' => 'authenticated-broadcast-test',
                'title' => 'Authenticated Broadcasting Test',
                'message' => 'This notification was sent through an authenticated user session to test private channel broadcasting',
                'channel' => 'App.User.' . $testUser->id,
                'broadcast_driver' => config('broadcasting.default'),
                'pusher_config' => [
                    'key' => config('broadcasting.connections.pusher.key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'app_id' => config('broadcasting.connections.pusher.app_id')
                ]
            ],
            'instructions' => [
                'frontend_test' => 'Open browser console to see if notification is received',
                'channel_verification' => 'Check if notification appears in private channel App.User.' . $testUser->id,
                'authentication_check' => 'Verify user authentication is maintained throughout the process'
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Authenticated broadcasting test failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->name('test.authenticated.broadcasting');

// Session encryption test route
Route::get('/test-session-encryption', function () {
    // Set some test data in session
    session(['test_key' => 'test_value_' . now()->timestamp]);
    session(['encrypted_data' => 'This data should be encrypted: ' . Str::random(32)]);

    // Retrieve and verify session data
    $testValue = session('test_key');
    $encryptedData = session('encrypted_data');

    return response()->json([
        'success' => true,
        'message' => 'Session encryption test completed',
        'session_data' => [
            'test_key' => $testValue,
            'encrypted_data' => $encryptedData,
            'session_id' => session()->getId(),
            'session_encrypt_enabled' => config('session.encrypt'),
        ],
        'encryption_status' => config('session.encrypt') ? 'ENABLED' : 'DISABLED',
        'timestamp' => now()->toISOString()
    ]);
})->name('test.session.encryption');
// Kiosk routes (public access for kiosk interface)
Route::middleware(['kiosk.session-isolation', 'kiosk.rate-limit'])->prefix('kiosk')->name('kiosk.')->group(function () {
    // Kiosk home/welcome screen
    Route::get('/', [App\Http\Controllers\KioskController::class, 'welcome'])->name('welcome');

    // Check-in flow
    Route::get('/checkin', [App\Http\Controllers\KioskController::class, 'checkinStart'])->name('checkin.start');
    Route::get('/checkin/search', [App\Http\Controllers\KioskController::class, 'checkinSearch'])->name('checkin.search');
    Route::post('/checkin/search', [App\Http\Controllers\KioskController::class, 'checkinSearchSubmit'])->name('checkin.search.submit');
    Route::get('/checkin/verify/{appointment}', [App\Http\Controllers\KioskController::class, 'checkinVerify'])->name('checkin.verify');
    Route::post('/checkin/confirm/{appointment}', [App\Http\Controllers\KioskController::class, 'checkinConfirm'])->name('checkin.confirm');
    Route::get('/checkin/success/{appointment}', [App\Http\Controllers\KioskController::class, 'checkinSuccess'])->name('checkin.success');

    // Payment flow
    Route::get('/payment/{appointment}', [App\Http\Controllers\KioskController::class, 'paymentAmount'])->name('payment.amount');
    Route::get('/payment/{appointment}/card', [App\Http\Controllers\KioskController::class, 'paymentCard'])->name('payment.card');
    Route::post('/payment/{appointment}/process', [App\Http\Controllers\KioskController::class, 'paymentProcess'])->name('payment.process');
    Route::get('/payment/{appointment}/receipt', [App\Http\Controllers\KioskController::class, 'paymentReceipt'])->name('payment.receipt');

    // Session management
    Route::post('/session/start', [App\Http\Controllers\KioskController::class, 'startSession'])->name('session.start');
    Route::post('/session/end', [App\Http\Controllers\KioskController::class, 'endSession'])->name('session.end');

    // Preferences (voice, contrast)
    Route::post('/preferences', [App\Http\Controllers\KioskController::class, 'updatePreferences'])->name('preferences.update');
});

// Patient HEP routes
Route::middleware(['auth', 'role:patient'])->prefix('patient/hep')->name('patient.hep.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Patient\HEPController::class, 'dashboard'])->name('dashboard');
    Route::get('/assignment/{assignment}', [App\Http\Controllers\Patient\HEPController::class, 'show'])->name('show');
    Route::get('/assignment/{assignment}/exercise/{exercise}', [App\Http\Controllers\Patient\HEPController::class, 'showExercise'])->name('exercise');
    Route::post('/assignment/{assignment}/progress', [App\Http\Controllers\Patient\HEPController::class, 'logProgress'])->name('log-progress');
    Route::get('/assignment/{assignment}/progress-data', [App\Http\Controllers\Patient\HEPController::class, 'getProgressData'])->name('progress-data');
});

// AI routes for appointments
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::post('/ai/appointments/{appointment}/suggest', [AppointmentController::class, 'aiSuggest'])->name('ai.appointments.suggest');
    Route::post('/ai/appointments/{appointment}/medical-copilot', [AppointmentController::class, 'aiMedicalCopilot'])->name('ai.appointments.medical-copilot');
    Route::post('/ai/appointments/{appointment}/save-quick-data', [AppointmentController::class, 'saveQuickData'])->name('doctor.appointments.save-quick-data');
    Route::get('/ai/patients/{patient}/ai-analyses', [AppointmentController::class, 'getPatientAIAnalyses'])->name('ai.patients.ai-analyses');
    Route::get('/ai/ai-analyses/{analysis}', [AppointmentController::class, 'showAIAnalysis'])->name('ai.analysis.show');
    Route::post('/ai/appointments/{appointment}/ai-analyses/save', [AppointmentController::class, 'saveAICopilotAnalysis'])->name('ai.appointments.ai-analyses.save');
    Route::post('/ai/ai-analyses/{analysis}/review', [AppointmentController::class, 'reviewAIAnalysis'])->name('ai.analysis.review');
});

// Include AI routes
require __DIR__.'/ai.php';

// Waitlist Routes
Route::middleware(['auth', 'role:patient'])->prefix('patient/waitlist')->name('patient.waitlist.')->group(function () {
    Route::get('/', [App\Http\Controllers\Patient\WaitlistController::class, 'dashboard'])->name('dashboard');
    Route::get('/join', [App\Http\Controllers\Patient\WaitlistController::class, 'join'])->name('join');
    Route::post('/join', [App\Http\Controllers\Patient\WaitlistController::class, 'store'])->name('store');
    Route::get('/status/{waitlist}', [App\Http\Controllers\Patient\WaitlistController::class, 'show'])->name('status');
    Route::get('/position/{waitlist}', [App\Http\Controllers\Patient\WaitlistController::class, 'getPosition'])->name('position');
    Route::post('/accept-offer/{entry}', [App\Http\Controllers\Patient\WaitlistController::class, 'acceptOffer'])->name('accept-offer');
    Route::post('/decline-offer/{entry}', [App\Http\Controllers\Patient\WaitlistController::class, 'declineOffer'])->name('decline-offer');
    Route::delete('/leave/{waitlist}', [App\Http\Controllers\Patient\WaitlistController::class, 'leaveWaitlist'])->name('leave');
    Route::get('/offer/{entry}', [App\Http\Controllers\Patient\WaitlistController::class, 'viewOffer'])->name('offer');
    Route::get('/preferences', [App\Http\Controllers\Patient\WaitlistController::class, 'preferences'])->name('preferences');
    Route::put('/preferences', [App\Http\Controllers\Patient\WaitlistController::class, 'updatePreferences'])->name('preferences.update');
});

Route::middleware(['auth', 'role:doctor'])->prefix('doctor/waitlist')->name('doctor.waitlist.')->group(function () {
    Route::get('/', [App\Http\Controllers\Doctor\WaitlistController::class, 'dashboard'])->name('dashboard');
    Route::get('/manage', [App\Http\Controllers\Doctor\WaitlistController::class, 'manage'])->name('manage');
    Route::get('/manage/export', [App\Http\Controllers\Doctor\WaitlistController::class, 'export'])->name('export');
    Route::get('/analytics', [App\Http\Controllers\Doctor\WaitlistController::class, 'analytics'])->name('analytics');
    Route::get('/patient/{waitlist}', [App\Http\Controllers\Doctor\WaitlistController::class, 'showPatient'])->name('show-patient');
    Route::post('/offer-slot', [App\Http\Controllers\Doctor\WaitlistController::class, 'offerSlot'])->name('offer-slot');
    Route::post('/manual-offer', [App\Http\Controllers\Doctor\WaitlistController::class, 'manualOffer'])->name('manual-offer');
    Route::post('/bulk-operations', [App\Http\Controllers\Doctor\WaitlistController::class, 'bulkOperations'])->name('bulk-operations');
    Route::post('/update-priority/{waitlist}', [App\Http\Controllers\Doctor\WaitlistController::class, 'updatePriority'])->name('update-priority');
    Route::post('/update-status/{waitlist}', [App\Http\Controllers\Doctor\WaitlistController::class, 'updateStatus'])->name('update-status');
    Route::delete('/remove-patient/{waitlist}', [App\Http\Controllers\Doctor\WaitlistController::class, 'removePatient'])->name('remove-patient');
    Route::post('/add-patient', [App\Http\Controllers\Doctor\WaitlistController::class, 'addPatient'])->name('add-patient');
});

// API Routes for Waitlist
Route::middleware(['auth', \App\Http\Middleware\EnsureJsonResponse::class])->group(function () {
    Route::get('/api/patient/waitlist/position/{waitlist}', [App\Http\Controllers\Patient\WaitlistController::class, 'getPosition'])->name('api.patient.waitlist.position');
    Route::get('/api/doctor/waitlist/dashboard', [App\Http\Controllers\Doctor\WaitlistController::class, 'getDashboard'])->name('api.doctor.waitlist.dashboard');
    Route::get('/api/doctor/waitlist/stats', [App\Http\Controllers\Doctor\WaitlistController::class, 'getStats'])->name('api.doctor.waitlist.stats');
    Route::get('/api/doctor/waitlist/patient/{waitlist}', [App\Http\Controllers\Doctor\WaitlistController::class, 'getPatient'])->name('api.doctor.waitlist.patient');
});

// Admin Settings Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings/transcription', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/transcription', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
});

// WebSocket test routes
Route::get('/websocket-test', [App\Http\Controllers\WebSocketController::class, 'testPage'])->name('websocket.test');
Route::post('/send-notification', [App\Http\Controllers\WebSocketController::class, 'sendNotification'])->name('send.notification');
