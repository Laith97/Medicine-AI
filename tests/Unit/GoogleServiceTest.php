<?php

use App\Models\Doctor;
use App\Models\GoogleAccount;
use App\Models\Review;
use App\Services\GoogleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery as m;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Use an existing user
    $user = \App\Models\User::first();

    // Create a doctor for testing
    $this->doctor = \App\Models\Doctor::create([
        'user_id' => $user->id,
        'specialty_id' => 1, // Set a valid specialty ID
        'license_number' => 'TEST123456',
        'phone' => '123-456-7890',
        'bio' => 'Test doctor for Google reviews',
        'languages' => ['English'],
        'address' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'zip_code' => '12345',
        'country' => 'Test Country',
        'latitude' => 0.0,
        'longitude' => 0.0,
        'consultation_fee' => 10000, // $100.00
        'appointment_duration' => 30,
        'auto_approve_appointments' => true,
        'allow_cancellation' => true,
        'allow_rescheduling' => true,
        'cancellation_hours' => 24,
        'average_rating' => 4.5,
        'total_reviews' => 10,
        'is_active' => true,
        'is_verified' => true,
    ]);

    // Create a Google account for the doctor
    $this->googleAccount = \App\Models\GoogleAccount::create([
        'doctor_id' => $this->doctor->id,
        'google_account_id' => 'test_google_account_id',
        'access_token' => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'token_expires_at' => now()->addHour(),
        'scopes' => ['https://www.googleapis.com/auth/business.manage'],
        'is_active' => true,
    ]);
});

afterEach(function () {
    m::close();
});

test('it can create google service instance', function () {
    $service = new GoogleService();

    expect($service)->toBeInstanceOf(GoogleService::class);
});

test('it can get auth url', function () {
    $service = new GoogleService();
    $url = $service->getAuthUrl();

    expect($url)->toBeString();
    expect($url)->toContain('accounts.google.com');
});

test('it can refresh token when expired', function () {
    $user = \App\Models\User::first();
    $doctor = \App\Models\Doctor::create([
        'user_id' => $user->id,
        'specialty_id' => 1,
        'license_number' => 'TEST123457',
        'phone' => '123-456-7891',
        'bio' => 'Test doctor for Google reviews',
        'languages' => ['English'],
        'address' => '124 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'zip_code' => '12346',
        'country' => 'Test Country',
        'latitude' => 0.1,
        'longitude' => 0.1,
        'consultation_fee' => 10000, // $100.00
        'appointment_duration' => 30,
        'auto_approve_appointments' => true,
        'allow_cancellation' => true,
        'allow_rescheduling' => true,
        'cancellation_hours' => 24,
        'average_rating' => 4.5,
        'total_reviews' => 10,
        'is_active' => true,
        'is_verified' => true,
    ]);
    $googleAccount = \App\Models\GoogleAccount::create([
        'doctor_id' => $doctor->id,
        'google_account_id' => 'test_google_account_id_2',
        'access_token' => 'old_token',
        'refresh_token' => 'refresh_token',
        'token_expires_at' => now()->subHour(), // Expired
        'scopes' => ['https://www.googleapis.com/auth/business.manage'],
        'is_active' => true,
    ]);

    $service = new GoogleService();

    // This should not throw an exception
    $service->refreshToken($googleAccount);

    expect(true)->toBeTrue(); // If we get here without exception, the test passes
});

