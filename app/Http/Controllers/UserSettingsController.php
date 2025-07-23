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
        $aboutTitle = 'About MedCura AI for Doctors';
        $aboutTagline = 'Empowering doctors to make evidence-based decisions.';
        $features = [
            [
                'icon' => 'fas fa-heartbeat',
                'title' => 'Evidence-Based Guidance',
                'description' => 'Access up-to-date, peer-reviewed recommendations for clinical decisions.'
            ],
            [
                'icon' => 'fas fa-user-md',
                'title' => 'Patient-Centered',
                'description' => 'Focus on what matters most for patient outcomes and safety.',
                'delay' => '200'
            ],
            [
                'icon' => 'fas fa-brain',
                'title' => 'Reduce Unnecessary Care',
                'description' => 'Identify and avoid low-value or unnecessary interventions.',
                'delay' => '400'
            ],
        ];
        $whatWeDoTitle = 'What We Do';
        $whatWeDoDescription = 'MedCura AI for Doctors provides a platform for clinicians to access, share, and discuss best practices, reducing unnecessary procedures and improving patient care.';
        $whatWeDoFeatures = [
            [
                'icon' => 'fas fa-list-ul',
                'description' => 'Curated lists of recommendations for various specialties.'
            ],
            [
                'icon' => 'fas fa-stethoscope',
                'description' => 'Tools to help you make wise choices at the point of care.'
            ],
            [
                'icon' => 'fas fa-comments',
                'description' => 'Community-driven updates and discussion.'
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
