<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'name',
        'description',
        'head_of_department',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the hospital that owns the department.
     */
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get all users in this department.
     * Note: Since we removed department_id from users, this returns empty collection
     */
    public function users()
    {
        // Return empty collection since department_id doesn't exist in users table
        return collect([]);
    }

    /**
     * Get the doctors in this department.
     * Note: Since we simplified doctors management, this returns empty collection
     */
    public function doctors()
    {
        // Return empty collection since we removed department assignments
        return collect([]);
    }

    /**
     * Get active doctors in this department.
     */
    public function activeDoctors()
    {
        // Return empty collection since we removed department assignments
        return collect([]);
    }

    /**
     * Get statistics for this department.
     * Note: Returns zero stats since we removed department-doctor relationships
     */
    public function getStatistics()
    {
        return [
            'total_doctors' => 0,
            'active_doctors' => 0,
            'total_appointments' => 0,
            'this_month_appointments' => 0,
        ];
    }
}