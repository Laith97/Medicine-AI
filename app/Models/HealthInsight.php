<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthInsight extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'insight_type',
        'summary',
        'content',
        'expires_at',
    ];

    protected $casts = [
        'content' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isFresh(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    public static function getLatestForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public static function getFreshForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
