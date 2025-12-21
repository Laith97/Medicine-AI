<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MonitoringDashboardController extends Controller
{
    public function index()
    {
        return view('doctor.monitoring.dashboard');
    }
}
