<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\VoiceAssistantController;
use Illuminate\Support\Facades\Route;

// AI Prescription Suggestion Routes
Route::middleware(['auth', 'sub.user.permissions'])->prefix('ai')->name('ai.')->group(function () {

    // Voice Assistant routes for AI prescription suggestions
    Route::prefix('voice-assistant')->name('voice-assistant.')->group(function () {
        Route::get('/', [VoiceAssistantController::class, 'index'])->name('index');
        Route::get('/history', [VoiceAssistantController::class, 'history'])->name('history');
        Route::get('/{transcription}', [VoiceAssistantController::class, 'show'])->name('show');

        // AJAX routes for jQuery implementation
        Route::post('/start-session', [VoiceAssistantController::class, 'startSession'])->name('start-session');
        Route::post('/stop-session', [VoiceAssistantController::class, 'stopSession'])->name('stop-session');
        Route::post('/handle-transcription', [VoiceAssistantController::class, 'handleTranscription'])->name('handle-transcription');
        Route::post('/process-with-ai', [VoiceAssistantController::class, 'processWithAI'])->name('process-with-ai');
        Route::post('/generate-ai-analysis', [VoiceAssistantController::class, 'generateAIAnalysis'])->name('generate-ai-analysis');
        Route::post('/create-ai-result', [VoiceAssistantController::class, 'createAiAssistantResult'])->name('create-ai-result');
        Route::post('/create-manual-diagnosis', [VoiceAssistantController::class, 'createManualDiagnosis'])->name('create-manual-diagnosis');
        Route::post('/create-new-patient', [VoiceAssistantController::class, 'createNewPatient'])->name('create-new-patient');
        Route::post('/reset-session', [VoiceAssistantController::class, 'resetSession'])->name('reset-session');
    });

    // AI suggestion route for appointments (prescription suggestions)
    Route::post('/appointments/{appointment}/suggest', [AppointmentController::class, 'aiSuggest'])->name('appointments.suggest');
    Route::post('/appointments/test-openai', [AppointmentController::class, 'testOpenAI'])->name('appointments.test-openai');

    // General AI routes that may be used for prescription suggestions
    // Route::get('/ask', [OpenAIController::class, 'showForm'])->name('ask-ai'); // Temporarily disabled
    Route::get('/progress', function () { return view('openai-progress'); })->name('progress');
    Route::post('/respond', [OpenAIController::class, 'getResponse'])->name('respond');
    Route::post('/follow-up', [OpenAIController::class, 'followUp'])->name('follow-up');
    Route::post('/create-manual-diagnosis', [OpenAIController::class, 'createManualDiagnosis'])->name('create-manual-diagnosis');
    Route::post('/patient-summary', [OpenAIController::class, 'generatePatientSummary'])->name('patient-summary');
});