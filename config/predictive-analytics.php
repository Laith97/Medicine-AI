<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values used when patient data is missing or unavailable
    |
    */

    'defaults' => [
        'last_visit_days' => 365,
        'age' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | High Risk Conditions
    |--------------------------------------------------------------------------
    |
    | Medical conditions considered high-risk for hospitalization prediction
    |
    */

    'high_risk_conditions' => [
        'diabetes',
        'hypertension',
        'heart disease',
        'cancer',
        'stroke',
        'kidney disease',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ML model training and prediction
    |
    */

    'models' => [
        'no_show' => [
            'path' => 'app/models/no_show_model.rbx',
        ],
        'hospitalization' => [
            'path' => 'app/models/hospitalization_model.rbx',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for batch processing of predictions
    |
    */

    'batch' => [
        'chunk_size' => 100,
        'prediction_window_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for categorizing risk levels
    |
    */

    'thresholds' => [
        'low_risk' => 0.3,
        'medium_risk' => 0.7,
    ],
];
