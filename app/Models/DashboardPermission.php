<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardPermission extends Model
{
    use HasFactory;

    protected $table = 'dashboard_permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'role_id',
        'dashboard_name',
        'access_level',
        'data_scope',
    ];

    protected $casts = [
        'role_id' => 'integer',
    ];

    /**
     * The analytics role this permission belongs to
     */
    public function analyticsRole(): BelongsTo
    {
        return $this->belongsTo(AnalyticsRole::class, 'role_id', 'role_id');
    }

    /**
     * Check if this permission allows access
     */
    public function allowsAccess(): bool
    {
        return $this->access_level !== 'none';
    }

    /**
     * Check if this permission allows full access
     */
    public function allowsFullAccess(): bool
    {
        return $this->access_level === 'full';
    }

    /**
     * Check if this permission allows limited access
     */
    public function allowsLimitedAccess(): bool
    {
        return in_array($this->access_level, ['limited', 'full']);
    }

    /**
     * Check if this permission allows basic access
     */
    public function allowsBasicAccess(): bool
    {
        return in_array($this->access_level, ['basic', 'limited', 'full']);
    }

    /**
     * Get available access levels
     */
    public static function getAccessLevels(): array
    {
        return ['none', 'basic', 'limited', 'full'];
    }

    /**
     * Get available data scopes
     */
    public static function getDataScopes(): array
    {
        return ['personal', 'team', 'department', 'hospital', 'system'];
    }
}
