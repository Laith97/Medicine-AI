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
    ]);

    auth()->user()->setting()->updateOrCreate(
        ['user_id' => auth()->id()],
        ['criterion' => $request->criterion]
    );

    return back()->with('status', 'Settings updated!');
    }

    public function contact(){
        return view('contact');
    }

    public function about(){
        $aboutTitle = 'About Choose Wisely for Doctors';
        $aboutTagline = 'Empowering doctors to make evidence-based decisions.';
        $features = [
            [
                'icon' => 'icon-medical-i-cardiology',
                'title' => 'Evidence-Based Guidance',
                'description' => 'Access up-to-date, peer-reviewed recommendations for clinical decisions.'
            ],
            [
                'icon' => 'icon-medical-i-social-services',
                'title' => 'Patient-Centered',
                'description' => 'Focus on what matters most for patient outcomes and safety.',
                'delay' => '200'
            ],
            [
                'icon' => 'icon-medical-i-neurology',
                'title' => 'Reduce Unnecessary Care',
                'description' => 'Identify and avoid low-value or unnecessary interventions.',
                'delay' => '400'
            ],
        ];
        $whatWeDoTitle = 'What We Do';
        $whatWeDoDescription = 'Choose Wisely for Doctors provides a platform for clinicians to access, share, and discuss best practices, reducing unnecessary procedures and improving patient care.';
        $whatWeDoFeatures = [
            [
                'icon' => 'icon-medical-i-womens-health',
                'description' => 'Curated lists of recommendations for various specialties.'
            ],
            [
                'icon' => 'icon-medical-i-ultrasound',
                'description' => 'Tools to help you make wise choices at the point of care.'
            ],
            [
                'icon' => 'icon-medical-i-cath-lab',
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
