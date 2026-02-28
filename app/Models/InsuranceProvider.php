<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Traits\AuditLoggable;

class InsuranceProvider extends Model
{
    use HasFactory, AuditLoggable;

    protected $fillable = [
        'name',
        'api_endpoint',
        'api_key',
        'supported_services',
        'contact_info',
    ];

    protected $casts = [
        'supported_services' => 'array',
        'contact_info' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $sensitiveFields = [
        'api_key',
    ];

    /**
     * Set the API key (encrypted)
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key'] = null;
        }
    }

    /**
     * Get the API key (decrypted)
     */
    public function getApiKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt API key', [
                    'provider_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        return null;
    }

    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }
}
