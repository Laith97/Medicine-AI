<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     */
    public function index()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        $departments = $hospital->departments()->paginate(10);

        return view('hospital-admin.departments.index', compact('departments', 'hospital'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        return view('hospital-admin.departments.create', compact('hospital'));
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $hospital->departments()->create($request->all());

        return redirect()->route('hospital-admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        $user = Auth::user();
        
        if ($department->hospital_id !== $user->hospital_id) {
            abort(403, 'Unauthorized access to this department.');
        }

        // Since department-user relationship was removed, we don't load related data

        return view('hospital-admin.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department)
    {
        $user = Auth::user();
        
        if ($department->hospital_id !== $user->hospital_id) {
            abort(403, 'Unauthorized access to this department.');
        }

        return view('hospital-admin.departments.edit', compact('department'));
    }

    /**
     * Update the specified department in storage.
     */
    public function update(Request $request, Department $department)
    {
        $user = Auth::user();
        
        if ($department->hospital_id !== $user->hospital_id) {
            abort(403, 'Unauthorized access to this department.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $department->update($request->all());

        return redirect()->route('hospital-admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department)
    {
        $user = Auth::user();
        
        if ($department->hospital_id !== $user->hospital_id) {
            abort(403, 'Unauthorized access to this department.');
        }

        // Since department-user relationship was removed, we can safely delete departments

        $departmentName = $department->name;
        $department->delete();

        return redirect()->route('hospital-admin.departments.index')
            ->with('success', "Department '{$departmentName}' deleted successfully.");
    }
}