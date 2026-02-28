<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use App\Services\EmailService;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'service' => 'nullable|string|max:100',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:2000',
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                // Validation passed, continue with processing
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check the form and try again.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'service' => $request->service ?? 'General Inquiry',
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        try {
            // Store contact submission in database
            ContactSubmission::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'service' => $data['service'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'submitted_at' => now(),
            ]);

            // Send email to specified recipients using EmailService for better reliability
            $recipients = ['malikqattom@gmail.com', 'laythfares99@gmail.com'];
            $emailService = new EmailService();
             
            foreach ($recipients as $recipient) {
                try {
                    $emailService->sendEmail(
                        $recipient,
                        'Contact Message - ' . $data['subject'],
                        'emails.contact',
                        [
                            'contactName' => $data['name'],
                            'contactEmail' => $data['email'],
                            'contactPhone' => $data['phone'],
                            'contactService' => $data['service'],
                            'contactSubject' => $data['subject'],
                            'messageContent' => $data['message'],
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to send email to $recipient: " . $e->getMessage());
                }
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your message! We will get back to you soon.'
                ]);
            } else {
                return redirect()->back()->with('success', 'Thank you for your message! We will get back to you soon.');
            }
        } catch (\Exception $e) {
            \Log::error('Contact form error: ' . $e->getMessage());
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'There was an error processing your message. Please try again later.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Unknown error'
                ], 500);
            } else {
                return redirect()->back()->with('error', 'There was an error processing your message. Please try again later.');
            }
        }
    }

    public function adminIndex()
    {
        $submissions = ContactSubmission::recent()->paginate(20);
        return view('admin.contact-submissions', compact('submissions'));
    }

    public function markAsRead(ContactSubmission $submission)
    {
        $submission->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Submission marked as read');
    }
}