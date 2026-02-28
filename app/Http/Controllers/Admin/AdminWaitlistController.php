<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Models\User;
use Illuminate\Http\Request;

class AdminWaitlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display the admin waitlist dashboard
     */
    public function dashboard(Request $request)
    {
        return view('admin.waitlist.dashboard');
    }

    /**
     * Display the admin waitlist analytics
     */
    public function analytics(Request $request)
    {
        return view('admin.waitlist.analytics');
    }
}