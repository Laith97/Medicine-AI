<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestDropdownController extends Controller
{
    /**
     * Display a test page for dropdown functionality
     */
    public function index()
    {
        return view('test-dropdown', [
            'user' => Auth::user(),
            'authGuard' => Auth::guard(),
            'isAdmin' => Auth::guard('admin')->check(),
            'isDoctor' => false,
            'isMainUser' => false,
        ]);
    }
}
