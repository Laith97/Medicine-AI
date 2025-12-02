<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpAppointmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Appointment $followUpAppointment;
    public Appointment $originalAppointment;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $followUpAppointment, Appointment $originalAppointment)
    {
        $this->followUpAppointment = $followUpAppointment;
        $this->originalAppointment = $originalAppointment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Follow-up Appointment Scheduled - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.follow-up-appointment',
            with: [
                'followUpAppointment' => $this->followUpAppointment,
                'originalAppointment' => $this->originalAppointment,
                'doctor' => $this->followUpAppointment->doctor,
                'patient' => $this->followUpAppointment->patient,
            ],
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