<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ClearinghouseAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'credentials',
        'settings',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'settings' => 'json',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the submissions for this clearinghouse account.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ClearinghouseSubmission::class);
    }

    /**
     * Get the responses for this clearinghouse account.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ClearinghouseResponse::class);
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Get decrypted credentials
     */
    public function getDecryptedCredentials(): array
    {
        $decrypted = Crypt::decryptString(json_decode($this->credentials, true));
        return json_decode($decrypted, true) ?? [];
    }

    /**
     * Set encrypted credentials
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials = json_encode(Crypt::encryptString(json_encode($credentials)));
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
