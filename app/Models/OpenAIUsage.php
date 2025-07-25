<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenAIUsage extends Model
{
    protected $table = 'openai_usages';
    
    protected $fillable = [
        'user_id',
        'request_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_estimate',
        'model_used',
        'request_metadata',
    ];

    protected $casts = [
        'cost_estimate' => 'decimal:6',
        'request_metadata' => 'array',
    ];

    /**
     * Get the user that owns the usage record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate cost based on tokens and model.
     */
    public static function calculateCost(int $totalTokens, string $model = 'gpt-4'): float
    {
        $costPer1k = config('stripe.token_cost_per_1k', 0.002);
        return ($totalTokens / 1000) * $costPer1k;
    }

    /**
     * Get usage statistics for a user within a date range.
     */
    public static function getUserUsageStats(int $userId, $startDate = null, $endDate = null): array
    {
        $query = self::where('user_id', $userId);
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(prompt_tokens) as total_prompt_tokens,
            SUM(completion_tokens) as total_completion_tokens,
            SUM(total_tokens) as total_tokens,
            SUM(cost_estimate) as total_cost
        ')->first();

        return [
            'total_requests' => $stats->total_requests ?? 0,
            'total_prompt_tokens' => $stats->total_prompt_tokens ?? 0,
            'total_completion_tokens' => $stats->total_completion_tokens ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => $stats->total_cost ?? 0,
        ];
    }
}