test('it throws exception when no refresh token available', function () {
    $user = \App\Models\User::first();
    $doctor = \App\Models\Doctor::create([
        'user_id' => $user->id,
        'specialty_id' => 1,
        'license_number' => 'TEST123458',
        'phone' => '123-456-7892',
        'bio' => 'Test doctor for Google reviews',
        'languages' => ['English'],
        'address' => '125 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'zip_code' => '12347',
        'country' => 'Test Country',
        'latitude' => 0.2,
        'longitude' => 0.2,
        'consultation_fee' => 10000, // $100.00
        'appointment_duration' => 30,
        'auto_approve_appointments' => true,
        'allow_cancellation' => true,
        'allow_rescheduling' => true,
        'cancellation_hours' => 24,
        'average_rating' => 4.5,
        'total_reviews' => 10,
        'is_active' => true,
        'is_verified' => true,
    ]);
    $googleAccount = \App\Models\GoogleAccount::create([
        'doctor_id' => $doctor->id,
        'google_account_id' => 'test_google_account_id_3',
        'access_token' => 'old_token',
        'refresh_token' => null, // No refresh token
        'token_expires_at' => now()->subHour(), // Expired
        'scopes' => ['https://www.googleapis.com/auth/business.manage'],
        'is_active' => true,
    ]);

    $service = new GoogleService();

    expect(fn() => $service->refreshToken($googleAccount))->toThrow(Exception::class);
});

test('it can post review to google', function () {
    $user = \App\Models\User::first();
    $doctor = \App\Models\Doctor::create([
        'user_id' => $user->id,
        'specialty_id' => 1,
        'license_number' => 'TEST123459',
        'phone' => '123-456-7893',
        'bio' => 'Test doctor for Google reviews',
        'languages' => ['English'],
        'address' => '126 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'zip_code' => '12348',
        'country' => 'Test Country',
        'latitude' => 0.3,
        'longitude' => 0.3,
        'consultation_fee' => 10000, // $100.00
        'appointment_duration' => 30,
        'auto_approve_appointments' => true,
        'allow_cancellation' => true,
        'allow_rescheduling' => true,
        'cancellation_hours' => 24,
        'average_rating' => 4.5,
        'total_reviews' => 10,
        'is_active' => true,
        'is_verified' => true,
    ]);
    $googleAccount = \App\Models\GoogleAccount::create([
        'doctor_id' => $doctor->id,
        'google_account_id' => 'test_google_account_id_4',
        'access_token' => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'token_expires_at' => now()->addHour(),
        'scopes' => ['https://www.googleapis.com/auth/business.manage'],
        'is_active' => true,
    ]);

    $review = \App\Models\Review::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $user->id,
        'rating' => 5,
        'comment' => 'Great service!',
        'consent_google_posting' => true,
        'posted_to_google' => false,
        'source' => 'medcura',
    ]);

    // Associate the doctor with the review
    $review->doctor()->associate($doctor);
    $review->setRelation('doctor', $doctor);

    $service = new GoogleService();
    $result = $service->postReview($review);

    expect($result)->toBeTrue();
});

test('it does not post review when doctor has no google account', function () {
    $user = \App\Models\User::first();
    $doctor = \App\Models\Doctor::create([
        'user_id' => $user->id,
        'specialty_id' => 1,
        'license_number' => 'TEST123460',
        'phone' => '123-456-7894',
        'bio' => 'Test doctor for Google reviews',
        'languages' => ['English'],
        'address' => '127 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'zip_code' => '12349',
        'country' => 'Test Country',
        'latitude' => 0.4,
        'longitude' => 0.4,
        'consultation_fee' => 10000, // $100.00
        'appointment_duration' => 30,
        'auto_approve_appointments' => true,
        'allow_cancellation' => true,
        'allow_rescheduling' => true,
        'cancellation_hours' => 24,
        'average_rating' => 4.5,
        'total_reviews' => 10,
        'is_active' => true,
        'is_verified' => true,
    ]);
    $review = \App\Models\Review::create([
        'doctor_id' => $doctor->id,
        'patient_id' => $user->id,
        'rating' => 5,
        'comment' => 'Great service!',
        'consent_google_posting' => true,
        'posted_to_google' => false,
        'source' => 'medcura',
    ]);

    // Associate the doctor with the review
    $review->doctor()->associate($doctor);

    $service = new GoogleService();

    expect(fn() => $service->postReview($review))->toThrow(Exception::class);
});
