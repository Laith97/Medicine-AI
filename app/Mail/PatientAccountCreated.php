<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Diagnosis;

class PatientAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $patient;
    public $doctor;
    public $diagnosis;
    public $tempPassword;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $patient, User $doctor, Diagnosis $diagnosis, string $tempPassword)
    {
        $this->patient = $patient;
        $this->doctor = $doctor;
        $this->diagnosis = $diagnosis;
        $this->tempPassword = $tempPassword;
        $this->loginUrl = route('login');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Medical Account Has Been Created - Login Credentials Inside',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.patient-account-created',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
