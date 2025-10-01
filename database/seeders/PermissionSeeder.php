<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Core permissions (available to sub-users)
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'description' => 'Access to main dashboard',
                'route_pattern' => 'dashboard',
                'category' => 'core',
                'is_restricted' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'settings',
                'display_name' => 'Settings',
                'description' => 'Access to user settings',
                'route_pattern' => 'settings',
                'category' => 'core',
                'is_restricted' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'cases',
                'display_name' => 'Patient Cases',
                'description' => 'View patient cases and records',
                'route_pattern' => 'cases',
                'category' => 'medical',
                'is_restricted' => false,
                'sort_order' => 3,
            ],

            // Appointment management (available to sub-users)
            [
                'name' => 'appointments',
                'display_name' => 'Appointments',
                'description' => 'Manage appointments',
                'route_pattern' => 'doctor.appointments.*',
                'category' => 'appointments',
                'is_restricted' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'availability',
                'display_name' => 'Availability Management',
                'description' => 'Manage doctor availability',
                'route_pattern' => 'doctor.availability.*',
                'category' => 'appointments',
                'is_restricted' => false,
                'sort_order' => 11,
            ],

            // Communication (available to sub-users)
            [
                'name' => 'reviews',
                'display_name' => 'Reviews Management',
                'description' => 'Manage patient reviews',
                'route_pattern' => 'doctor.reviews.*',
                'category' => 'communication',
                'is_restricted' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'chat',
                'display_name' => 'Chat Messages',
                'description' => 'Handle chat messages',
                'route_pattern' => 'doctor.chat.*',
                'category' => 'communication',
                'is_restricted' => false,
                'sort_order' => 21,
            ],

            // Content management (available to sub-users)
            [
                'name' => 'landing_page',
                'display_name' => 'Landing Page',
                'description' => 'Manage landing page content',
                'route_pattern' => 'doctor.landing-page.*',
                'category' => 'content',
                'is_restricted' => false,
                'sort_order' => 30,
            ],
            [
                'name' => 'blog',
                'display_name' => 'Blog Posts',
                'description' => 'Manage blog posts',
                'route_pattern' => 'doctor.blog.*',
                'category' => 'content',
                'is_restricted' => false,
                'sort_order' => 31,
            ],
            [
                'name' => 'notes',
                'display_name' => 'Notes',
                'description' => 'Manage doctor notes',
                'route_pattern' => 'doctor.notes.*',
                'category' => 'content',
                'is_restricted' => false,
                'sort_order' => 32,
            ],
            [
                'name' => 'profile',
                'display_name' => 'Profile Management',
                'description' => 'Manage doctor profile',
                'route_pattern' => 'doctor.profile.*',
                'category' => 'core',
                'is_restricted' => false,
                'sort_order' => 33,
            ],
            [
                'name' => 'appointment_settings',
                'display_name' => 'Appointment Settings',
                'description' => 'Manage appointment settings',
                'route_pattern' => 'doctor.settings.*',
                'category' => 'appointments',
                'is_restricted' => false,
                'sort_order' => 34,
            ],

            // Financial (available to sub-users)
            [
                'name' => 'invoices',
                'display_name' => 'Billing & Invoices',
                'description' => 'View billing and invoices',
                'route_pattern' => 'invoices.*',
                'category' => 'financial',
                'is_restricted' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'subscription',
                'display_name' => 'Subscription Management',
                'description' => 'Manage subscription',
                'route_pattern' => 'subscription.*',
                'category' => 'financial',
                'is_restricted' => false,
                'sort_order' => 41,
            ],

            // RESTRICTED PERMISSIONS (only for main users/doctors)
            [
                'name' => 'ai_assistant',
                'display_name' => 'AI Assistant',
                'description' => 'Access to AI diagnostic assistant',
                'route_pattern' => 'ai.ask-ai',
                'category' => 'restricted',
                'is_restricted' => true,
                'sort_order' => 100,
            ],
            [
                'name' => 'ai_openai',
                'display_name' => 'OpenAI Features',
                'description' => 'Access to OpenAI-powered features',
                'route_pattern' => 'openai.*',
                'category' => 'restricted',
                'is_restricted' => true,
                'sort_order' => 101,
            ],
            [
                'name' => 'voice_assistant',
                'display_name' => 'Voice Assistant',
                'description' => 'Access to voice-powered assistant',
                'route_pattern' => 'ai.voice-assistant.*',
                'category' => 'restricted',
                'is_restricted' => true,
                'sort_order' => 102,
            ],
            [
                'name' => 'diagnosis',
                'display_name' => 'Diagnoses',
                'description' => 'Create and manage diagnoses',
                'route_pattern' => 'diagnosis.*',
                'category' => 'restricted',
                'is_restricted' => true,
                'sort_order' => 103,
            ],
            [
                'name' => 'sub_users',
                'display_name' => 'Sub-User Management',
                'description' => 'Manage sub-users and their permissions',
                'route_pattern' => 'sub-users.*',
                'category' => 'restricted',
                'is_restricted' => true,
                'sort_order' => 104,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
