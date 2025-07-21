<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'zip_code',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    public function setting()
{
    return $this->hasOne(Setting::class);
}

public function patientAnalyses()
{
    return $this->hasMany(PatientAnalysis::class);
}

// Doctor relationship
public function doctor()
{
    return $this->hasOne(Doctor::class);
}

// Patient appointments
public function appointments()
{
    return $this->hasMany(Appointment::class, 'patient_id');
}

// Patient reviews
public function reviews()
{
    return $this->hasMany(Review::class, 'patient_id');
}

/**
 * Check if user is admin
 */
public function isAdmin()
{
    return $this->is_admin;
}

/**
 * Make user admin
 */
public function makeAdmin()
{
    $this->update(['is_admin' => true]);
}

/**
 * Remove admin privileges
 */
public function removeAdmin()
{
    $this->update(['is_admin' => false]);
}

/**
 * Check if user is a doctor
 */
public function isDoctor()
{
    return $this->role === 'doctor';
}

/**
 * Check if user is a patient
 */
public function isPatient()
{
    return $this->role === 'patient';
}

/**
 * Get full address
 */
public function getFullAddressAttribute()
{
    $parts = array_filter([
        $this->address,
        $this->city,
        $this->state,
        $this->zip_code
    ]);

    return implode(', ', $parts);
}

/**
 * Send the password reset notification.
 *
 * @param  string  $token
 * @return void
 */
public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPasswordNotification($token));
}

}
