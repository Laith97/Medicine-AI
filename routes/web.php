<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $showPricingSection = SystemSetting::get('show_pricing_section', true);
    return view('main', compact('showPricingSection'));
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

