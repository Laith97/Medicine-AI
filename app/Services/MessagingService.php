<?php

namespace App\Services;

use App\Models\AiMessageSuggestion;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageThread;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class MessagingService
{
    private const MODEL = 'gpt-4o-mini';
    private const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    /**
     * Get eligible doctors for a patient (doctors with appointments or diagnoses).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getEligibleDoctorsForPatient(int $patientId): \Illuminate\Support\Collection
    {
        $doctorIdsFromAppointments = \App\Models\Appointment::where('patient_id', $patientId)
            ->pluck('doctor_id')
            ->filter()
            ->unique();

        $doctorIdsFromDiagnoses = \App\Models\Diagnosis::where('patient_id', $patientId)
            ->pluck('doctor_id')
            ->filter()
            ->unique();

        $allDoctorIds = $doctorIdsFromAppointments->merge($doctorIdsFromDiagnoses)->unique();

        return User::whereIn('id', $allDoctorIds)
            ->where('role', 'doctor')
            ->get();
    }

    /**
     * Create a new message thread with an initial message.
     */
    public function createThread(User $patient, User $doctor, string $subject, string $body, ?int $diagnosisId = null, ?array $attachments = []): MessageThread
    {
        $type = $diagnosisId ? 'follow_up' : 'general';

        $thread = MessageThread::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'type' => $type,
            'diagnosis_id' => $diagnosisId,
            'subject' => $subject,
            'last_message_at' => now(),
        ]);

        $message = $this->addMessage($thread, $patient, 'patient', $body, $attachments);

        // Notify doctor of new thread
        $doctor->notify(new NewMessageNotification($thread, $patient));

        return $thread;
    }

    /**
     * Add a message to a thread.
     */
    public function addMessage(MessageThread $thread, User $sender, string $senderType, string $body, ?array $attachments = [], ?AiMessageSuggestion $aiSuggestion = null): Message
    {
        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_type' => $senderType,
            'sender_id' => $sender->id,
            'body' => $body,
            'ai_suggestion_id' => $aiSuggestion?->id,
            'is_sent' => true,
        ]);

        $thread->update(['last_message_at' => now()]);

        if ($attachments) {
            $this->storeAttachments($message, $attachments);
        }

        // Notify patient when doctor replies
        if ($senderType === 'doctor') {
            $thread->patient->notify(new NewMessageNotification($thread, $sender));
        }

        return $message;
    }

    /**
     * Store file attachments for a message.
     *
     * @param UploadedFile[] $files
     * @throws \InvalidArgumentException
     */
    public function storeAttachments(Message $message, array $files): void
    {
        foreach ($files as $file) {
            if ($file->getSize() > self::MAX_ATTACHMENT_SIZE) {
                throw new \InvalidArgumentException("File {$file->getClientOriginalName()} exceeds 10MB limit.");
            }

            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                throw new \InvalidArgumentException("File type {$file->getMimeType()} is not allowed.");
            }

            $path = $file->store('', 'message_attachments');

            MessageAttachment::create([
                'message_id' => $message->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    /**
     * Generate an AI reply suggestion for the doctor.
     */
    public function generateAiSuggestion(MessageThread $thread, User $doctor): ?AiMessageSuggestion
    {
        try {
            $recentMessages = $thread->messages()->orderBy('created_at', 'desc')->take(5)->get()->reverse()->values();
            $diagnosis = $thread->diagnosis;

            $prompt = $this->buildAiPrompt($thread, $recentMessages, $diagnosis);

            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a medical assistant helping a doctor compose a professional, empathetic reply to a patient. Keep the reply concise (2-4 sentences), warm, and professional. Do not invent medical details.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.5,
            ]);

            $suggestedReply = trim($response->choices[0]->message->content);

            if (empty($suggestedReply)) {
                return null;
            }

            return AiMessageSuggestion::create([
                'thread_id' => $thread->id,
                'doctor_id' => $doctor->id,
                'suggested_reply' => $suggestedReply,
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            Log::error('AI message suggestion failed', [
                'thread_id' => $thread->id,
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sanitize user-supplied text to prevent prompt injection.
     * Strips code fences and control characters that could manipulate the AI prompt.
     */
    private function sanitizeForPrompt(string $text): string
    {
        // Strip markdown code fences (prevents ```json injection)
        $text = preg_replace('/`{3,}/', '', $text);
        // Strip single code fences (prevents `inline code`)
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        // Strip control chars except newlines/tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        // Collapse excessive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Build the prompt for AI reply generation.
     */
    private function buildAiPrompt(MessageThread $thread, $recentMessages, $diagnosis): string
    {
        $prompt = "Patient Subject: {$thread->subject}\n\n";
        $prompt .= "Conversation History:\n";

        foreach ($recentMessages as $msg) {
            $sender = $msg->sender_type === 'patient' ? 'Patient' : 'Doctor';
            $body = $this->sanitizeForPrompt($msg->body);
            $prompt .= "[{$sender}] {$body}\n";
        }

        if ($diagnosis) {
            $diagnosisText = $this->sanitizeForPrompt($diagnosis->diagnosis_text);
            $prompt .= "\nRelevant Diagnosis: {$diagnosisText}\n";
        }

        $prompt .= "\nPlease write a professional reply from the doctor to the patient.";

        return $prompt;
    }

    /**
     * Approve an AI suggestion and send it as a doctor message.
     */
    public function approveSuggestion(AiMessageSuggestion $suggestion, User $doctor): Message
    {
        $suggestion->update(['status' => 'approved']);

        return $this->addMessage($suggestion->thread, $doctor, 'doctor', $suggestion->suggested_reply);
    }

    /**
     * Reject an AI suggestion.
     */
    public function rejectSuggestion(AiMessageSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'rejected']);
    }

    /**
     * Get a file for download (with authorization check).
     */
    public function getAttachment(MessageAttachment $attachment, User $user): ?string
    {
        $disk = Storage::disk('message_attachments');

        if (!$disk->exists($attachment->file_path)) {
            return null;
        }

        $absolutePath = $disk->path($attachment->file_path);

        // Prevent path traversal: resolved path must be within the attachments directory
        $basePath = realpath($disk->path(''));
        $resolvedPath = realpath($absolutePath);

        if ($resolvedPath === false || !str_starts_with($resolvedPath, $basePath)) {
            return null;
        }

        return $resolvedPath;
    }
}
