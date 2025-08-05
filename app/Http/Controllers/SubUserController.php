<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class SubUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            
            // Only main doctor users can manage sub-users
            if (!$user->isDoctor() || $user->isSubUser()) {
                abort(403, 'Only main doctor accounts can manage sub-users.');
            }
            
            // Ensure the user has an active doctor profile
            if (!$user->hasActiveDoctorProfile()) {
                abort(403, 'Active doctor profile required to manage sub-users.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display a listing of sub-users
     */
    public function index()
    {
        $subUsers = auth()->user()->subUsers()->with('permissions')->get();
        
        return view('sub-users.index', compact('subUsers'));
    }

    /**
     * Show the form for creating a new sub-user
     */
    public function create()
    {
        $availablePermissions = Permission::getAvailableForSubUsers()
            ->groupBy('category');
        
        return view('sub-users.create', compact('availablePermissions'));
    }

    /**
     * Store a newly created sub-user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'sub_user_role' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($request) {
            // Create the sub-user
            $subUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'doctor', // Sub-users inherit the parent's role for system compatibility
                'parent_user_id' => auth()->id(),
                'sub_user_role' => $request->sub_user_role,
                'is_sub_user' => true,
            ]);

            // Assign permissions
            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)
                    ->where('is_restricted', false) // Ensure no restricted permissions
                    ->get();

                foreach ($permissions as $permission) {
                    $subUser->grantPermission($permission, auth()->user());
                }
            }
        });

        return redirect()->route('sub-users.index')
            ->with('success', 'Sub-user created successfully.');
    }

    /**
     * Display the specified sub-user
     */
    public function show(User $subUser)
    {
        $this->authorizeSubUser($subUser);
        
        $subUser->load('permissions');
        
        return view('sub-users.show', compact('subUser'));
    }

    /**
     * Show the form for editing the specified sub-user
     */
    public function edit(User $subUser)
    {
        $this->authorizeSubUser($subUser);
        
        $availablePermissions = Permission::getAvailableForSubUsers()
            ->groupBy('category');
        
        $subUser->load('permissions');
        
        return view('sub-users.edit', compact('subUser', 'availablePermissions'));
    }

    /**
     * Update the specified sub-user
     */
    public function update(Request $request, User $subUser)
    {
        $this->authorizeSubUser($subUser);
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $subUser->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'sub_user_role' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($request, $subUser) {
            // Update basic info
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'sub_user_role' => $request->sub_user_role,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $subUser->update($updateData);

            // Update permissions
            $subUser->permissions()->detach(); // Remove all current permissions

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)
                    ->where('is_restricted', false) // Ensure no restricted permissions
                    ->get();

                foreach ($permissions as $permission) {
                    $subUser->grantPermission($permission, auth()->user());
                }
            }
        });

        return redirect()->route('sub-users.index')
            ->with('success', 'Sub-user updated successfully.');
    }

    /**
     * Remove the specified sub-user
     */
    public function destroy(User $subUser)
    {
        $this->authorizeSubUser($subUser);
        
        $subUser->delete();
        
        return redirect()->route('sub-users.index')
            ->with('success', 'Sub-user deleted successfully.');
    }

    /**
     * Toggle sub-user active status
     */
    public function toggleStatus(User $subUser)
    {
        $this->authorizeSubUser($subUser);
        
        // We can use a custom field for this or just delete/restore
        // For now, we'll implement a simple active/inactive toggle
        
        return redirect()->route('sub-users.index')
            ->with('success', 'Sub-user status updated successfully.');
    }

    /**
     * Ensure the sub-user belongs to the authenticated user
     */
    private function authorizeSubUser(User $subUser)
    {
        if (!$subUser->is_sub_user || $subUser->parent_user_id !== auth()->id()) {
            abort(404, 'Sub-user not found.');
        }
    }
}