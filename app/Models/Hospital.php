<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Hospital extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'website',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hospital) {
            if (empty($hospital->slug)) {
                $hospital->slug = Str::slug($hospital->name);
            }
        });

        static::updating(function ($hospital) {
            if ($hospital->isDirty('name') && empty($hospital->slug)) {
                $hospital->slug = Str::slug($hospital->name);
            }
        });

        static::created(function ($hospital) {
            $hospital->createDefaultDepartments();
        });
    }

    /**
     * Hospital admin relationship
     */
    public function admin()
    {
        return $this->hasOne(User::class, 'hospital_id')->where('role', 'hospital_admin');
    }

    /**
     * All hospital admins for this hospital
     */
    public function hospitalAdmins()
    {
        return $this->hasMany(User::class, 'hospital_id')->where('role', 'hospital_admin');
    }

    /**
     * All users associated with this hospital
     */
    public function users()
    {
        return $this->hasMany(User::class, 'hospital_id');
    }

    /**
     * Doctors associated with this hospital
     */
    public function doctors()
    {
        return $this->hasMany(User::class, 'hospital_id')->where('role', 'doctor');
    }

    /**
     * Departments associated with this hospital
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Active doctors associated with this hospital
     */
    public function activeDoctors()
    {
        return $this->doctors()->whereHas('doctor', function ($query) {
            $query->where('is_active', true);
        });
    }

    /**
     * Get hospital statistics
     */
    public function getStatistics()
    {
        $doctors = $this->doctors()->with('doctor')->get();
        $activeDoctors = $doctors->filter(function ($doctor) {
            return $doctor->doctor && $doctor->doctor->is_active;
        });

        $totalAppointments = 0;
        $totalReviews = 0;
        $totalRevenue = 0;

        foreach ($activeDoctors as $doctor) {
            if ($doctor->doctor) {
                $totalAppointments += $doctor->doctor->appointments()->count();
                $totalReviews += $doctor->doctor->reviews()->count();
                // Add revenue calculation if needed
            }
        }

        return [
            'total_doctors' => $doctors->count(),
            'active_doctors' => $activeDoctors->count(),
            'inactive_doctors' => $doctors->count() - $activeDoctors->count(),
            'total_appointments' => $totalAppointments,
            'total_reviews' => $totalReviews,
            'average_rating' => $totalReviews > 0 ? $activeDoctors->avg(function ($doctor) {
                return $doctor->doctor ? $doctor->doctor->reviews()->avg('rating') : 0;
            }) : 0,
        ];
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
     * Scope for active hospitals
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Create default departments for the hospital
     */
    public function createDefaultDepartments()
    {
        $defaultDepartments = [
            [
                'name' => 'Emergency Department',
                'description' => 'Emergency medical care and trauma services',
                'is_active' => true,
            ],
            [
                'name' => 'Internal Medicine',
                'description' => 'General internal medicine and primary care',
                'is_active' => true,
            ],
            [
                'name' => 'Surgery',
                'description' => 'Surgical procedures and perioperative care',
                'is_active' => true,
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Medical care for infants, children, and adolescents',
                'is_active' => true,
            ],
            [
                'name' => 'Obstetrics & Gynecology',
                'description' => 'Women\'s health, pregnancy, and childbirth',
                'is_active' => true,
            ],
            [
                'name' => 'Cardiology',
                'description' => 'Heart and cardiovascular system care',
                'is_active' => true,
            ],
            [
                'name' => 'Radiology',
                'description' => 'Medical imaging and diagnostic services',
                'is_active' => true,
            ],
            [
                'name' => 'Laboratory Services',
                'description' => 'Clinical laboratory testing and pathology',
                'is_active' => true,
            ],
        ];

        foreach ($defaultDepartments as $departmentData) {
            $this->departments()->create($departmentData);
        }
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}