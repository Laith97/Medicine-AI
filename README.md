# Medicine-AI

A Laravel-based web application designed for medical professionals. It integrates with OpenAI's API to provide AI-assisted medical analysis, patient data management, and clinical decision support.

## Ambient Transcription (Ambient Scribing)

This feature captures microphone audio in the browser, streams it to the backend in short chunks, performs real-time transcription and insight extraction, then generates a final transcription after the session ends.

### Architecture Overview
- **Frontend**: Uses `MediaRecorder` to record audio in ~5s chunks and POSTs them to the server.
- **Backend Services**:
  - `AmbientRecordingService`: session lifecycle (start/pause/resume/complete), chunk storage, throttled batch job dispatch.
  - `RealTimeAIService`: transcribes each chunk and extracts real-time insights; broadcasts via private channels.
- **Queue Jobs**:
  - `ProcessAmbientChunksBatch`: processes unprocessed chunks in batches; re-dispatches when more remain.
  - `TranscribeFinalAmbientRecording`: final file transcription and cleanup once the session is stopped.
- **Events**:
  - `AmbientSessionUpdated` (channel: `private-doctor.{doctorId}`, event: `ambient.session.updated`)
  - `RealTimeInsightCreated` (channel: `private-doctor.{doctorId}`, event: `ambient.insight.created`)

### Endpoints
All routes require authentication and appropriate role/middleware.

- `POST /ambient/sessions`
  - Body (JSON): `{ patient_id: number, language?: string, diarization?: boolean }`
  - Response: `{ success: boolean, session_uuid: string, status: 'active' }`

- `POST /ambient/sessions/{uuid}/chunks` (throttled: `ambient-chunks`)
  - Query: `?duration=<seconds>&recorded_at=<ISO8601>`
  - Body: binary audio chunk (e.g., `audio/webm` or `audio/mp4`)
  - Response: `{ success: true, chunk_id: number }`

- `POST /ambient/sessions/{uuid}/pause`
  - Response: `{ success: true, status: 'paused' }`

- `POST /ambient/sessions/{uuid}/resume`
  - Response: `{ success: true, status: 'active' }`

- `POST /ambient/sessions/{uuid}/stop`
  - Response: `{ success: true, status: 'completed', audio_file_path: string, message: string }`
  - Triggers final transcription job.

### Real-time Broadcasts
Subscribe to the doctor’s private channel `doctor.{doctorId}` using Laravel Echo.

- Event: `.ambient.session.updated`
  - Payload types:
    - `{ type: 'transcription', text: string }` — partial transcript updates
    - `{ type: 'final_transcription', text: string }` — final transcript after processing
    - `{ type: 'final_transcription_error', message: string }` — if final transcription fails

- Event: `.ambient.insight.created`
  - Payload: `{ insight: { id: number, type: string, data: object, confidence: number } }`

### Frontend Behavior (reference)
- On Start:
  1. POST `/ambient/sessions` with `patient_id`, optional `language`, and `diarization`.
  2. Begin `MediaRecorder` with codec fallback: `audio/webm;codecs=opus` → `audio/webm` → `audio/mp4`.
  3. On each `dataavailable`, POST the binary chunk to `/ambient/sessions/{uuid}/chunks`.

- On Stop:
  1. POST `/ambient/sessions/{uuid}/stop`.
  2. Show processing indicators until final transcript event arrives.

- Real-time UI:
  - Listen to `.ambient.session.updated` and `.ambient.insight.created` to update the transcript and display insights.

### Operations
- Migrations: `php artisan migrate`
- Queue worker: `php artisan queue:work --queue=processing,default`
- Scheduler: ensure `php artisan schedule:run` executes every minute
- Rate limiting: route `/ambient/sessions/{uuid}/chunks` uses limiter `ambient-chunks` (60/min by user/IP)
- Optional: install `ffmpeg` on PATH to transcode merged audio to `m4a` for better compatibility.

### Testing
- Feature test: `tests/Feature/AmbientTranscriptionTest.php`
- Fake client: `App\Services\Fakes\FakeOpenAIClient` used in tests to avoid real API calls

### Notes
- The backend stores chunks in DB (`ambient_recording_chunks`) and merges to a file at session end (`storage/app/ambient/{uuid}.webm` or `.m4a`).
- The final transcription job broadcasts success/error and may cleanup per-chunk rows after success.
