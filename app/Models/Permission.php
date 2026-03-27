<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'route_pattern',
        'category',
        'is_restricted',
        'sort_order',
    ];

    protected $casts = [
        'is_restricted' => 'boolean',
    ];

    /**
     * Users who have this permission
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withPivot('granted_by')
                    ->withTimestamps();
    }

    /**
     * Check if this permission matches a given route
     */
    public function matchesRoute(string $routeName): bool
    {
        if (!$this->route_pattern) {
            return false;
        }

        // Exact match
        if ($this->route_pattern === $routeName) {
            return true;
        }

        // Wildcard match (e.g., 'appointments.*' matches 'appointments.index')
        // but NOT 'appointments.foo.bar' (nested routes)
        if (str_ends_with($this->route_pattern, '.*')) {
            $prefix = str_replace('.*', '', $this->route_pattern);

            // Must match prefix exactly and either end there or be followed by a dot
            // This prevents 'doctor.*' from matching 'doctor.foo.bar'
            if (str_starts_with($routeName, $prefix)) {
                $remaining = substr($routeName, strlen($prefix));
                return $remaining === '' || str_starts_with($remaining, '.');
            }
        }

        return false;
    }

    /**
     * Get permissions by category
     */
    public static function getByCategory(string $category)
    {
        return static::where('category', $category)
                    ->orderBy('sort_order')
                    ->orderBy('display_name')
                    ->get();
    }

    /**
     * Get non-restricted permissions (available for sub-users)
     */
    public static function getAvailableForSubUsers()
    {
        return static::where('is_restricted', false)
                    ->orderBy('category')
                    ->orderBy('sort_order')
                    ->orderBy('display_name')
                    ->get();
    }

    /**
     * Get restricted permissions (only for main users)
     */
    public static function getRestrictedPermissions()
    {
        return static::where('is_restricted', true)
                    ->orderBy('sort_order')
                    ->orderBy('display_name')
                    ->get();
    }
}