<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistAiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'ai_matching_enabled',
        'auto_send_offers',
        'min_match_score',
        'offer_expiry_minutes',
        'max_offers_per_slot',
        'priority_override_auto',
    ];

    protected $casts = [
        'ai_matching_enabled' => 'boolean',
        'auto_send_offers' => 'boolean',
        'priority_override_auto' => 'boolean',
        'min_match_score' => 'decimal:2',
        'offer_expiry_minutes' => 'integer',
        'max_offers_per_slot' => 'integer',
    ];

    /**
     * Get the doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Check if AI matching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->ai_matching_enabled;
    }

    /**
     * Check if auto-send is enabled.
     */
    public function shouldAutoSend(): bool
    {
        return $this->auto_send_offers;
    }

    /**
     * Scope for enabled AI matching.
     */
    public function scopeEnabled($query)
    {
        return $query->where('ai_matching_enabled', true);
    }
}
