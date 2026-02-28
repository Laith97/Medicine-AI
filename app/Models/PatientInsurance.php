<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Traits\AuditLoggable;

class PatientInsurance extends Model
{
    use HasFactory, AuditLoggable;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($insurance) {
            if ($insurance->isDirty()) {
                // Increment version for optimistic locking
                $insurance->version = $insurance->version + 1;
            }
        });

        static::updated(function ($insurance) {
            // Invalidate eligibility cache when insurance data changes
            if ($insurance->wasChanged(['policy_number', 'group_number', 'subscriber_id', 'relationship_to_subscriber', 'effective_date', 'termination_date'])) {
                // Clear all eligibility cache keys for this patient insurance
                $cachePattern = "eligibility:{$insurance->id}:*";
                // Since we can't use wildcards with Cache facade, we'll clear specific common service types
                $commonServiceTypes = ['office_visit', 'consultation', 'procedure', 'diagnostic', 'therapy'];
                foreach ($commonServiceTypes as $serviceType) {
                    Cache::forget("eligibility:{$insurance->id}:{$serviceType}");
                }
            }
        });
    }

    protected $fillable = [
        'patient_id',
        'insurance_provider_id',
        'policy_number',
        'group_number',
        'subscriber_id',
        'relationship_to_subscriber',
        'effective_date',
        'termination_date',
        'copay_info',
        'deductible_info',
        'version',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'termination_date' => 'date',
        'copay_info' => 'array',
        'deductible_info' => 'array',
    ];

    protected $hidden = [
        'policy_number',
        'subscriber_id',
    ];

    protected $sensitiveFields = [
        'policy_number',
        'subscriber_id',
        'copay_info',
        'deductible_info',
    ];

    /**
     * Set the policy number (encrypted)
     */
    public function setPolicyNumberAttribute($value)
    {
        if ($value) {
            $this->attributes['policy_number'] = Crypt::encryptString($value);
        } else {
            $this->attributes['policy_number'] = null;
        }
    }

    /**
     * Get the policy number (decrypted)
     */
    public function getPolicyNumberAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt policy number', [
                    'insurance_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    /**
     * Set the subscriber ID (encrypted)
     */
    public function setSubscriberIdAttribute($value)
    {
        if ($value) {
            $this->attributes['subscriber_id'] = Crypt::encryptString($value);
        } else {
            $this->attributes['subscriber_id'] = null;
        }
    }

    /**
     * Get the subscriber ID (decrypted)
     */
    public function getSubscriberIdAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt subscriber ID', [
                    'insurance_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    /**
     * Set the copay info (encrypted)
     */
    public function setCopayInfoAttribute($value)
    {
        if ($value) {
            $this->attributes['copay_info'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['copay_info'] = null;
        }
    }

    /**
     * Get the copay info (decrypted)
     */
    public function getCopayInfoAttribute($value)
    {
        if ($value) {
            try {
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt copay info', [
                    'insurance_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    /**
     * Set the deductible info (encrypted)
     */
    public function setDeductibleInfoAttribute($value)
    {
        if ($value) {
            $this->attributes['deductible_info'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['deductible_info'] = null;
        }
    }

    /**
     * Get the deductible info (decrypted)
     */
    public function getDeductibleInfoAttribute($value)
    {
        if ($value) {
            try {
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt deductible info', [
                    'insurance_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    public function patient()
    {
        return $this->belongsTo(PatientData::class, 'patient_id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function eligibilityChecks()
    {
        return $this->hasMany(EligibilityCheck::class);
    }
}
