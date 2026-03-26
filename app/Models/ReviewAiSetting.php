<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'auto_generate_enabled',
        'auto_post_enabled',
        'default_tone',
        'custom_instructions',
        'min_rating_for_auto_response',
        'respond_to_negative',
    ];

    protected $casts = [
        'auto_generate_enabled' => 'boolean',
        'auto_post_enabled' => 'boolean',
        'respond_to_negative' => 'boolean',
        'custom_instructions' => 'array',
        'min_rating_for_auto_response' => 'integer',
    ];

    /**
     * Get the doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Check if auto-generate is enabled.
     */
    public function isAutoGenerateEnabled(): bool
    {
        return $this->auto_generate_enabled;
    }

    /**
     * Check if should auto-respond based on rating.
     */
    public function shouldAutoRespond(int $rating): bool
    {
        return $rating >= $this->min_rating_for_auto_response;
    }

    /**
     * Scope for doctors with auto-generate enabled.
     */
    public function scopeWithAutoGenerate($query)
    {
        return $query->where('auto_generate_enabled', true);
    }
}
