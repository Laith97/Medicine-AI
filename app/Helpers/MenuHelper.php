<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Permission;

class MenuHelper
{
    /**
     * Get menu items for the authenticated user
     */
    public static function getMenuItems(User $user): array
    {
        if ($user->isSubUser()) {
            return self::getSubUserMenuItems($user);
        }

        return self::getMainUserMenuItems($user);
    }

    /**
     * Get menu items for main users (doctors/patients)
     */
    private static function getMainUserMenuItems(User $user): array
    {
        if ($user->isDoctor()) {
            return self::getDoctorMenuItems($user);
        }

        if ($user->isPatient()) {
            return self::getPatientMenuItems($user);
        }

        return [];
    }

    /**
     * Get menu items for doctors
     */
    private static function getDoctorMenuItems(User $user): array
    {
        $menuItems = [
            // Main Dashboard
            [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'dashboard',
            ],
            
            // AI Tools Dropdown
            [
                'name' => 'AI Tools',
                'icon' => 'fas fa-robot',
                'dropdown' => true,
                'items' => [
                    [
                        'name' => 'AI Assistant',
                        'route' => 'ask-ai',
                        'icon' => 'fas fa-robot',
                        'permission' => 'ai_assistant',
                        'restricted' => true,
                    ],
                    [
                        'name' => 'Voice Assistant',
                        'route' => 'voice-assistant.index',
                        'icon' => 'fas fa-microphone',
                        'permission' => 'voice_assistant',
                        'restricted' => true,
                    ],
                    [
                        'name' => 'Diagnoses',
                        'route' => 'diagnosis.index',
                        'icon' => 'fas fa-stethoscope',
                        'permission' => 'diagnosis',
                        'restricted' => true,
                    ],
                ]
            ],

            // Patient Management
            [
                'name' => 'Patients',
                'icon' => 'fas fa-users',
                'dropdown' => true,
                'items' => [
                    [
                        'name' => 'Patient Cases',
                        'route' => 'cases',
                        'icon' => 'fas fa-folder-open',
                        'permission' => 'cases',
                    ],
                    [
                        'name' => 'My Notes',
                        'route' => 'doctor.notes.index',
                        'icon' => 'fas fa-sticky-note',
                        'permission' => 'notes',
                    ],
                    [
                        'name' => 'Chat Messages',
                        'route' => 'doctor.chat.index',
                        'icon' => 'fas fa-comments',
                        'permission' => 'chat',
                    ],
                ]
            ],

            // Appointments & Schedule
            [
                'name' => 'Schedule',
                'icon' => 'fas fa-calendar-alt',
                'dropdown' => true,
                'items' => [
                    [
                        'name' => 'Appointments',
                        'route' => 'doctor.appointments.index',
                        'icon' => 'fas fa-calendar',
                        'permission' => 'appointments',
                    ],
                    [
                        'name' => 'Availability',
                        'route' => 'doctor.availability.index',
                        'icon' => 'fas fa-clock',
                        'permission' => 'availability',
                    ],
                    [
                        'name' => 'Reviews',
                        'route' => 'doctor.reviews.index',
                        'icon' => 'fas fa-star',
                        'permission' => 'reviews',
                    ],
                ]
            ],

            // Online Presence
            [
                'name' => 'Online Presence',
                'icon' => 'fas fa-globe',
                'dropdown' => true,
                'items' => [
                    [
                        'name' => 'Landing Page',
                        'route' => 'doctor.landing-page.index',
                        'icon' => 'fas fa-globe',
                        'permission' => 'landing_page',
                    ],
                    [
                        'name' => 'Blog Posts',
                        'route' => 'doctor.blog.index',
                        'icon' => 'fas fa-blog',
                        'permission' => 'blog',
                    ],
                ]
            ],

            // Business Management
            [
                'name' => 'Business',
                'icon' => 'fas fa-briefcase',
                'dropdown' => true,
                'items' => [
                    [
                        'name' => 'Billing & Invoices',
                        'route' => 'invoices.index',
                        'icon' => 'fas fa-file-invoice',
                        'permission' => 'invoices',
                    ],
                    [
                        'name' => 'Subscription',
                        'route' => 'subscription.manage',
                        'icon' => 'fas fa-credit-card',
                        'permission' => 'subscription',
                    ],
                    [
                        'name' => 'Sub-Users',
                        'route' => 'sub-users.index',
                        'icon' => 'fas fa-users',
                        'permission' => 'sub_users',
                        'restricted' => true,
                    ],
                ]
            ],

            // Settings
           /* [
                'name' => 'Settings',
                'route' => 'settings',
                'icon' => 'fas fa-cog',
                'permission' => 'settings',
            ],*/
        ];

        // Filter menu items and their dropdown items based on permissions
        return array_filter(array_map(function ($item) use ($user) {
            if (isset($item['dropdown']) && isset($item['items'])) {
                // Filter dropdown items
                $filteredItems = array_filter($item['items'], function ($subItem) use ($user) {
                    return self::userCanAccessMenuItem($user, $subItem);
                });
                
                if (!empty($filteredItems)) {
                    $item['items'] = array_values($filteredItems);
                    return $item;
                }
                return null;
            } else {
                // Check if user can access this item
                return self::userCanAccessMenuItem($user, $item) ? $item : null;
            }
        }, $menuItems), function ($item) {
            return $item !== null;
        });
    }

