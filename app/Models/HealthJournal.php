<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class HealthJournal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'entry_date',
        'symptoms',
        'severity',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'symptoms' => 'array',
        'severity' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('entry_date', $date);
    }

    public static function getForDate(int $userId, string|Carbon $date): ?self
    {
        return self::where('user_id', $userId)
            ->where('entry_date', $date)
            ->first();
    }

    /**
     * Upsert a journal entry for a given user and date.
     * Creates if not exists, updates if exists.
     *
     * @param int $userId
     * @param string|Carbon $date
     * @param array $symptoms
     * @param array $severity
     * @param string|null $notes
     * @return self
     */
    public static function upsertEntry(int $userId, string|Carbon $date, array $symptoms, array $severity, ?string $notes): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'entry_date' => $date],
            [
                'symptoms' => $symptoms,
                'severity' => $severity,
                'notes' => $notes,
            ]
        );
    }
}
