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
     * Calculate cost based on tokens and model using OpenAI pricing.
     */
    public static function calculateCost(int $totalTokens, string $model = 'gpt-4', int $promptTokens = 0, int $completionTokens = 0): float
    {
        // OpenAI pricing per 1M tokens (as of 2024)
        $pricing = [
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00], // $2.50 input, $10.00 output per 1M tokens
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60], // $0.15 input, $0.60 output per 1M tokens
            'gpt-4' => ['input' => 30.00, 'output' => 60.00], // $30.00 input, $60.00 output per 1M tokens
            'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00], // $10.00 input, $30.00 output per 1M tokens
            'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50], // $0.50 input, $1.50 output per 1M tokens
        ];

        // Default to gpt-4o pricing if model not found
        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o'];
        
        // If we have separate prompt and completion tokens, calculate separately
        if ($promptTokens > 0 && $completionTokens > 0) {
            $inputCost = ($promptTokens / 1000000) * $modelPricing['input'];
            $outputCost = ($completionTokens / 1000000) * $modelPricing['output'];
            return $inputCost + $outputCost;
        }
        
        // Fallback: assume 70% input, 30% output for total tokens
        $estimatedPromptTokens = $totalTokens * 0.7;
        $estimatedCompletionTokens = $totalTokens * 0.3;
        
        $inputCost = ($estimatedPromptTokens / 1000000) * $modelPricing['input'];
        $outputCost = ($estimatedCompletionTokens / 1000000) * $modelPricing['output'];
        
        return $inputCost + $outputCost;
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
