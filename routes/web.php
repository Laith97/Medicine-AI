<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DiagnosisController;
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
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $showPricingSection = SystemSetting::get('show_pricing_section', true);
    return view('main', compact('showPricingSection'));
});

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
    Route::get('/ask-ai', [OpenAIController::class, 'showForm'])->name('ask-ai');
    Route::post('/openai/respond', [OpenAIController::class, 'getResponse'])->name('openai.respond');
    Route::post('/openai/follow-up', [OpenAIController::class, 'followUp'])->name('openai.follow-up');
    Route::post('/openai/create-manual-diagnosis', [OpenAIController::class, 'createManualDiagnosis'])->name('openai.create-manual-diagnosis');
    Route::post('/patient/summary', [OpenAIController::class, 'generatePatientSummary'])->name('patient.summary');

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

    // Voice Assistant routes
    Route::prefix('voice-assistant')->name('voice-assistant.')->group(function () {
        Route::get('/', [App\Http\Controllers\VoiceAssistantController::class, 'index'])->name('index');
        Route::get('/history', [App\Http\Controllers\VoiceAssistantController::class, 'history'])->name('history');
        Route::get('/{transcription}', [App\Http\Controllers\VoiceAssistantController::class, 'show'])->name('show');

        // AJAX routes for jQuery implementation
        Route::post('/start-session', [App\Http\Controllers\VoiceAssistantController::class, 'startSession'])->name('start-session');
        Route::post('/stop-session', [App\Http\Controllers\VoiceAssistantController::class, 'stopSession'])->name('stop-session');
        Route::post('/handle-transcription', [App\Http\Controllers\VoiceAssistantController::class, 'handleTranscription'])->name('handle-transcription');
        Route::post('/process-with-ai', [App\Http\Controllers\VoiceAssistantController::class, 'processWithAI'])->name('process-with-ai');
        Route::post('/generate-ai-analysis', [App\Http\Controllers\VoiceAssistantController::class, 'generateAIAnalysis'])->name('generate-ai-analysis');
        Route::post('/create-ai-result', [App\Http\Controllers\VoiceAssistantController::class, 'createAiAssistantResult'])->name('create-ai-result');
        Route::post('/create-manual-diagnosis', [App\Http\Controllers\VoiceAssistantController::class, 'createManualDiagnosis'])->name('create-manual-diagnosis');
        Route::post('/create-new-patient', [App\Http\Controllers\VoiceAssistantController::class, 'createNewPatient'])->name('create-new-patient');
        Route::post('/reset-session', [App\Http\Controllers\VoiceAssistantController::class, 'resetSession'])->name('reset-session');
    });

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
            Route::get('/', [DiagnosisController::class, 'index'])->name('index');
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
        $user = auth()->user();
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
                'ask-ai' => $user->canAccessRoute('ask-ai'),
                'voice-assistant.index' => $user->canAccessRoute('voice-assistant.index'),
                'diagnosis.index' => $user->canAccessRoute('diagnosis.index'),
                'cases' => $user->canAccessRoute('cases'),
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
        $user = auth()->user();

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
        if (!auth()->check()) {
            return 'Not logged in';
        }

        $user = auth()->user();
        return "Hello {$user->name}! You are logged in as a " . ($user->isSubUser() ? 'sub-user' : 'main user');
    })->name('simple.test');

    // Sub-user success page
    Route::get('/sub-user-success', function() {
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return redirect()->route('dashboard');
        }
        return view('sub-user-success');
    })->name('sub-user.success');

    // Test dashboard access for sub-users
    Route::get('/test-dashboard-access', function() {
        if (!auth()->check()) {
            return 'Please login first';
        }

        $user = auth()->user();

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
                    'cases' => $user->canAccessRoute('cases'),
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
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return 'Please login as sub-user first';
        }

        try {
            $controller = new \App\Http\Controllers\Doctor\BlogController();
            $doctor = auth()->user()->getEffectiveDoctor();

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
        $user = auth()->user();
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

    // Test route to verify restriction system
    Route::get('/test/restriction-status', function() {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        $setting = $user->monthlyInvoiceSetting;
        $testRoutes = ['ask-ai', 'cases', 'dashboard', 'appointments', 'reviews', 'settings', 'profile.edit'];

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

    // Doctor Notes routes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'store'])->name('store');
        Route::get('/{note}', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'show'])->name('show');
        Route::get('/{note}/edit', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'edit'])->name('edit');
        Route::put('/{note}', [App\Http\Controllers\Doctor\DoctorNotesController::class, 'update'])->name('update');
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
});

// Public Doctor Landing Pages (must be after doctor middleware group to avoid conflicts)
Route::get('/doctor/{username}', [PublicLandingPageController::class, 'show'])->name('doctor.landing');
Route::get('/doctor/{username}/blogs', [PublicLandingPageController::class, 'showBlogs'])->name('doctor.blogs');
Route::get('/doctor/{username}/blog/{slug}', [PublicLandingPageController::class, 'showBlogPost'])->name('doctor.blog.post');

// Doctor Landing Page Management Routes
Route::prefix('doctor/landing-page')->name('doctor.landing-page.')->group(function () {
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
});

// Public Chat Routes
Route::post('/doctor/{username}/chat/init', [PublicChatController::class, 'initializeChat'])->name('doctor.public-chat.init');
Route::post('/doctor/{username}/chat/send', [PublicChatController::class, 'sendMessage'])->name('doctor.public-chat.send');
Route::get('/doctor/{username}/chat/history', [PublicChatController::class, 'getChatHistory'])->name('doctor.public-chat.history');
Route::get('/doctor/{username}/chat/check-new', [PublicChatController::class, 'checkNewMessages'])->name('doctor.public-chat.check-new');

// Public Testimonials API
Route::get('/doctor/{username}/testimonials', [TestimonialController::class, 'getPublicTestimonials'])->name('doctor.testimonials.public');

// Stripe webhook (outside auth middleware)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');

// Include test routes for sub-user functionality
require __DIR__.'/test-routes.php';










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

// Debug route to test if routes are working
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

// Security dashboard routes
Route::middleware('auth:admin')->prefix('security')->name('security.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Security\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/audit-logs/{auditLog}', [App\Http\Controllers\Security\DashboardController::class, 'show'])->name('audit-logs.show');
    Route::get('/export', [App\Http\Controllers\Security\DashboardController::class, 'export'])->name('export');
});

require __DIR__.'/auth.php';
