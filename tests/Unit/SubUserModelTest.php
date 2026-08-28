<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Permission;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubUserModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $specialty = Specialty::factory()->create();
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id, 'specialty_id' => $specialty->id, 'is_active' => true]);
    }

    private function makePermission(array $attrs = []): Permission
    {
        return Permission::create(array_merge([
            'name' => 'test_perm_' . uniqid(),
            'display_name' => 'Test Perm',
            'route_pattern' => 'doctor.appointments.*',
            'category' => 'appointments',
            'is_restricted' => false,
            'sort_order' => 1,
        ], $attrs));
    }

    public function test_is_sub_user_flags(): void
    {
        $sub = User::factory()->create(['role' => 'doctor', 'is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id]);
        $main = User::factory()->create(['role' => 'doctor', 'is_sub_user' => false]);

        $this->assertTrue($sub->isSubUser());
        $this->assertFalse($sub->isMainUser());
        $this->assertFalse($main->isSubUser());
        $this->assertTrue($main->isMainUser());
    }

    public function test_parent_sub_users_relationship(): void
    {
        $sub1 = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        $sub2 = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        User::factory()->create(['is_sub_user' => true, 'parent_user_id' => User::factory()->create(['role'=>'doctor'])->id, 'role'=>'doctor']);

        $this->assertCount(2, $this->doctorUser->subUsers()->get());
        $this->assertEquals($this->doctorUser->id, $sub1->parentUser->id);
    }

    public function test_has_permission_main_user_has_all_non_restricted(): void
    {
        $this->makePermission(['name' => 'dashboard', 'route_pattern' => 'dashboard', 'is_restricted' => false]);
        $this->makePermission(['name' => 'appointments', 'route_pattern' => 'doctor.appointments.*', 'is_restricted' => false]);

        $this->assertTrue($this->doctorUser->hasPermission('dashboard'));
        $this->assertTrue($this->doctorUser->hasPermission('appointments'));
        $this->assertTrue($this->doctorUser->hasPermission('nonexistent')); // main user fallback returns true for non-existent? check code: returns true if isMainUser and not restricted -> true
    }

    public function test_has_permission_restricted_denied_for_non_doctor_main(): void
    {
        $restricted = $this->makePermission(['name' => 'diagnosis', 'route_pattern' => 'diagnosis.*', 'is_restricted' => true]);
        $patient = User::factory()->create(['role' => 'patient', 'is_sub_user' => false]);

        $this->assertFalse($patient->hasPermission('diagnosis'));
        $this->assertTrue($this->doctorUser->hasPermission('diagnosis')); // doctor can
    }

    public function test_has_permission_sub_user_only_explicit(): void
    {
        $perm = $this->makePermission(['name' => 'appointments']);
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);

        $this->assertFalse($sub->hasPermission('appointments'));
        $sub->grantPermission($perm, $this->doctorUser);
        $this->assertTrue($sub->fresh()->hasPermission('appointments'));
    }

    public function test_grant_permission_rejects_restricted_for_sub_user(): void
    {
        $restricted = $this->makePermission(['name' => 'sub_users', 'route_pattern' => 'sub-users.*', 'is_restricted' => true]);
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);

        $result = $sub->grantPermission($restricted, $this->doctorUser);
        $this->assertFalse($result);
        $this->assertEquals(0, $sub->permissions()->count());
    }

    public function test_grant_permission_rejects_duplicate(): void
    {
        $perm = $this->makePermission(['name' => 'appointments']);
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);

        $this->assertTrue($sub->grantPermission($perm, $this->doctorUser));
        $this->assertFalse($sub->grantPermission($perm, $this->doctorUser)); // duplicate
        $this->assertEquals(1, $sub->permissions()->count());
    }

    public function test_can_access_route_wildcard(): void
    {
        $perm = $this->makePermission(['name' => 'appointments', 'route_pattern' => 'doctor.appointments.*', 'is_restricted' => false]);
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        $sub->grantPermission($perm, $this->doctorUser);

        $this->assertTrue($sub->canAccessRoute('doctor.appointments.index'));
        $this->assertTrue($sub->canAccessRoute('doctor.appointments.show'));
        $this->assertFalse($sub->canAccessRoute('doctor.reviews.index'));
    }

    public function test_permission_matches_route_exact_and_wildcard(): void
    {
        $exact = $this->makePermission(['name' => 'exact', 'route_pattern' => 'dashboard', 'is_restricted' => false]);
        $wild = $this->makePermission(['name' => 'wild', 'route_pattern' => 'doctor.chat.*', 'is_restricted' => false]);

        $this->assertTrue($exact->matchesRoute('dashboard'));
        $this->assertFalse($exact->matchesRoute('dashboard.index'));
        $this->assertTrue($wild->matchesRoute('doctor.chat.index'));
        $this->assertTrue($wild->matchesRoute('doctor.chat.settings'));
        $this->assertFalse($wild->matchesRoute('doctor.reviews.index'));
        $this->assertFalse((new Permission(['route_pattern' => null]))->matchesRoute('anything'));
    }

    public function test_get_available_for_sub_users_excludes_restricted(): void
    {
        $this->makePermission(['name' => 'open', 'is_restricted' => false]);
        $this->makePermission(['name' => 'closed', 'is_restricted' => true]);

        $available = Permission::getAvailableForSubUsers();
        $this->assertTrue($available->contains('name', 'open'));
        $this->assertFalse($available->contains('name', 'closed'));
    }

    public function test_get_effective_doctor_returns_parent(): void
    {
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        $this->assertEquals($this->doctor->id, $sub->getEffectiveDoctor()->id);
        $this->assertEquals($this->doctorUser->id, $sub->getEffectiveDoctorUser()->id);
        $this->assertEquals($this->doctor->id, $this->doctorUser->getEffectiveDoctor()->id);
    }

    public function test_revoke_permission(): void
    {
        $perm = $this->makePermission(['name' => 'appointments']);
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        $sub->grantPermission($perm, $this->doctorUser);
        $this->assertTrue($sub->revokePermission($perm));
        $this->assertFalse($sub->fresh()->hasPermission('appointments'));
    }

    public function test_has_active_doctor_profile(): void
    {
        $sub = User::factory()->create(['is_sub_user' => true, 'parent_user_id' => $this->doctorUser->id, 'role' => 'doctor']);
        $this->assertTrue($sub->hasActiveDoctorProfile()); // parent is active
        $this->doctor->update(['is_active' => false]);
        $this->assertFalse($sub->fresh()->hasActiveDoctorProfile());
    }
}
