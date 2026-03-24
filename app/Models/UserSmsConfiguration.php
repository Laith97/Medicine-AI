<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class UserSmsConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'hospital_id',
        'provider_key',
        'provider_config',
        'is_active',
        'use_admin_config'
    ];

    protected $casts = [
        'provider_config' => 'array',
        'is_active' => 'boolean',
        'use_admin_config' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->validateExclusiveIds();
        });

        static::updating(function ($model) {
            $model->validateExclusiveIds();
        });
    }

    /**
     * Validate that only one of user_id or hospital_id is set (mutually exclusive)
     */
    protected function validateExclusiveIds()
    {
        if (!is_null($this->user_id) && !is_null($this->hospital_id)) {
            throw new \InvalidArgumentException('Only one of user_id or hospital_id can be set');
        }

        if (is_null($this->user_id) && is_null($this->hospital_id)) {
            throw new \InvalidArgumentException('Either user_id or hospital_id must be set');
        }
    }

    /**
     * Relationship to the user (doctor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the hospital
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get active SMS configurations for a specific user
     */
    public static function getActiveUserConfigurations(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->get()
            ->keyBy('provider_key')
            ->toArray();
    }

    /**
     * Get active SMS configurations for a specific hospital
     */
    public static function getActiveHospitalConfigurations(int $hospitalId): array
    {
        return self::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->get()
            ->keyBy('provider_key')
            ->toArray();
    }

    /**
     * Check if user should use their own configuration
     */
    public static function shouldUseUserConfig(int $userId, string $providerKey): bool
    {
        $config = self::where('user_id', $userId)
            ->where('provider_key', $providerKey)
            ->first();

        return $config && !$config->use_admin_config;
    }

    /**
     * Check if hospital should use their own configuration
     */
    public static function shouldUseHospitalConfig(int $hospitalId, string $providerKey): bool
    {
        $config = self::where('hospital_id', $hospitalId)
            ->where('provider_key', $providerKey)
            ->first();

        return $config && !$config->use_admin_config;
    }

    /**
     * Get sanitized provider configuration (without sensitive data)
     */
    public function getSanitizedConfig(): array
    {
        if (!$this->provider_config) {
            return [];
        }

        $config = $this->provider_config;

        // Remove sensitive fields from config for display purposes
        $sensitiveFields = ['auth_token', 'password', 'access_key', 'secret', 'key'];

        foreach ($sensitiveFields as $field) {
            if (isset($config[$field])) {
                $config[$field] = str_repeat('*', min(8, strlen($config[$field])));
            }
        }

        return $config;
    }
}