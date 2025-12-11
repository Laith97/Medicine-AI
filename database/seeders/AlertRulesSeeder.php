<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AlertRule;

class AlertRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alertRules = [
            // System Health Alerts
            [
                'name' => 'Database Connection Failure',
                'description' => 'Alert when database connections fail',
                'event_type' => 'system_health',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'service',
                            'operator' => 'equals',
                            'value' => 'database'
                        ],
                        [
                            'field' => 'status',
                            'operator' => 'equals',
                            'value' => 'unhealthy'
                        ]
                    ]
                ],
                'severity_config' => [
                    'critical' => [
                        'status' => ['operator' => 'equals', 'value' => 'unhealthy']
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 5,
                            'channels' => ['email', 'sms'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ],
                        [
                            'delay_minutes' => 15,
                            'channels' => ['email', 'sms'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin'],
                                ['type' => 'role', 'value' => 'hospital_admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email', 'sms'],
                'is_active' => true,
                'priority' => 100,
                'cooldown_minutes' => 10,
                'metadata' => [
                    'category' => 'system_health',
                    'auto_resolve' => false,
                    'requires_acknowledgement' => true
                ]
            ],

            // Performance Alerts
            [
                'name' => 'High Response Time',
                'description' => 'Alert when API response times exceed threshold',
                'event_type' => 'performance',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'metric',
                            'operator' => 'equals',
                            'value' => 'response_time_p95'
                        ],
                        [
                            'field' => 'value',
                            'operator' => 'greater_than',
                            'value' => 2000
                        ]
                    ]
                ],
                'severity_config' => [
                    'high' => [
                        'value' => ['operator' => 'greater_than', 'value' => 2000]
                    ],
                    'critical' => [
                        'value' => ['operator' => 'greater_than', 'value' => 5000]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 10,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 80,
                'cooldown_minutes' => 30,
                'metadata' => [
                    'category' => 'performance',
                    'auto_resolve' => true,
                    'resolve_threshold' => 1500
                ]
            ],

            // Error Rate Alerts
            [
                'name' => 'High Error Rate',
                'description' => 'Alert when API error rate exceeds threshold',
                'event_type' => 'performance',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'metric',
                            'operator' => 'equals',
                            'value' => 'error_rate'
                        ],
                        [
                            'field' => 'value',
                            'operator' => 'greater_than',
                            'value' => 0.05
                        ]
                    ]
                ],
                'severity_config' => [
                    'medium' => [
                        'value' => ['operator' => 'greater_than', 'value' => 0.05]
                    ],
                    'high' => [
                        'value' => ['operator' => 'greater_than', 'value' => 0.10]
                    ],
                    'critical' => [
                        'value' => ['operator' => 'greater_than', 'value' => 0.20]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 5,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ],
                        [
                            'delay_minutes' => 15,
                            'channels' => ['email', 'sms'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin'],
                                ['type' => 'role', 'value' => 'hospital_admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 90,
                'cooldown_minutes' => 15,
                'metadata' => [
                    'category' => 'performance',
                    'auto_resolve' => true,
                    'resolve_threshold' => 0.02
                ]
            ],

            // Queue Health Alerts
            [
                'name' => 'Queue Processing Failure',
                'description' => 'Alert when queue job failure rate is high',
                'event_type' => 'queue_health',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'queue_name',
                            'operator' => 'not_in',
                            'value' => []
                        ],
                        [
                            'field' => 'failure_rate',
                            'operator' => 'greater_than',
                            'value' => 0.10
                        ]
                    ]
                ],
                'severity_config' => [
                    'medium' => [
                        'failure_rate' => ['operator' => 'greater_than', 'value' => 0.10]
                    ],
                    'high' => [
                        'failure_rate' => ['operator' => 'greater_than', 'value' => 0.25]
                    ],
                    'critical' => [
                        'failure_rate' => ['operator' => 'greater_than', 'value' => 0.50]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 10,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 70,
                'cooldown_minutes' => 20,
                'metadata' => [
                    'category' => 'queue_health',
                    'auto_resolve' => true,
                    'resolve_threshold' => 0.05
                ]
            ],

            // Business Logic Alerts
            [
                'name' => 'Appointment Booking Failure',
                'description' => 'Alert when appointment booking fails repeatedly',
                'event_type' => 'appointment_booking',
                'model_type' => 'App\\Models\\Appointment',
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'action',
                            'operator' => 'equals',
                            'value' => 'booking_failed'
                        ],
                        [
                            'field' => 'failure_count',
                            'operator' => 'greater_than',
                            'value' => 5
                        ]
                    ]
                ],
                'severity_config' => [
                    'medium' => [
                        'failure_count' => ['operator' => 'greater_than', 'value' => 5]
                    ],
                    'high' => [
                        'failure_count' => ['operator' => 'greater_than', 'value' => 15]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 15,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 60,
                'cooldown_minutes' => 30,
                'metadata' => [
                    'category' => 'business_logic',
                    'auto_resolve' => false,
                    'requires_acknowledgement' => true
                ]
            ],

            // Security Alerts
            [
                'name' => 'Failed Login Attempts',
                'description' => 'Alert on multiple failed login attempts from same IP',
                'event_type' => 'security',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'event_type',
                            'operator' => 'equals',
                            'value' => 'failed_login'
                        ],
                        [
                            'field' => 'attempt_count',
                            'operator' => 'greater_than',
                            'value' => 10
                        ]
                    ]
                ],
                'severity_config' => [
                    'medium' => [
                        'attempt_count' => ['operator' => 'greater_than', 'value' => 10]
                    ],
                    'high' => [
                        'attempt_count' => ['operator' => 'greater_than', 'value' => 25]
                    ],
                    'critical' => [
                        'attempt_count' => ['operator' => 'greater_than', 'value' => 50]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 5,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 95,
                'cooldown_minutes' => 15,
                'metadata' => [
                    'category' => 'security',
                    'auto_resolve' => false,
                    'requires_acknowledgement' => true
                ]
            ],

            // Resource Usage Alerts
            [
                'name' => 'High Memory Usage',
                'description' => 'Alert when application memory usage is high',
                'event_type' => 'resource_usage',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'resource',
                            'operator' => 'equals',
                            'value' => 'memory'
                        ],
                        [
                            'field' => 'usage_percent',
                            'operator' => 'greater_than',
                            'value' => 85
                        ]
                    ]
                ],
                'severity_config' => [
                    'medium' => [
                        'usage_percent' => ['operator' => 'greater_than', 'value' => 85]
                    ],
                    'high' => [
                        'usage_percent' => ['operator' => 'greater_than', 'value' => 95]
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 10,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 75,
                'cooldown_minutes' => 20,
                'metadata' => [
                    'category' => 'resource_usage',
                    'auto_resolve' => true,
                    'resolve_threshold' => 70
                ]
            ],

            // External Service Alerts
            [
                'name' => 'External Service Down',
                'description' => 'Alert when external services are unavailable',
                'event_type' => 'external_service',
                'model_type' => null,
                'conditions' => [
                    'operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'service_status',
                            'operator' => 'equals',
                            'value' => 'down'
                        ]
                    ]
                ],
                'severity_config' => [
                    'high' => [
                        'service_status' => ['operator' => 'equals', 'value' => 'down']
                    ]
                ],
                'escalation_rules' => [
                    'default' => [
                        [
                            'delay_minutes' => 5,
                            'channels' => ['email'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin']
                            ]
                        ],
                        [
                            'delay_minutes' => 20,
                            'channels' => ['email', 'sms'],
                            'recipients' => [
                                ['type' => 'role', 'value' => 'admin'],
                                ['type' => 'role', 'value' => 'hospital_admin']
                            ]
                        ]
                    ]
                ],
                'notification_channels' => ['email'],
                'is_active' => true,
                'priority' => 85,
                'cooldown_minutes' => 30,
                'metadata' => [
                    'category' => 'external_services',
                    'auto_resolve' => false,
                    'requires_acknowledgement' => true
                ]
            ]
        ];

        foreach ($alertRules as $ruleData) {
            AlertRule::create($ruleData);
        }
    }
}
