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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('main');
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

});

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Admin route to view contact submissions
Route::middleware('auth')->group(function () {
    Route::get('/admin/contact-submissions', [ContactController::class, 'adminIndex'])->name('admin.contact-submissions');
    Route::patch('/admin/contact-submissions/{submission}/mark-read', [ContactController::class, 'markAsRead'])->name('admin.contact-submissions.mark-read');
});
Route::get('/about', [UserSettingsController::class, 'about'])->name('about');

// Doctor routes
Route::middleware(['auth'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');

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
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', AdminController::class);
    Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::get('/users/{user}/patient-analyses', [AdminController::class, 'userPatientAnalyses'])->name('users.patient-analyses');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

