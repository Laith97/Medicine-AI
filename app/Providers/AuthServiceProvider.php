<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\DoctorNote;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Policies\DoctorNotePolicy;
use App\Policies\HepProgramPolicy;
use App\Policies\HepAssignmentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        DoctorNote::class => DoctorNotePolicy::class,
        HepProgram::class => HepProgramPolicy::class,
        HepAssignment::class => HepAssignmentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
