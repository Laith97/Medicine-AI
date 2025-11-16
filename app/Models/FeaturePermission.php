<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePermission extends Model
{
    use HasFactory;

    protected $table = 'feature_permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'role_id',
        'feature_name',
        'can_access',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'can_access' => 'boolean',
    ];

    /**
     * The analytics role this permission belongs to
     */
    public function analyticsRole(): BelongsTo
    {
        return $this->belongsTo(AnalyticsRole::class, 'role_id', 'role_id');
    }

    /**
     * Get available feature names
     */
    public static function getAvailableFeatures(): array
    {
        return [
            'real_time_updates',
            'export_data',
            'custom_date_ranges',
            'drill_down_analysis',
            'alert_configuration',
            'dashboard_customization',
            'scheduled_reports',
            'api_access',
        ];
    }
}
