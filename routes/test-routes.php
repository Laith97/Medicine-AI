<?php

// Test routes for sub-user functionality
Route::middleware(['auth'])->group(function () {
    
    // Test sub-user appointments access
    Route::get('/test-appointments-access', function() {
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return 'Please login as sub-user first';
        }
        
        try {
            $user = auth()->user();
            $doctor = $user->getEffectiveDoctor();
            
            if (!$doctor) {
                return 'No effective doctor found';
            }
            
            $appointmentsCount = $doctor->appointments()->count();
            $todayAppointments = $doctor->appointments()
                ->whereDate('appointment_date', today())
                ->count();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sub-user can access appointments!',
                'doctor_id' => $doctor->id,
                'total_appointments' => $appointmentsCount,
                'today_appointments' => $todayAppointments,
                'user_info' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'parent_user_id' => $user->parent_user_id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->name('test.appointments.access');

    // Test sub-user blog access
    Route::get('/test-blog-posts-access', function() {
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return 'Please login as sub-user first';
        }
        
        try {
            $user = auth()->user();
            $doctor = $user->getEffectiveDoctor();
            
            if (!$doctor) {
                return 'No effective doctor found';
            }
            
            $blogPostsCount = $doctor->blogPosts()->count();
            $publishedPosts = $doctor->blogPosts()->where('is_published', true)->count();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sub-user can access blog posts!',
                'doctor_id' => $doctor->id,
                'total_blog_posts' => $blogPostsCount,
                'published_posts' => $publishedPosts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->name('test.blog.posts.access');

    // Test sub-user landing page access
    Route::get('/test-landing-page-access', function() {
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return 'Please login as sub-user first';
        }
        
        try {
            $user = auth()->user();
            $doctor = $user->getEffectiveDoctor();
            
            if (!$doctor) {
                return 'No effective doctor found';
            }
            
            $landingPage = $doctor->landingPage;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sub-user can access landing page!',
                'doctor_id' => $doctor->id,
                'landing_page' => $landingPage ? [
                    'id' => $landingPage->id,
                    'username' => $landingPage->username,
                    'is_published' => $landingPage->is_published,
                    'template' => $landingPage->template,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->name('test.landing.page.access');

    // Test comprehensive data sharing
    Route::get('/test-comprehensive-sharing', function() {
        if (!auth()->check() || !auth()->user()->isSubUser()) {
            return 'Please login as sub-user first';
        }
        
        try {
            $user = auth()->user();
            $doctor = $user->getEffectiveDoctor();
            $doctorUser = $user->getEffectiveDoctorUser();
            
            if (!$doctor || !$doctorUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No effective doctor or doctor user found'
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'All data sharing working perfectly!',
                'sub_user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'parent_doctor_user' => [
                    'id' => $doctorUser->id,
                    'name' => $doctorUser->name,
                    'email' => $doctorUser->email,
                ],
                'doctor_profile' => [
                    'id' => $doctor->id,
                    'bio' => $doctor->bio,
                    'consultation_fee' => $doctor->consultation_fee,
                    'is_active' => $doctor->is_active,
                ],
                'shared_data' => [
                    'appointments' => $doctor->appointments()->count(),
                    'blog_posts' => $doctor->blogPosts()->count(),
                    'reviews' => $doctor->reviews()->count(),
                    'doctor_notes' => $doctorUser->doctorNotes()->count(),
                    'assigned_patients' => $doctorUser->assignedPatients()->count(),
                ],
                'permissions' => $user->permissions->pluck('display_name')->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    })->name('test.comprehensive.sharing');
});