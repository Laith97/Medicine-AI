<?php

use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('main');
});
Route::middleware('auth')->group(function () {
    Route::get('/ask-openai', [OpenAIController::class, 'showForm'])->name('ask-openai');
    Route::post('/openai/respond', [OpenAIController::class, 'getResponse'])->name('openai.respond');
   
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings');
    Route::put('/user/settings/update', [UserSettingsController::class, 'update'])->name('settings.update');
    Route::get('/cases', [OpenAIController::class, 'getCases'])->name('cases');
    Route::get('/dashboard', [OpenAIController::class, 'dashboard'])->name('dashboard');

});

Route::get('/contact', [UserSettingsController::class, 'contact'])->name('contact');
Route::get('/about', [UserSettingsController::class, 'about'])->name('about');




Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
