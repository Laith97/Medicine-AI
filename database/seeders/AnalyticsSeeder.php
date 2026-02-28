<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnalyticsRole;
use App\Models\DashboardPermission;
use App\Models\FeaturePermission;
use App\Models\KpiPermission;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define roles with hierarchy levels
        $roles = [
            [
                'role_name' => 'super_admin',
                'role_description' => 'Complete system access across all hospitals',
                'hierarchy_level' => 10,
            ],
            [
                'role_name' => 'system_admin',
                'role_description' => 'Administrative access to system operations',
                'hierarchy_level' => 9,
            ],
            [
                'role_name' => 'hospital_admin',
                'role_description' => 'Administrative control over a specific hospital',
                'hierarchy_level' => 8,
            ],
            [
                'role_name' => 'department_head',
                'role_description' => 'Leadership role within a specific department',
                'hierarchy_level' => 7,
            ],
            [
                'role_name' => 'senior_doctor',
                'role_description' => 'Experienced physician with supervisory responsibilities',
                'hierarchy_level' => 6,
            ],
            [
                'role_name' => 'doctor',
                'role_description' => 'Licensed physician',
                'hierarchy_level' => 5,
            ],
            [
                'role_name' => 'nurse',
                'role_description' => 'Registered nursing professional',
                'hierarchy_level' => 4,
            ],
            [
                'role_name' => 'medical_assistant',
                'role_description' => 'Clinical support staff',
                'hierarchy_level' => 3,
            ],
            [
                'role_name' => 'receptionist',
                'role_description' => 'Front desk and administrative support',
                'hierarchy_level' => 2,
            ],
            [
                'role_name' => 'practice_manager',
                'role_description' => 'Administrative management of medical practice',
                'hierarchy_level' => 7,
            ],
            [
                'role_name' => 'billing_manager',
                'role_description' => 'Revenue cycle management',
                'hierarchy_level' => 6,
            ],
            [
                'role_name' => 'compliance_officer',
                'role_description' => 'Regulatory compliance and audit oversight',
                'hierarchy_level' => 8,
            ],
        ];

        // Create roles
        $roleMap = [];
        foreach ($roles as $roleData) {
            $role = AnalyticsRole::updateOrCreate(
                ['role_name' => $roleData['role_name']],
                $roleData
            );
            $roleMap[$roleData['role_name']] = $role;
        }

        // Dashboard permissions matrix
        $dashboardPermissions = [
            'super_admin' => [
                'executive' => ['access_level' => 'full', 'data_scope' => 'system'],
                'revenue' => ['access_level' => 'full', 'data_scope' => 'system'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'system'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'system'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'system'],
            ],
            'system_admin' => [
                'executive' => ['access_level' => 'full', 'data_scope' => 'system'],
                'revenue' => ['access_level' => 'full', 'data_scope' => 'system'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'system'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'system'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'system'],
            ],
            'hospital_admin' => [
                'executive' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'revenue' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'hospital'],
            ],
            'department_head' => [
                'executive' => ['access_level' => 'limited', 'data_scope' => 'department'],
                'revenue' => ['access_level' => 'limited', 'data_scope' => 'department'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'department'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'department'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'department'],
            ],
            'senior_doctor' => [
                'executive' => ['access_level' => 'limited', 'data_scope' => 'team'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'team'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'team'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'team'],
            ],
            'doctor' => [
                'executive' => ['access_level' => 'basic', 'data_scope' => 'personal'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'personal'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'personal'],
            ],
            'nurse' => [
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'team'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'team'],
                'clinical' => ['access_level' => 'limited', 'data_scope' => 'team'],
            ],
            'medical_assistant' => [
                'patient_experience' => ['access_level' => 'limited', 'data_scope' => 'team'],
                'operations' => ['access_level' => 'limited', 'data_scope' => 'team'],
            ],
            'receptionist' => [
                'executive' => ['access_level' => 'basic', 'data_scope' => 'department'],
                'patient_experience' => ['access_level' => 'basic', 'data_scope' => 'department'],
                'operations' => ['access_level' => 'basic', 'data_scope' => 'department'],
            ],
            'practice_manager' => [
                'executive' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'revenue' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'patient_experience' => ['access_level' => 'full', 'data_scope' => 'hospital'],
                'operations' => ['access_level' => 'full', 'data_scope' => 'hospital'],
            ],
            'billing_manager' => [
                'revenue' => ['access_level' => 'full', 'data_scope' => 'hospital'],
            ],
            'compliance_officer' => [
                'executive' => ['access_level' => 'full', 'data_scope' => 'system'],
                'clinical' => ['access_level' => 'full', 'data_scope' => 'system'],
            ],
        ];

        // Create dashboard permissions
        foreach ($dashboardPermissions as $roleName => $dashboards) {
            $role = $roleMap[$roleName];
            foreach ($dashboards as $dashboardName => $permissions) {
                DashboardPermission::updateOrCreate(
                    [
                        'role_id' => $role->role_id,
                        'dashboard_name' => $dashboardName,
                    ],
                    $permissions
                );
            }
        }

        // Feature permissions matrix
        $featurePermissions = [
            'super_admin' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'system_admin' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'hospital_admin' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'department_head' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'senior_doctor' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'doctor' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'nurse' => [
                'real_time_updates' => true,
                'export_data' => false,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'dashboard_customization' => true,
            ],
            'medical_assistant' => [
                'real_time_updates' => true,
                'export_data' => false,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'dashboard_customization' => true,
            ],
            'receptionist' => [
                'real_time_updates' => true,
                'custom_date_ranges' => true,
                'dashboard_customization' => true,
            ],
            'practice_manager' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'billing_manager' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
            'compliance_officer' => [
                'real_time_updates' => true,
                'export_data' => true,
                'custom_date_ranges' => true,
                'drill_down_analysis' => true,
                'alert_configuration' => true,
                'dashboard_customization' => true,
                'scheduled_reports' => true,
                'api_access' => true,
            ],
        ];

        // Create feature permissions
        foreach ($featurePermissions as $roleName => $features) {
            $role = $roleMap[$roleName];
            foreach ($features as $featureName => $canAccess) {
                FeaturePermission::updateOrCreate(
                    [
                        'role_id' => $role->role_id,
                        'feature_name' => $featureName,
                    ],
                    ['can_access' => $canAccess]
                );
            }
        }

        // KPI permissions matrix
        $kpiPermissions = [
            'super_admin' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
                'system_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'system_admin' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
                'system_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'hospital_admin' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'department_head' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'senior_doctor' => [
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'doctor' => [
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
            ],
            'nurse' => [
                'patient_satisfaction' => ['can_view' => true, 'can_export' => false],
                'operational_efficiency' => ['can_view' => true, 'can_export' => false],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => false],
                'quality_measures' => ['can_view' => true, 'can_export' => false],
            ],
            'medical_assistant' => [
                'patient_satisfaction' => ['can_view' => true, 'can_export' => false],
                'operational_efficiency' => ['can_view' => true, 'can_export' => false],
            ],
            'receptionist' => [
                'patient_satisfaction' => ['can_view' => true, 'can_export' => false],
                'operational_efficiency' => ['can_view' => true, 'can_export' => false],
            ],
            'practice_manager' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
                'patient_satisfaction' => ['can_view' => true, 'can_export' => true],
                'operational_efficiency' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
            ],
            'billing_manager' => [
                'revenue_metrics' => ['can_view' => true, 'can_export' => true],
            ],
            'compliance_officer' => [
                'compliance_metrics' => ['can_view' => true, 'can_export' => true],
                'clinical_outcomes' => ['can_view' => true, 'can_export' => true],
                'quality_measures' => ['can_view' => true, 'can_export' => true],
                'staff_performance' => ['can_view' => true, 'can_export' => true],
                'system_performance' => ['can_view' => true, 'can_export' => true],
            ],
        ];

        // Create KPI permissions
        foreach ($kpiPermissions as $roleName => $kpis) {
            $role = $roleMap[$roleName];
            foreach ($kpis as $kpiCategory => $permissions) {
                KpiPermission::updateOrCreate(
                    [
                        'role_id' => $role->role_id,
                        'kpi_category' => $kpiCategory,
                    ],
                    $permissions
                );
            }
        }
    }
}
