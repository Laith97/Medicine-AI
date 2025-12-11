<?php

namespace Database\Seeders;

use App\Models\ComplianceRule;
use Illuminate\Database\Seeder;

class ComplianceRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            // HIPAA Compliance Rules
            [
                'name' => 'HIPAA - Patient Data Access Logging',
                'description' => 'Log all access to patient data for HIPAA compliance',
                'rule_type' => 'hipaa',
                'event_type' => 'document_created',
                'model_type' => 'App\\Models\\PatientData',
                'conditions' => [
                    [
                        'field' => 'patient_id',
                        'operator' => 'is_not_null',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'patient_data_access',
                    ]
                ],
                'is_active' => true,
                'priority' => 100,
                'metadata' => [
                    'hipaa_section' => '164.308(a)(1)',
                    'requirement' => 'Access Control',
                ],
            ],
            [
                'name' => 'HIPAA - PHI Encryption Check',
                'description' => 'Ensure PHI data is properly encrypted',
                'rule_type' => 'hipaa',
                'event_type' => 'document_submitted',
                'model_type' => 'App\\Models\\ClearinghouseSubmission',
                'conditions' => [
                    [
                        'field' => 'edi_content',
                        'operator' => 'contains',
                        'value' => 'PHI',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'phi_encryption_check',
                    ]
                ],
                'is_active' => true,
                'priority' => 95,
                'metadata' => [
                    'hipaa_section' => '164.312(e)(1)',
                    'requirement' => 'Transmission Security',
                ],
            ],
            [
                'name' => 'HIPAA - Minimum Necessary Access',
                'description' => 'Ensure only minimum necessary data is accessed',
                'rule_type' => 'hipaa',
                'event_type' => 'document_updated',
                'model_type' => 'App\\Models\\PatientData',
                'conditions' => [
                    [
                        'field' => 'updated_fields',
                        'operator' => 'contains',
                        'value' => 'diagnosis',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'minimum_necessary_check',
                    ]
                ],
                'is_active' => true,
                'priority' => 90,
                'metadata' => [
                    'hipaa_section' => '164.502(b)',
                    'requirement' => 'Uses and Disclosures',
                ],
            ],

            // Evaluation Rules
            [
                'name' => 'Evaluation - Required Fields Check',
                'description' => 'Ensure all required fields are present in evaluations',
                'rule_type' => 'evaluation',
                'event_type' => 'document_submitted',
                'model_type' => 'App\\Models\\Diagnosis',
                'conditions' => [
                    [
                        'field' => 'diagnosis_text',
                        'operator' => 'is_null',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'block_operation',
                        'message' => 'Diagnosis text is required for evaluation submission',
                    ]
                ],
                'is_active' => true,
                'priority' => 80,
                'metadata' => [
                    'evaluation_type' => 'diagnosis',
                    'requirement' => 'Complete Documentation',
                ],
            ],
            [
                'name' => 'Evaluation - ICD-10 Code Validation',
                'description' => 'Validate ICD-10 codes in diagnosis submissions',
                'rule_type' => 'evaluation',
                'event_type' => 'document_created',
                'model_type' => 'App\\Models\\Diagnosis',
                'conditions' => [
                    [
                        'field' => 'icd10_codes',
                        'operator' => 'is_not_null',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'icd10_validation',
                    ]
                ],
                'is_active' => true,
                'priority' => 75,
                'metadata' => [
                    'evaluation_type' => 'coding',
                    'requirement' => 'Accurate Coding',
                ],
            ],
            [
                'name' => 'Evaluation - Treatment Plan Completeness',
                'description' => 'Ensure treatment plans include all required components',
                'rule_type' => 'evaluation',
                'event_type' => 'document_submitted',
                'model_type' => 'App\\Models\\HepProgram',
                'conditions' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'active',
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'treatment_plan_review',
                    ]
                ],
                'is_active' => true,
                'priority' => 70,
                'metadata' => [
                    'evaluation_type' => 'treatment',
                    'requirement' => 'Comprehensive Planning',
                ],
            ],

            // General Compliance Rules
            [
                'name' => 'Audit Trail - All Document Changes',
                'description' => 'Maintain audit trail for all document modifications',
                'rule_type' => 'custom',
                'event_type' => 'document_updated',
                'model_type' => 'App\\Models\\PatientData',
                'conditions' => [],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'document_change_audit',
                    ]
                ],
                'is_active' => true,
                'priority' => 50,
                'metadata' => [
                    'purpose' => 'Audit Trail',
                    'scope' => 'All Changes',
                ],
            ],
            [
                'name' => 'Data Integrity - Submission Validation',
                'description' => 'Validate data integrity before submission',
                'rule_type' => 'custom',
                'event_type' => 'document_submitted',
                'model_type' => 'App\\Models\\Claim',
                'conditions' => [
                    [
                        'field' => 'expected_amount',
                        'operator' => 'greater_than',
                        'value' => 0,
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'log_audit',
                        'event_type' => 'data_integrity_check',
                    ]
                ],
                'is_active' => true,
                'priority' => 45,
                'metadata' => [
                    'purpose' => 'Data Validation',
                    'scope' => 'Financial Data',
                ],
            ],
        ];

        foreach ($rules as $ruleData) {
            ComplianceRule::create($ruleData);
        }
    }
}