    /**
     * Get menu items for patients
     */
    private static function getPatientMenuItems(User $user): array
    {
        return [
            [
                'name' => 'Find Doctors',
                'route' => 'doctors.index',
                'icon' => 'fas fa-user-md',
            ],
            [
                'name' => 'My Appointments',
                'route' => 'appointments.index',
                'icon' => 'fas fa-calendar',
            ],
            [
                'name' => 'My Diagnoses',
                'route' => 'diagnosis.patient.index',
                'icon' => 'fas fa-file-medical',
            ],
        ];
    }

    /**
     * Get menu items for sub-users based on their permissions
     */
    private static function getSubUserMenuItems(User $user): array
    {
        $userPermissions = $user->permissions->pluck('name')->toArray();
        $allMenuItems = self::getDoctorMenuItems($user->parentUser ?? $user);

        return self::filterMenuItemsByPermissions($allMenuItems, $userPermissions);
    }

    /**
     * Filter menu items based on user permissions
     */
    private static function filterMenuItemsByPermissions(array $menuItems, array $userPermissions): array
    {
        $filteredItems = [];

        foreach ($menuItems as $item) {
            if (isset($item['dropdown']) && isset($item['items'])) {
                // Filter dropdown items
                $filteredDropdownItems = self::filterMenuItemsByPermissions($item['items'], $userPermissions);
                
                if (!empty($filteredDropdownItems)) {
                    $item['items'] = $filteredDropdownItems;
                    $filteredItems[] = $item;
                }
            } else {
                // Check if user has permission for this item
                if (!isset($item['permission']) || in_array($item['permission'], $userPermissions)) {
                    // Skip restricted items for sub-users
                    if (!isset($item['restricted']) || !$item['restricted']) {
                        $filteredItems[] = $item;
                    }
                }
            }
        }

        return $filteredItems;
    }

    /**
     * Check if user can access a menu item
     */
    private static function userCanAccessMenuItem(User $user, array $item): bool
    {
        // If no permission specified, allow access
        if (!isset($item['permission'])) {
            return true;
        }

        // Check if user has the required permission
        return $user->hasPermission($item['permission']);
    }

    /**
     * Check if a route should be shown in navigation for the current user
     */
    public static function shouldShowRoute(string $routeName, User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        return $user->canAccessRoute($routeName);
    }

    /**
     * Get the user's role display name
     */
    public static function getUserRoleDisplay(User $user): string
    {
        if ($user->isSubUser()) {
            return ucfirst($user->sub_user_role ?? 'Sub User');
        }

        return ucfirst($user->role);
    }

    /**
     * Get breadcrumb items for the current route
     */
    public static function getBreadcrumbs(string $routeName, User $user): array
    {
        $breadcrumbs = [
            ['name' => 'Dashboard', 'route' => 'dashboard']
        ];

        // Add route-specific breadcrumbs
        $routeBreadcrumbs = [
            'sub-users.index' => [['name' => 'Sub-Users', 'route' => 'sub-users.index']],
            'sub-users.create' => [
                ['name' => 'Sub-Users', 'route' => 'sub-users.index'],
                ['name' => 'Create Sub-User', 'route' => null]
            ],
            'sub-users.edit' => [
                ['name' => 'Sub-Users', 'route' => 'sub-users.index'],
                ['name' => 'Edit Sub-User', 'route' => null]
            ],
            'ask-ai' => [['name' => 'AI Assistant', 'route' => null]],
            'voice-assistant.index' => [['name' => 'Voice Assistant', 'route' => null]],
            'diagnosis.index' => [['name' => 'Diagnoses', 'route' => null]],
            'cases' => [['name' => 'Patient Cases', 'route' => null]],
        ];

        if (isset($routeBreadcrumbs[$routeName])) {
            $breadcrumbs = array_merge($breadcrumbs, $routeBreadcrumbs[$routeName]);
        }

        return $breadcrumbs;
    }
}