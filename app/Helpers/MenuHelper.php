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
        // If admin OR hospital admin is impersonating, show all menu items without restrictions
        if (session()->has('impersonating_admin_id') || session()->has('impersonating_hospital_admin_id')) {
            return self::getMainUserMenuItemsWithoutRestrictions($user);
        }

        if ($user->isSubUser()) {
            return self::getSubUserMenuItems($user);
        }

        return self::getMainUserMenuItems($user);
    }

    /**
     * Get menu items for main users (doctors/patients/hospital_admins)
     */
    private static function getMainUserMenuItems(User $user): array
    {
        if ($user->isDoctor()) {
            return self::getDoctorMenuItems($user);
        }

        if ($user->isPatient()) {
            return self::getPatientMenuItems($user);
        }

        if ($user->isHospitalAdmin()) {
            return self::getHospitalAdminMenuItems($user);
        }

        return [];
    }

    /**
     * Get menu items for main users without restrictions (for admin impersonation)
     */
    private static function getMainUserMenuItemsWithoutRestrictions(User $user): array
    {
        if ($user->isDoctor()) {
            return self::getDoctorMenuItemsWithoutRestrictions($user);
        }

        if ($user->isPatient()) {
            return self::getPatientMenuItems($user);
        }

        if ($user->isHospitalAdmin()) {
            return self::getHospitalAdminMenuItems($user); // Hospital admins already have all permissions
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

            // Clinical Section
            [
                'name' => 'Clinical',
                'icon' => 'fas fa-tools',
                'dropdown' => true,
                'header_class' => 'sidebar-header-clinical',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    // AI Ask temporarily disabled - removed completely
                    [
                        'name' => 'Voice Assistant',
                        'route' => 'ai.voice-assistant.index',
                        'icon' => 'fas fa-microphone',
                        'permission' => 'voice_assistant',
                        'restricted' => true,
                    ],
                    [
                        'name' => 'Recorded Voices',
                        'route' => 'ai.voice-assistant.recorded-voices',
                        'icon' => 'fas fa-history',
                        'permission' => 'voice_assistant',
                        'restricted' => true,
                    ],
                    [
                        'name' => 'Diagnoses',
                        'route' => 'diagnosis.index',
                        'icon' => 'fas fa-stethoscope',
                        'permission' => 'diagnosis',
                    ],
                ]
            ],

            // Patients Section
            [
                'name' => 'Patients',
                'icon' => 'fas fa-users',
                'dropdown' => true,
                'header_class' => 'sidebar-header-patients',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Patient Records',
                        'route' => 'doctor.notes.index',
                        'icon' => 'fas fa-sticky-note',
                        'permission' => 'notes',
                    ],
                    [
                        'name' => 'Communications',
                        'route' => 'doctor.chat.index',
                        'icon' => 'fas fa-comments',
                        'permission' => 'chat',
                    ],
                    [
                        'name' => 'Patient Management',
                        'route' => 'doctor.patient-management.index',
                        'icon' => 'fas fa-folder',
                        'permission' => 'cases',
                    ],
                ]
            ],

            // Practice Section
            [
                'name' => 'Practice',
                'icon' => 'fas fa-calendar-alt',
                'dropdown' => true,
                'header_class' => 'sidebar-header-practice',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-left: 4px solid #2563eb; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
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
                        'name' => 'Appointment Settings',
                        'route' => 'doctor.settings.appointments',
                        'icon' => 'fas fa-cogs',
                        'permission' => 'appointments',
                    ],
                ]
            ],

            // Analytics & Insights Section
            [
                'name' => 'Analytics & Insights',
                'icon' => 'fas fa-chart-line',
                'dropdown' => true,
                'header_class' => 'sidebar-header-analytics',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Analytics',
                        'route' => 'doctor.analytics.index',
                        'icon' => 'fas fa-chart-bar',
                        'permission' => 'analytics',
                    ],
                    [
                        'name' => 'Reviews',
                        'route' => 'doctor.reviews.index',
                        'icon' => 'fas fa-star',
                        'permission' => 'reviews',
                    ],
                    [
                        'name' => 'Testimonials',
                        'route' => 'doctor.testimonials.index',
                        'icon' => 'fas fa-comments',
                        'permission' => 'testimonials',
                    ],
                ]
            ],

            // Account Section
            [
                'name' => 'Account',
                'icon' => 'fas fa-user-cog',
                'dropdown' => true,
                'header_class' => 'sidebar-header-account',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-left: 4px solid #6b7280; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Doctor Profile',
                        'route' => 'doctor.profile.edit',
                        'icon' => 'fas fa-user-md',
                        'permission' => 'profile',
                    ],
                ]
            ],

            // Business Tools Section
            [
                'name' => 'Business Tools',
                'icon' => 'fas fa-briefcase',
                'dropdown' => true,
                'header_class' => 'sidebar-header-business-tools',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #d97706; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => array_filter([
                    // Only show billing for standalone doctors (not hospital doctors)
                    !$user->hospital_id ? [
                        'name' => 'Billing & Invoices',
                        'route' => 'invoices.index',
                        'icon' => 'fas fa-file-invoice',
                        'permission' => 'invoices',
                    ] : null,
                    !$user->hospital_id ? [
                        'name' => 'Subscription',
                        'route' => 'subscription.manage',
                        'icon' => 'fas fa-credit-card',
                        'permission' => 'subscription',
                    ] : null,
                    !$user->hospital_id ? [
                        'name' => 'Pricing',
                        'route' => 'subscription.pricing',
                        'icon' => 'fas fa-tags',
                        'permission' => 'subscription',
                    ] : null,
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
                    [
                        'name' => 'Sub-Users',
                        'route' => 'sub-users.index',
                        'icon' => 'fas fa-users',
                        'permission' => 'sub_users',
                        'restricted' => true,
                    ],
                ])
            ],
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
     * Get menu items for doctors without restrictions (for admin impersonation)
     */
    private static function getDoctorMenuItemsWithoutRestrictions(User $user): array
    {
        $menuItems = [
            // Main Dashboard
            [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'fas fa-tachometer-alt',
            ],

            // Clinical Section - Show ALL items
            [
                'name' => 'Clinical',
                'icon' => 'fas fa-tools',
                'dropdown' => true,
                'header_class' => 'sidebar-header-clinical',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    // AI Ask temporarily disabled - removed completely
                    [
                        'name' => 'Voice Assistant',
                        'route' => 'ai.voice-assistant.index',
                        'icon' => 'fas fa-microphone',
                    ],
                    [
                        'name' => 'Recorded Voices',
                        'route' => 'ai.voice-assistant.recorded-voices',
                        'icon' => 'fas fa-history',
                    ],
                    [
                        'name' => 'Diagnoses',
                        'route' => 'diagnosis.index',
                        'icon' => 'fas fa-stethoscope',
                    ],
                ]
            ],

            // Patients Section
            [
                'name' => 'Patients',
                'icon' => 'fas fa-users',
                'dropdown' => true,
                'header_class' => 'sidebar-header-patients',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Patient Records',
                        'route' => 'doctor.notes.index',
                        'icon' => 'fas fa-sticky-note',
                    ],
                    [
                        'name' => 'Communications',
                        'route' => 'doctor.chat.index',
                        'icon' => 'fas fa-comments',
                    ],
                    [
                        'name' => 'Patient Management',
                        'route' => 'doctor.patient-management.index',
                        'icon' => 'fas fa-folder',
                    ],
                ]
            ],

            // Practice Section
            [
                'name' => 'Practice',
                'icon' => 'fas fa-calendar-alt',
                'dropdown' => true,
                'header_class' => 'sidebar-header-practice',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-left: 4px solid #2563eb; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Appointments',
                        'route' => 'doctor.appointments.index',
                        'icon' => 'fas fa-calendar',
                    ],
                    [
                        'name' => 'Availability',
                        'route' => 'doctor.availability.index',
                        'icon' => 'fas fa-clock',
                    ],
                    [
                        'name' => 'Appointment Settings',
                        'route' => 'doctor.settings.appointments',
                        'icon' => 'fas fa-cogs',
                    ],
                ]
            ],

            // Analytics & Insights Section
            [
                'name' => 'Analytics & Insights',
                'icon' => 'fas fa-chart-line',
                'dropdown' => true,
                'header_class' => 'sidebar-header-analytics',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Analytics',
                        'route' => 'doctor.analytics.index',
                        'icon' => 'fas fa-chart-bar',
                    ],
                    [
                        'name' => 'Reviews',
                        'route' => 'doctor.reviews.index',
                        'icon' => 'fas fa-star',
                    ],
                    [
                        'name' => 'Testimonials',
                        'route' => 'doctor.testimonials.index',
                        'icon' => 'fas fa-comments',
                    ],
                ]
            ],

            // Account Section
            [
                'name' => 'Account',
                'icon' => 'fas fa-user-cog',
                'dropdown' => true,
                'header_class' => 'sidebar-header-account',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-left: 4px solid #6b7280; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => [
                    [
                        'name' => 'Doctor Profile',
                        'route' => 'doctor.profile.edit',
                        'icon' => 'fas fa-user-md',
                    ],
                ]
            ],

            // Business Tools Section - Show restricted items but still respect hospital_id for billing
            [
                'name' => 'Business Tools',
                'icon' => 'fas fa-briefcase',
                'dropdown' => true,
                'header_class' => 'sidebar-header-business-tools',
                'header_style' => 'font-weight: 600; color: #1f2937; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #d97706; padding: 12px 16px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
                'items' => array_filter([
                    // Only show billing for standalone doctors (not hospital doctors) - even during impersonation
                    !$user->hospital_id ? [
                        'name' => 'Billing & Invoices',
                        'route' => 'invoices.index',
                        'icon' => 'fas fa-file-invoice',
                    ] : null,
                    !$user->hospital_id ? [
                        'name' => 'Subscription',
                        'route' => 'subscription.manage',
                        'icon' => 'fas fa-credit-card',
                    ] : null,
                    !$user->hospital_id ? [
                        'name' => 'Pricing',
                        'route' => 'subscription.pricing',
                        'icon' => 'fas fa-tags',
                    ] : null,
                    [
                        'name' => 'Landing Page',
                        'route' => 'doctor.landing-page.index',
                        'icon' => 'fas fa-globe',
                    ],
                    [
                        'name' => 'Blog Posts',
                        'route' => 'doctor.blog.index',
                        'icon' => 'fas fa-blog',
                    ],
                    [
                        'name' => 'Sub-Users',
                        'route' => 'sub-users.index',
                        'icon' => 'fas fa-users',
                    ],
                ])
            ],
        ];

        // Return all menu items without any filtering
        return $menuItems;
    }

    /**
     * Get menu items for patients
     */
    private static function getPatientMenuItems(User $user): array
    {
        return [
            [
                'name' => 'Find Care',
                'route' => 'doctors.index',
                'icon' => 'fas fa-user-md',
            ],
            [
                'name' => 'My Health',
                'icon' => 'fas fa-heartbeat',
                'dropdown' => true,
                'href' => 'appointments.index', // Clickable parent header
                'items' => [
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
                ]
            ],
        ];
    }

    /**
     * Get menu items for hospital admins
     */
    private static function getHospitalAdminMenuItems(User $user): array
    {
        $menuItems = [
            // Main Dashboard
            [
                'name' => 'Dashboard',
                'route' => 'hospital-admin.dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'dashboard',
            ],

            // Hospital Management Section
            [
                'name' => 'Hospital Management',
                'icon' => 'fas fa-hospital',
                'dropdown' => true,
                'href' => 'hospital-admin.doctors.index', // Clickable parent header
                'items' => [
                    [
                        'name' => 'Manage Doctors',
                        'route' => 'hospital-admin.doctors.index',
                        'icon' => 'fas fa-user-md',
                        'permission' => 'manage_doctors',
                    ],
                    [
                        'name' => 'Add Doctor',
                        'route' => 'hospital-admin.doctors.create',
                        'icon' => 'fas fa-plus',
                        'permission' => 'manage_doctors',
                    ],
                    [
                        'name' => 'Doctor Statistics',
                        'route' => 'hospital-admin.doctors.statistics',
                        'icon' => 'fas fa-chart-bar',
                        'permission' => 'manage_doctors',
                    ],
                    [
                        'name' => 'Hospital Profile',
                        'route' => 'hospital-admin.hospital.profile',
                        'icon' => 'fas fa-building',
                        'permission' => 'hospital_settings',
                    ],
                    [
                        'name' => 'Departments',
                        'route' => 'hospital-admin.departments.index',
                        'icon' => 'fas fa-sitemap',
                        'permission' => 'hospital_settings',
                    ],
                ]
            ],

            // Analytics & Reports Section
            [
                'name' => 'Analytics & Reports',
                'icon' => 'fas fa-chart-line',
                'dropdown' => true,
                'href' => 'hospital-admin.analytics.overview', // Clickable parent header
                'items' => [
                    [
                        'name' => 'Hospital Overview',
                        'route' => 'hospital-admin.analytics.overview',
                        'icon' => 'fas fa-chart-pie',
                        'permission' => 'analytics',
                    ],
                    [
                        'name' => 'Doctor Performance',
                        'route' => 'hospital-admin.analytics.doctors',
                        'icon' => 'fas fa-user-chart',
                        'permission' => 'analytics',
                    ],
                    [
                        'name' => 'Financial Reports',
                        'route' => 'hospital-admin.analytics.financial',
                        'icon' => 'fas fa-dollar-sign',
                        'permission' => 'analytics',
                    ],
                    [
                        'name' => 'Usage Reports',
                        'route' => 'hospital-admin.usage.index',
                        'icon' => 'fas fa-chart-area',
                        'permission' => 'billing',
                    ],
                ]
            ],

            // Administration Section
            [
                'name' => 'Administration',
                'icon' => 'fas fa-cogs',
                'dropdown' => true,
                'href' => 'hospital-admin.subscription.manage', // Clickable parent header
                'items' => [
                    [
                        'name' => 'Subscription',
                        'route' => 'hospital-admin.subscription.manage',
                        'icon' => 'fas fa-credit-card',
                        'permission' => 'billing',
                    ],
                    [
                        'name' => 'Pricing',
                        'route' => 'subscription.pricing',
                        'icon' => 'fas fa-tags',
                        'permission' => 'billing',
                    ],
                    [
                        'name' => 'Invoices',
                        'route' => 'hospital-admin.invoices.index',
                        'icon' => 'fas fa-file-invoice',
                        'permission' => 'billing',
                    ],
                ]
            ],
        ];

        // Filter menu items based on permissions (hospital admins have all permissions by default)
        return $menuItems;
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

        if ($user->isHospitalAdmin()) {
            return 'Hospital Admin';
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
            // 'ai.ask-ai' => [['name' => 'AI Assistant', 'route' => null]], // Temporarily disabled
            'ai.voice-assistant.index' => [['name' => 'Voice Assistant', 'route' => null]],
            'diagnosis.index' => [['name' => 'Diagnoses', 'route' => null]],
        ];

        if (isset($routeBreadcrumbs[$routeName])) {
            $breadcrumbs = array_merge($breadcrumbs, $routeBreadcrumbs[$routeName]);
        }

        return $breadcrumbs;
    }
}
