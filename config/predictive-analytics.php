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
    | Includes common medical abbreviations and terminology variations
    |
    */

    'high_risk_conditions' => [
        // Diabetes
        'diabetes', 'diabetes mellitus', 't1dm', 't2dm', 'diabetic',
        
        // Cardiovascular
        'hypertension', 'high blood pressure', 'htn',
        'heart disease', 'coronary artery disease', 'cad', 
        'heart failure', 'chf', 'congestive heart failure',
        'myocardial infarction', 'heart attack', 'mi',
        'arrhythmia', 'atrial fibrillation', 'afib',
        
        // Cerebrovascular
        'stroke', 'cva', 'cerebrovascular accident', 'tia',
        
        // Respiratory
        'copd', 'chronic obstructive pulmonary disease',
        'asthma', 'emphysema', 'chronic bronchitis',
        
        // Renal
        'kidney disease', 'renal failure', 'ckd', 'chronic kidney disease',
        'esrd', 'end stage renal disease', 'dialysis',
        
        // Oncology
        'cancer', 'carcinoma', 'lymphoma', 'leukemia', 
        'metastatic', 'malignancy', 'tumor',
        
        // Hepatic
        'cirrhosis', 'liver disease', 'hepatic failure',
        
        // Immunological
        'immunocompromised', 'hiv', 'aids',
        
        // Metabolic
        'obesity', 'morbid obesity', 'bmi >40',
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
