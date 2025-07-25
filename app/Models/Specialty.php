<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
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

        static::creating(function ($specialty) {
            if (empty($specialty->slug)) {
                $specialty->slug = Str::slug($specialty->name);
            }
        });

        static::updating(function ($specialty) {
            if ($specialty->isDirty('name') && empty($specialty->slug)) {
                $specialty->slug = Str::slug($specialty->name);
            }
        });
    }

    /**
     * Get doctors for this specialty
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * Get active doctors for this specialty
     */
    public function activeDoctors()
    {
        return $this->hasMany(Doctor::class)->where('is_active', true);
    }

    /**
     * Scope for active specialties
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get route key name
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
