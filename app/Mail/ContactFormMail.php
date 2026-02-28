<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $contactData)
    {
        $this->contactData = $contactData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact Message - ' . $this->contactData['subject'],
            from: new Address(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'), 'MedCura AI Contact'),
            replyTo: [new Address($this->contactData['email'], $this->contactData['name'])],
            using: [
                function ($message) {
                    $message->getHeaders()
                        ->addTextHeader('X-Mailer', 'MedCura AI Contact System')
                        ->addTextHeader('X-Priority', '3')
                        ->addTextHeader('X-Entity-ID', 'MedCura-Contact-' . uniqid());
                    
                    // Set return path properly
                    $message->returnPath(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'));
                },
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'contactName' => $this->contactData['name'],
                'contactEmail' => $this->contactData['email'],
                'contactPhone' => $this->contactData['phone'],
                'contactService' => $this->contactData['service'],
                'contactSubject' => $this->contactData['subject'],
                'messageContent' => $this->contactData['message'],
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
