<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
                'specialty' => $specialty
            ]
        );

        return back()->with('status', 'Settings updated!');
    }



    public function about(){
        $aboutTitle = 'About MedCura AI - Complete Healthcare Platform';
        $aboutTagline = 'Revolutionizing healthcare with AI-powered diagnosis, patient management, and professional growth tools.';
        $features = [
            [
                'icon' => 'fas fa-brain',
                'title' => 'AI-Powered Diagnosis',
                'description' => 'Advanced GPT-4 powered diagnostic assistance with voice transcription, manual diagnosis creation, and intelligent follow-up questions for comprehensive patient care.'
            ],
            [
                'icon' => 'fas fa-microphone',
                'title' => 'Voice Assistant',
                'description' => 'Hands-free consultation documentation with real-time speech-to-text, automatic chart filling, and AI-powered clinical analysis.',
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
        $whatWeDoDescription = 'MedCura AI provides a comprehensive platform that combines artificial intelligence, patient management, and professional growth tools to transform modern medical practices. From AI-powered diagnosis to automated patient communication, we help healthcare professionals deliver better care while growing their practice.';
        $whatWeDoFeatures = [
            [
                'icon' => 'fas fa-robot',
                'description' => 'AI Assistant with GPT-4 powered analysis for instant diagnostic insights and clinical recommendations.'
            ],
            [
                'icon' => 'fas fa-microphone',
                'description' => 'Voice Assistant for hands-free consultation documentation with automatic transcription and chart filling.'
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'description' => 'Complete appointment management system with online booking, availability settings, and automated reminders.'
            ],
            [
                'icon' => 'fas fa-file-medical',
                'description' => 'Manual diagnosis system with voice input, patient notifications, and AI-powered follow-up questions.'
            ],
            [
                'icon' => 'fas fa-comments',
                'description' => 'Real-time chat system with patients, automated responses, and comprehensive message management.'
            ],
            [
                'icon' => 'fas fa-blog',
                'description' => 'Professional blog management with SEO optimization, featured images, and reading time calculation.'
            ],
            [
                'icon' => 'fas fa-star',
                'description' => 'Patient review and testimonial system with verification badges and case study management.'
            ],
            [
                'icon' => 'fas fa-chart-bar',
                'description' => 'Advanced analytics with visit tracking, performance metrics, and comprehensive reporting tools.'
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
