<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('main');
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

});

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Admin route to view contact submissions
Route::middleware('auth')->group(function () {
    Route::get('/admin/contact-submissions', [ContactController::class, 'adminIndex'])->name('admin.contact-submissions');
    Route::patch('/admin/contact-submissions/{submission}/mark-read', [ContactController::class, 'markAsRead'])->name('admin.contact-submissions.mark-read');
});
Route::get('/about', [UserSettingsController::class, 'about'])->name('about');

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

