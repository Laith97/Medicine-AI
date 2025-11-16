<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_info',
        'payer_id',
        'settings',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the rules for this payer.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PayerRule::class);
    }

    /**
     * Find payer by payer_id.
     */
    public static function findByPayerId(string $payerId): ?self
    {
        return static::where('payer_id', $payerId)->first();
    }
}
