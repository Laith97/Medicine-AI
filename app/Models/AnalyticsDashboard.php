<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * AnalyticsDashboard Model
 * 
 * Represents an analytics dashboard that users can customize with widgets,
 * metrics, and filters for monitoring key application data.
 * 
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array|null $configuration
 * @property bool $is_default
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalyticsDashboard extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_dashboards';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'configuration',
        'is_default',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'configuration' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this dashboard.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all widgets associated with this dashboard.
     *
     * @return HasMany
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id');
    }

    /**
     * Get all metrics associated with this dashboard.
     *
     * @return HasMany
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(AnalyticsMetric::class, 'dashboard_id');
    }

    /**
     * Get all filters associated with this dashboard.
     *
     * @return HasMany
     */
    public function filters(): HasMany
    {
        return $this->hasMany(DashboardFilter::class, 'dashboard_id');
    }

    /**
     * Scope to get active dashboards only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get dashboards by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get the default dashboard for a user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query, int $userId)
    {
        return $query->where('user_id', $userId)->where('is_default', true);
    }

    /**
     * Set configuration for the dashboard.
     *
     * @param array $config
     * @return self
     */
    public function setConfiguration(array $config): self
    {
        $this->configuration = array_merge($this->configuration ?? [], $config);
        return $this;
    }

    /**
     * Get a specific configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfiguration(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Add a widget to the dashboard.
     *
     * @param array $widgetData
     * @return DashboardWidget
     */
    public function addWidget(array $widgetData): DashboardWidget
    {
        return $this->widgets()->create($widgetData);
    }

    /**
     * Remove a widget from the dashboard.
     *
     * @param int $widgetId
     * @return bool
     */
    public function removeWidget(int $widgetId): bool
    {
        return $this->widgets()->where('id', $widgetId)->delete() > 0;
    }

    /**
     * Get all active widgets with their metrics.
     *
     * @return Collection
     */
    public function getActiveWidgets(): Collection
    {
        return $this->widgets()
            ->where('is_active', true)
            ->orderBy('position')
            ->with('metrics')
            ->get();
    }

    /**
     * Retrieve metrics based on applied filters.
     *
     * @param array $filterOptions
     * @return Collection
     */
    public function retrieveMetrics(array $filterOptions = []): Collection
    {
        $query = $this->metrics()->with('data');

        // Apply active filters
        $filters = $this->getActiveFilters();
        foreach ($filters as $filter) {
            $query = $this->applyFilter($query, $filter, $filterOptions);
        }

        return $query->get();
    }

    /**
     * Get all active filters for the dashboard.
     *
     * @return Collection
     */
    public function getActiveFilters(): Collection
    {
        return $this->filters()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Apply a single filter to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param DashboardFilter $filter
     * @param array $filterOptions
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilter($query, DashboardFilter $filter, array $filterOptions)
    {
        $filterValue = data_get($filterOptions, $filter->field);

        if ($filterValue === null) {
            $filterValue = $filter->default_value;
        }

        if ($filterValue === null) {
            return $query;
        }

        $operator = $filter->operator ?? '=';

        switch ($operator) {
            case '=':
                return $query->where($filter->field, $filterValue);
            case '!=':
                return $query->where($filter->field, '!=', $filterValue);
            case '>':
                return $query->where($filter->field, '>', $filterValue);
            case '<':
                return $query->where($filter->field, '<', $filterValue);
            case '>=':
                return $query->where($filter->field, '>=', $filterValue);
            case '<=':
                return $query->where($filter->field, '<=', $filterValue);
            case 'like':
                return $query->where($filter->field, 'like', "%{$filterValue}%");
            case 'in':
                return $query->whereIn($filter->field, (array)$filterValue);
            case 'between':
                if (is_array($filterValue) && count($filterValue) === 2) {
                    return $query->whereBetween($filter->field, $filterValue);
                }
                return $query;
            default:
                return $query;
        }
    }

    /**
     * Add a filter to the dashboard.
     *
     * @param array $filterData
     * @return DashboardFilter
     */
    public function addFilter(array $filterData): DashboardFilter
    {
        return $this->filters()->create($filterData);
    }

    /**
     * Remove a filter from the dashboard.
     *
     * @param int $filterId
     * @return bool
     */
    public function removeFilter(int $filterId): bool
    {
        return $this->filters()->where('id', $filterId)->delete() > 0;
    }

    /**
     * Toggle the active status of a filter.
     *
     * @param int $filterId
     * @return bool
     */
    public function toggleFilter(int $filterId): bool
    {
        $filter = $this->filters()->find($filterId);

        if ($filter) {
            $filter->is_active = !$filter->is_active;
            return $filter->save();
        }

        return false;
    }

    /**
     * Update dashboard configuration and settings.
     *
     * @param array $config
     * @return bool
     */
    public function updateConfiguration(array $config): bool
    {
        $this->setConfiguration($config);
        return $this->save();
    }

    /**
     * Clone the dashboard for a user.
     *
     * @param int $userId
     * @param string|null $name
     * @return self
     */
    public function cloneForUser(int $userId, ?string $name = null): self
    {
        $clone = $this->replicate();
        $clone->user_id = $userId;
        $clone->name = $name ?? $this->name . ' (Copy)';
        $clone->slug = $this->generateSlug($clone->name);
        $clone->is_default = false;
        $clone->save();

        // Clone widgets
        foreach ($this->widgets as $widget) {
            $widgetClone = $widget->replicate();
            $widgetClone->dashboard_id = $clone->id;
            $widgetClone->save();

            // Clone widget metrics if they exist
            if (method_exists($widget, 'metrics')) {
                foreach ($widget->metrics as $metric) {
                    $metricClone = $metric->replicate();
                    $metricClone->dashboard_widget_id = $widgetClone->id;
                    $metricClone->save();
                }
            }
        }

        // Clone filters
        foreach ($this->filters as $filter) {
            $filterClone = $filter->replicate();
            $filterClone->dashboard_id = $clone->id;
            $filterClone->save();
        }

        return $clone;
    }

    /**
     * Generate a unique slug from the dashboard name.
     *
     * @param string $name
     * @return string
     */
    protected function generateSlug(string $name): string
    {
        $slug = str_replace(' ', '-', strtolower($name));
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $baseSlug = $slug;
        $count = 1;
        while ($this->where('slug', $slug)->where('user_id', $this->user_id)->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Set as the default dashboard for the user.
     *
     * @return bool
     */
    public function setAsDefault(): bool
    {
        // Remove default status from other dashboards
        $this->user->dashboards()->update(['is_default' => false]);

        // Set this as default
        $this->is_default = true;
        return $this->save();
    }

    /**
     * Activate the dashboard.
     *
     * @return bool
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Deactivate the dashboard.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Get dashboard statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_widgets' => $this->widgets()->count(),
            'active_widgets' => $this->widgets()->where('is_active', true)->count(),
            'total_metrics' => $this->metrics()->count(),
            'total_filters' => $this->filters()->count(),
            'active_filters' => $this->filters()->where('is_active', true)->count(),
        ];
    }

    /**
     * Export dashboard configuration.
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'configuration' => $this->configuration,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'widgets' => $this->widgets()->with('metrics')->get()->toArray(),
            'filters' => $this->filters()->get()->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
