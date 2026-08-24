<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unified registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Create your account');
    $response->assertSee('Healthcare Professional');
    $response->assertSee('Patient');
});

test('register-doctor redirects to unified register with role doctor', function () {
    $response = $this->get('/register-doctor');

    $response->assertRedirect('/register?role=doctor');
});

test('register patient GET redirects to unified register with role patient', function () {
    $response = $this->get('/register/patient');

    $response->assertRedirect('/register?role=patient');
});

test('login screen can be rendered modern', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Welcome back');
});

test('doctor can register via unified form', function () {
    $response = $this->post('/register', [
        'name' => 'Dr Test User',
        'email' => 'doctor-test@example.com',
        'phone' => '+12345678901',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'specialty' => 'Cardiology',
        'selected_plan' => 'free',
        'selected_billing' => 'monthly',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('doctor.dashboard', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'doctor-test@example.com', 'role' => 'doctor']);
});

test('doctor registration fails without specialty', function () {
    $response = $this->post('/register', [
        'name' => 'Dr No Specialty',
        'email' => 'no-specialty@example.com',
        'phone' => '+12345678902',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'specialty' => '',
        'selected_plan' => 'free',
        'selected_billing' => 'monthly',
    ]);

    $response->assertSessionHasErrors('specialty');
    $this->assertGuest();
});

test('patient can register via patient endpoint', function () {
    $response = $this->post('/register/patient', [
        'name' => 'Patient Test',
        'email' => 'patient-test@example.com',
        'phone' => '+19876543210',
        'date_of_birth' => '1990-05-15',
        'gender' => 'male',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('doctors.index', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'patient-test@example.com', 'role' => 'patient']);
});

test('patient registration fails without terms', function () {
    $response = $this->post('/register/patient', [
        'name' => 'Patient No Terms',
        'email' => 'patient-noterms@example.com',
        'phone' => '+19876543211',
        'date_of_birth' => '1990-05-15',
        'gender' => 'female',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
});

test('unified register shows both forms', function () {
    $response = $this->get('/register?role=patient');

    $response->assertStatus(200);
    $response->assertSee('Healthcare Professional', false);
    $response->assertSee('Patient', false);
});
