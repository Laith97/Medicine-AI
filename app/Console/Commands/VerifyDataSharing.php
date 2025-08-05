<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class VerifyDataSharing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:data-sharing {doctor_email} {sub_user_email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that doctor and sub-user see the same data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doctorEmail = $this->argument('doctor_email');
        $subUserEmail = $this->argument('sub_user_email');
        
        $doctor = User::where('email', $doctorEmail)->first();
        $subUser = User::where('email', $subUserEmail)->first();
        
        if (!$doctor || !$subUser) {
            $this->error("One or both users not found.");
            return 1;
        }

        if (!$doctor->isDoctor()) {
            $this->error("First user is not a doctor.");
            return 1;
        }

        if (!$subUser->isSubUser()) {
            $this->error("Second user is not a sub-user.");
            return 1;
        }

        $this->info("🔍 Verifying data sharing between:");
        $this->info("   Doctor: {$doctor->name} ({$doctor->email})");
        $this->info("   Sub-user: {$subUser->name} ({$subUser->email})");

        // Test 1: Appointments
        $this->info("\n=== 1. Testing Appointments ===");
        
        Auth::login($doctor);
        $doctorAppointments = $doctor->doctor->appointments()->count();
        Auth::logout();
        
        Auth::login($subUser);
        $subUserAppointments = $subUser->getEffectiveDoctor()->appointments()->count();
        Auth::logout();
        
        if ($doctorAppointments === $subUserAppointments) {
            $this->info("✅ Appointments match: {$doctorAppointments}");
        } else {
            $this->error("❌ Appointments don't match: Doctor({$doctorAppointments}) vs Sub-user({$subUserAppointments})");
        }

        // Test 2: Blog Posts
        $this->info("\n=== 2. Testing Blog Posts ===");
        
        Auth::login($doctor);
        $doctorBlogs = $doctor->doctor->blogPosts()->count();
        Auth::logout();
        
        Auth::login($subUser);
        $subUserBlogs = $subUser->getEffectiveDoctor()->blogPosts()->count();
        Auth::logout();
        
        if ($doctorBlogs === $subUserBlogs) {
            $this->info("✅ Blog posts match: {$doctorBlogs}");
        } else {
            $this->error("❌ Blog posts don't match: Doctor({$doctorBlogs}) vs Sub-user({$subUserBlogs})");
        }

        // Test 3: Reviews
        $this->info("\n=== 3. Testing Reviews ===");
        
        Auth::login($doctor);
        $doctorReviews = $doctor->doctor->reviews()->count();
        Auth::logout();
        
        Auth::login($subUser);
        $subUserReviews = $subUser->getEffectiveDoctor()->reviews()->count();
        Auth::logout();
        
        if ($doctorReviews === $subUserReviews) {
            $this->info("✅ Reviews match: {$doctorReviews}");
        } else {
            $this->error("❌ Reviews don't match: Doctor({$doctorReviews}) vs Sub-user({$subUserReviews})");
        }

        // Test 4: Doctor Notes
        $this->info("\n=== 4. Testing Doctor Notes ===");
        
        Auth::login($doctor);
        $doctorNotes = $doctor->doctorNotes()->count();
        Auth::logout();
        
        Auth::login($subUser);
        $subUserNotes = $subUser->getEffectiveDoctorUser()->doctorNotes()->count();
        Auth::logout();
        
        if ($doctorNotes === $subUserNotes) {
            $this->info("✅ Doctor notes match: {$doctorNotes}");
        } else {
            $this->error("❌ Doctor notes don't match: Doctor({$doctorNotes}) vs Sub-user({$subUserNotes})");
        }

        // Test 5: Assigned Patients
        $this->info("\n=== 5. Testing Assigned Patients ===");
        
        Auth::login($doctor);
        $doctorPatients = $doctor->assignedPatients()->count();
        Auth::logout();
        
        Auth::login($subUser);
        $subUserPatients = $subUser->getEffectiveAssignedPatients()->count();
        Auth::logout();
        
        if ($doctorPatients === $subUserPatients) {
            $this->info("✅ Assigned patients match: {$doctorPatients}");
        } else {
            $this->error("❌ Assigned patients don't match: Doctor({$doctorPatients}) vs Sub-user({$subUserPatients})");
        }

        // Test 6: Landing Page
        $this->info("\n=== 6. Testing Landing Page ===");
        
        Auth::login($doctor);
        $doctorLandingPage = $doctor->doctor->landingPage ? $doctor->doctor->landingPage->id : null;
        Auth::logout();
        
        Auth::login($subUser);
        $subUserLandingPage = $subUser->getEffectiveDoctor()->landingPage ? $subUser->getEffectiveDoctor()->landingPage->id : null;
        Auth::logout();
        
        if ($doctorLandingPage === $subUserLandingPage) {
            $this->info("✅ Landing page match: " . ($doctorLandingPage ? "ID {$doctorLandingPage}" : "None"));
        } else {
            $this->error("❌ Landing page don't match: Doctor({$doctorLandingPage}) vs Sub-user({$subUserLandingPage})");
        }

        $this->info("\n🎯 Data sharing verification completed!");
        
        return 0;
    }
}