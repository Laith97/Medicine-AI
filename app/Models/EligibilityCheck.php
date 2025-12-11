<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Traits\AuditLoggable;

class EligibilityCheck extends Model
{
    use HasFactory, AuditLoggable, SoftDeletes;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($check) {
            if ($check->isDirty()) {
                // Increment version for optimistic locking
                $check->version = $check->version + 1;
            }
        });
    }

    protected $fillable = [
        'patient_insurance_id',
        'check_date',
        'service_type',
        'eligibility_status',
        'response_data',
        'expires_at',
        'checked_by',
        'version',
    ];

    protected $casts = [
        'check_date' => 'datetime',
        'expires_at' => 'datetime',
        'response_data' => 'array',
    ];

    protected $hidden = [
        'response_data',
    ];

    protected $sensitiveFields = [
        'response_data',
    ];

    /**
     * Set the response data (encrypted)
     */
    public function setResponseDataAttribute($value)
    {
        if ($value) {
            $this->attributes['response_data'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['response_data'] = null;
        }
    }

    /**
     * Get the response data (decrypted)
     */
    public function getResponseDataAttribute($value)
    {
        if ($value) {
            try {
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt response data', [
                    'check_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    public function patientInsurance()
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
