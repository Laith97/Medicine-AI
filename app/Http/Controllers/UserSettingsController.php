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
        return view('about');
    }

}
