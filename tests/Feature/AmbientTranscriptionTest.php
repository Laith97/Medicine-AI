<?php

use App\Models\User;
use App\Models\AmbientRecordingSession;
use App\Services\AmbientRecordingService;
use App\Services\RealTimeAIService;
use App\Services\Fakes\FakeOpenAIClient;
use Illuminate\Support\Facades\Event;
use App\Events\AmbientSessionUpdated;
use App\Events\RealTimeInsightCreated;

it('can start, upload chunks, and complete session with queued final transcription', function () {
    Event::fake([AmbientSessionUpdated::class, RealTimeInsightCreated::class]);

    $doctor = User::factory()->create(['role' => 'doctor']);
    $patient = User::factory()->create(['role' => 'patient']);
    $this->actingAs($doctor);

    // Bind fake client so jobs/services don’t call real network
    app()->instance(\App\Services\OpenAIClient::class, new FakeOpenAIClient());

    // Start session
    $service = app(AmbientRecordingService::class);
    /** @var AmbientRecordingSession $session */
    $session = $service->start($doctor->id, $patient->id, null, 'en', false);

    // Process a chunk using fake AI
    $ai = new RealTimeAIService(new FakeOpenAIClient());
    $chunk = $service->storeChunk($session, random_bytes(1000), 5, now()->toISOString());

    // Run batch job directly (simulate)
    (new App\Jobs\ProcessAmbientChunksBatch($session->id))->handle($ai);

    // Complete session (merge)
    $service->complete($session);

    // Ensure final job dispatchable (we won’t run queue here)
    \App\Jobs\TranscribeFinalAmbientRecording::dispatchSync($session->id);

    Event::assertDispatched(AmbientSessionUpdated::class);
});
