<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiPermission extends Model
{
    use HasFactory;

    protected $table = 'kpi_permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'role_id',
        'kpi_category',
        'can_view',
        'can_export',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'can_view' => 'boolean',
        'can_export' => 'boolean',
    ];

    /**
     * The analytics role this permission belongs to
     */
    public function analyticsRole(): BelongsTo
    {
        return $this->belongsTo(AnalyticsRole::class, 'role_id', 'role_id');
    }

    /**
     * Get available KPI categories
     */
    public static function getAvailableKpiCategories(): array
    {
        return [
            'revenue_metrics',
            'patient_satisfaction',
            'operational_efficiency',
            'clinical_outcomes',
            'quality_measures',
            'compliance_metrics',
            'staff_performance',
            'system_performance',
        ];
    }
}
