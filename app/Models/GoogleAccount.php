<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class GoogleAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'google_account_id',
        'business_account_id',
        'location_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the doctor that owns the Google account
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired()
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if token needs refresh (expires within 5 minutes)
     */
    public function needsTokenRefresh()
    {
        return $this->token_expires_at && $this->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Update tokens
     */
    public function updateTokens($accessToken, $refreshToken = null, $expiresIn = null)
    {
        $data = [
            'access_token' => $accessToken,
        ];

        if ($refreshToken) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($expiresIn) {
            $data['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        $this->update($data);
    }

    /**
     * Mark as synced
     */
    public function markAsSynced()
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Deactivate account
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate account
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Get formatted last sync time
     */
    public function getLastSyncFormattedAttribute()
    {
        if (!$this->last_sync_at) {
            return 'Never';
        }

        return $this->last_sync_at->diffForHumans();
    }

    /**
     * Get token status
     */
    public function getTokenStatusAttribute()
    {
        if ($this->isTokenExpired()) {
            return 'expired';
        }

        if ($this->needsTokenRefresh()) {
            return 'needs_refresh';
        }

        return 'valid';
    }
}
