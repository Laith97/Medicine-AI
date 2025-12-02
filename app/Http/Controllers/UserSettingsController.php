<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserSettingsController extends Controller
{

    public function index()
    {
        $setting = auth()->user()->setting;
        return view('settings', compact('setting'));
    }



    public function update(Request $request)
    {
        $request->validate([
            'criterion' => ['required', Rule::in(['NICE', 'CDC', 'Mayo Clinic'])],
            'specialty' => ['nullable', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
            'notification_volume' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        // Determine the final specialty value
        $specialty = $request->specialty;

        // If specialty is empty but custom_specialty is provided, use custom_specialty
        if (empty($specialty) && !empty($request->custom_specialty)) {
            $specialty = trim($request->custom_specialty);
        }

        auth()->user()->setting()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'criterion' => $request->criterion,
                'specialty' => $specialty,
                'notification_volume' => $request->notification_volume ?? 0.3
            ]
        );

        return back()->with('status', 'Settings updated!');
    }

    public function getSettings()
    {
        $setting = auth()->user()->setting;

        return response()->json([
            'criterion' => $setting->criterion ?? 'CDC',
            'specialty' => $setting->specialty ?? null,
            'notification_volume' => $setting->notification_volume ?? 0.3
        ]);
    }



    public function about(){
        $aboutTitle = 'About MedCura - Complete Healthcare Platform';
        $aboutTagline = 'Revolutionizing healthcare with comprehensive diagnosis, patient management, and professional growth tools.';
        $features = [
            [
                'icon' => 'fas fa-stethoscope',
                'title' => 'Comprehensive Diagnosis',
                'description' => 'Complete diagnostic system with manual entry, voice transcription, follow-up questions, and comprehensive patient care documentation.'
            ],
            [
                'icon' => 'fas fa-microphone',
                'title' => 'Voice Assistant',
                'description' => 'Hands-free consultation documentation with real-time speech-to-text, automatic chart filling, and clinical note generation.',
                'delay' => '200'
            ],
            [
                'icon' => 'fas fa-users',
                'title' => 'Patient Management',
                'description' => 'Complete patient lifecycle management with appointment booking, case tracking, automated notifications, and review systems.',
                'delay' => '400'
            ],
            [
                'icon' => 'fas fa-globe',
                'title' => 'Online Presence',
                'description' => 'Professional landing pages, blog management, live chat widgets, and patient testimonials to grow your practice online.',
                'delay' => '600'
            ],
            [
                'icon' => 'fas fa-shield-alt',
                'title' => 'HIPAA Compliant',
                'description' => 'Enterprise-grade encryption, secure data handling, role-based access control, and comprehensive audit trails.',
                'delay' => '800'
            ],
            [
                'icon' => 'fas fa-mobile-alt',
                'title' => 'Multi-Channel Communication',
                'description' => 'Automated email and SMS notifications, real-time chat, appointment reminders, and subscription management.',
                'delay' => '1000'
            ],
        ];
        $whatWeDoTitle = 'Complete Healthcare Solution';
        $whatWeDoDescription = 'MedCura provides a comprehensive platform that combines advanced technology, patient management, and professional growth tools to transform modern medical practices. From comprehensive diagnosis to automated patient communication, we help healthcare professionals deliver better care while growing their practice.';
        $whatWeDoFeatures = [
            [
                'icon' => 'fas fa-stethoscope',
                'description' => 'Comprehensive diagnosis system with manual entry, voice transcription, and intelligent follow-up questions for thorough patient care.'
            ],
            [
                'icon' => 'fas fa-microphone',
                'description' => 'Voice Assistant for hands-free consultation documentation with real-time speech-to-text and clinical note generation.'
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'description' => 'Smart scheduling system with appointment booking, calendar integration, and automated patient reminders.'
            ],
            [
                'icon' => 'fas fa-users',
                'description' => 'Complete patient management with lifecycle tracking, automated notifications, and comprehensive case management.'
            ],
            [
                'icon' => 'fas fa-mobile-alt',
                'description' => 'Multi-channel communication through SMS, email campaigns, live chat, and automated appointment reminders.'
            ],
            [
                'icon' => 'fas fa-chart-line',
                'description' => 'Practice analytics dashboard with patient demographics, revenue tracking, and performance insights for data-driven decisions.'
            ],
            [
                'icon' => 'fas fa-users-cog',
                'description' => 'Staff management with role-based permissions, sub-user accounts, and collaborative practice management tools.'
            ],
            [
                'icon' => 'fas fa-globe',
                'description' => 'Professional online presence with customizable landing pages, blog management, and patient testimonial systems.'
            ],
        ];
        $team = [
            [
                'image' => 'demos/medical/images/doctors/1.jpg',
                'name' => 'Dr. John Doe',
                'specialty' => 'Internal Medicine'
            ],
            [
                'image' => 'demos/medical/images/doctors/2.jpg',
                'name' => 'Dr. Jane Smith',
                'specialty' => 'Family Medicine'
            ],
            [
                'image' => 'demos/medical/images/doctors/3.jpg',
                'name' => 'Dr. Alex Brown',
                'specialty' => 'Pediatrics'
            ],
        ];
        return view('about', compact('aboutTitle', 'aboutTagline', 'features', 'whatWeDoTitle', 'whatWeDoDescription', 'whatWeDoFeatures', 'team'));
    }

}
