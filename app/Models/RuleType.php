<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'validation_rules',
    ];

    protected $casts = [
        'validation_rules' => 'array',
    ];

    /**
     * Get the rules of this type.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PayerRule::class);
    }

    /**
     * Find rule type by name.
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }
}
