# Advanced Analytics Dashboards - Data Models & Relationships

## Overview
This document defines the data models and relationships for the Advanced Analytics Dashboards feature. It includes both the operational database models and the analytics data warehouse models with their relationships.

## Operational Database Models

### Core Entity Models

#### User Model
```php
class User extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'role', 'hospital_id',
        'department_id', 'specialty', 'subscription_plan',
        'trial_start_date', 'trial_end_date', 'is_active'
    ];

    // Relationships
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function patients()
    {
        return $this->hasMany(PatientData::class, 'assigned_doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'doctor_id');
    }
}
```

#### PatientData Model
```php
class PatientData extends Model
{
    protected $fillable = [
        'patient_key', 'first_name', 'last_name', 'date_of_birth',
        'gender', 'ethnicity', 'primary_language', 'insurance_provider',
        'insurance_plan_type', 'assigned_doctor_id', 'hospital_id',
        'chronic_conditions', 'allergies', 'risk_score'
    ];

    // Relationships
    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_key', 'patient_key');
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class, 'patient_key', 'patient_key');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'patient_key', 'patient_key');
    }
}
```

#### Appointment Model
```php
class Appointment extends Model
{
    protected $fillable = [
        'patient_key', 'doctor_id', 'hospital_id', 'scheduled_date',
        'scheduled_time', 'actual_start_time', 'actual_end_time',
        'status', 'appointment_type', 'service_type', 'wait_time_minutes',
        'consultation_duration_minutes', 'patient_satisfaction_score',
        'total_cost', 'insurance_covered_amount', 'patient_paid_amount'
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(PatientData::class, 'patient_key', 'patient_key');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function diagnosis()
    {
        return $this->hasOne(Diagnosis::class);
    }
}
```

#### Diagnosis Model
```php
class Diagnosis extends Model
{
    protected $fillable = [
        'patient_key', 'appointment_id', 'doctor_id', 'diagnosis_code',
        'diagnosis_name', 'severity', 'treatment_plan', 'outcome_score',
        'follow_up_required', 'follow_up_completed', 'notes'
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(PatientData::class, 'patient_key', 'patient_key');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
```

#### Claim Model
```php
class Claim extends Model
{
    protected $fillable = [
        'patient_key', 'appointment_id', 'diagnosis_id', 'claim_number',
        'insurance_provider_id', 'submitted_amount', 'approved_amount',
        'paid_amount', 'denial_reason', 'status', 'submitted_date',
        'processed_date', 'paid_date'
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(PatientData::class, 'patient_key', 'patient_key');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }
}
```

#### Hospital Model
```php
class Hospital extends Model
{
    protected $fillable = [
        'name', 'type', 'location', 'bed_count', 'specialty_focus',
        'accreditation_status', 'is_active'
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function patients()
    {
        return $this->hasMany(PatientData::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
```

## Analytics Data Warehouse Models

### Dimension Models

#### DimUser Model
```php
class DimUser extends Model
{
    protected $table = 'dim_user';
    protected $primaryKey = 'user_key';

    protected $fillable = [
        'user_id', 'email', 'role', 'specialty', 'department',
        'hospital_id', 'subscription_plan', 'subscription_status',
        'trial_start_date', 'trial_end_date', 'created_date',
        'updated_date', 'is_active', 'effective_start_date',
        'effective_end_date', 'version'
    ];

    // SCD Type 2 implementation
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('effective_end_date', '9999-12-31');
    }
}
```

#### DimPatient Model
```php
class DimPatient extends Model
{
    protected $table = 'dim_patient';
    protected $primaryKey = 'patient_key';

    protected $fillable = [
        'patient_id', 'patient_key', 'date_of_birth', 'gender',
        'ethnicity', 'primary_language', 'insurance_provider',
        'insurance_plan_type', 'risk_score', 'chronic_conditions',
        'allergies', 'primary_doctor_id', 'hospital_id',
        'first_visit_date', 'last_visit_date', 'total_visits',
        'is_active', 'effective_start_date', 'effective_end_date', 'version'
    ];

    // Relationships
    public function primaryDoctor()
    {
        return $this->belongsTo(DimUser::class, 'primary_doctor_id', 'user_key');
    }

    public function hospital()
    {
        return $this->belongsTo(DimHospital::class, 'hospital_key');
    }
}
```

#### DimDate Model
```php
class DimDate extends Model
{
    protected $table = 'dim_date';
    protected $primaryKey = 'date_key';

    protected $fillable = [
        'date', 'year', 'quarter', 'month', 'month_name',
        'week_of_year', 'day_of_week', 'day_name', 'is_weekend',
        'is_holiday', 'fiscal_year', 'fiscal_quarter'
    ];

    // Helper methods
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    public function scopeLast30Days($query)
    {
        $startDate = now()->subDays(30);
        return $query->where('date', '>=', $startDate);
    }
}
```

### Fact Models

#### FactAppointments Model
```php
class FactAppointments extends Model
{
    protected $table = 'fact_appointments';
    protected $primaryKey = 'appointment_key';

    protected $fillable = [
        'date_key', 'time_key', 'patient_key', 'doctor_key',
        'hospital_key', 'service_key', 'appointment_id',
        'scheduled_date', 'scheduled_time', 'actual_start_time',
        'actual_end_time', 'status', 'appointment_type',
        'booking_method', 'wait_time_minutes', 'consultation_duration_minutes',
        'patient_satisfaction_score', 'doctor_notes', 'total_cost',
        'insurance_covered_amount', 'patient_paid_amount'
    ];

    // Relationships
    public function date()
    {
        return $this->belongsTo(DimDate::class, 'date_key');
    }

    public function time()
    {
        return $this->belongsTo(DimTime::class, 'time_key');
    }

    public function patient()
    {
        return $this->belongsTo(DimPatient::class, 'patient_key');
    }

    public function doctor()
    {
        return $this->belongsTo(DimUser::class, 'doctor_key');
    }

    public function hospital()
    {
        return $this->belongsTo(DimHospital::class, 'hospital_key');
    }

    public function service()
    {
        return $this->belongsTo(DimService::class, 'service_key');
    }
}
```

#### FactFinancialTransactions Model
```php
class FactFinancialTransactions extends Model
{
    protected $table = 'fact_financial_transactions';
    protected $primaryKey = 'transaction_key';

    protected $fillable = [
        'date_key', 'patient_key', 'doctor_key', 'hospital_key',
        'transaction_id', 'transaction_date', 'transaction_type',
        'payment_method', 'amount', 'tax_amount', 'discount_amount',
        'insurance_adjustment', 'net_amount', 'claim_id', 'invoice_id',
        'subscription_id', 'description', 'status', 'processed_at'
    ];

    // Relationships
    public function date()
    {
        return $this->belongsTo(DimDate::class, 'date_key');
    }

    public function patient()
    {
        return $this->belongsTo(DimPatient::class, 'patient_key');
    }

    public function doctor()
    {
        return $this->belongsTo(DimUser::class, 'doctor_key');
    }

    public function hospital()
    {
        return $this->belongsTo(DimHospital::class, 'hospital_key');
    }
}
```

#### FactClinicalOutcomes Model
```php
class FactClinicalOutcomes extends Model
{
    protected $table = 'fact_clinical_outcomes';
    protected $primaryKey = 'outcome_key';

    protected $fillable = [
        'date_key', 'patient_key', 'doctor_key', 'service_key',
        'diagnosis_id', 'outcome_date', 'diagnosis_code', 'procedure_code',
        'outcome_category', 'outcome_score', 'length_of_stay_days',
        'readmission_within_30_days', 'complication_occurred',
        'patient_satisfaction', 'treatment_cost', 'follow_up_required',
        'follow_up_completed', 'notes'
    ];

    // Relationships
    public function date()
    {
        return $this->belongsTo(DimDate::class, 'date_key');
    }

    public function patient()
    {
        return $this->belongsTo(DimPatient::class, 'patient_key');
    }

    public function doctor()
    {
        return $this->belongsTo(DimUser::class, 'doctor_key');
    }

    public function service()
    {
        return $this->belongsTo(DimService::class, 'service_key');
    }
}
```

## Aggregate Models

#### AggDailyKpis Model
```php
class AggDailyKpis extends Model
{
    protected $table = 'agg_daily_kpis';
    protected $primaryKey = 'date_key';

    protected $fillable = [
        'date_key', 'hospital_key', 'total_appointments',
        'completed_appointments', 'cancelled_appointments',
        'no_show_appointments', 'average_wait_time_minutes',
        'average_consultation_duration', 'patient_satisfaction_score',
        'total_revenue', 'insurance_revenue', 'patient_revenue',
        'total_patients_seen', 'new_patients', 'returning_patients',
        'active_users', 'new_registrations'
    ];

    // Relationships
    public function date()
    {
        return $this->belongsTo(DimDate::class, 'date_key');
    }

    public function hospital()
    {
        return $this->belongsTo(DimHospital::class, 'hospital_key');
    }
}
```

#### AggMonthlyKpis Model
```php
class AggMonthlyKpis extends Model
{
    protected $table = 'agg_monthly_kpis';
    protected $primaryKey = ['year', 'month', 'hospital_key'];

    protected $fillable = [
        'year', 'month', 'hospital_key', 'total_appointments',
        'completed_appointments', 'revenue', 'patient_satisfaction',
        'provider_utilization', 'average_wait_time', 'churn_rate',
        'growth_rate', 'active_users', 'new_users'
    ];

    // Relationships
    public function hospital()
    {
        return $this->belongsTo(DimHospital::class, 'hospital_key');
    }
}
```

## Relationship Diagrams

### Operational Database ER Diagram
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Hospital  │     │    User     │     │ PatientData│
│             │     │             │     │             │
│ • id        │1───●│ • hospital_id│     │ • patient_key│
│ • name      │     │ • department│●───1│ • assigned_ │
│ • location  │     │ • role      │     │   doctor_id │
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │
       │                   │                   │
       ●                   ●                   ●
       │                   │                   │
       1                   1                   1
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Department  │     │ Appointment │     │ Diagnosis  │
│             │     │             │     │             │
│ • hospital_id│     │ • patient_key│     │ • patient_key│
│ • name      │     │ • doctor_id │     │ • appointment│
└─────────────┘     └─────────────┘     │ • doctor_id │
                                        └─────────────┘
                                               │
                                               │
                                               ●
                                               │
                                               1
                                        ┌─────────────┐
                                        │   Claim     │
                                        │             │
                                        │ • patient_key│
                                        │ • diagnosis_id│
                                        └─────────────┘
```

### Analytics Data Warehouse Star Schema
```
                    ┌─────────────────┐
                    │ FactAppointments│
                    │                 │
                    │ • appointment_key│
                    │ • date_key      │
                    │ • patient_key   │
                    │ • doctor_key    │
                    │ • hospital_key  │
                    │ • service_key   │
                    └─────────────────┘
                             │
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
    │   DimDate   │  │ DimPatient  │  │  DimDoctor  │
    │             │  │             │  │             │
    │ • date_key  │  │ • patient_key│  │ • doctor_key│
    │ • date      │  │ • patient_id │  │ • doctor_id │
    │ • year      │  │ • dob       │  │ • specialty │
    │ • month     │  │ • gender    │  │ • hospital_id│
    └─────────────┘  └─────────────┘  └─────────────┘
            │                │                │
            └────────────────┼────────────────┘
                             │
                    ┌─────────────┐
                    │ DimHospital │
                    │             │
                    │ • hospital_key│
                    │ • hospital_id│
                    │ • name      │
                    │ • type      │
                    └─────────────┘
```

## Data Flow Relationships

### ETL Process Flow
```
Operational DB ──► Staging Tables ──► Dimension Tables ──► Fact Tables ──► Aggregate Tables
     │                     │                     │                │                │
     │                     │                     │                │                │
     ▼                     ▼                     ▼                ▼                ▼
  MySQL Source      Data Cleansing      SCD Processing   Fact Loading   KPI Calculation
  (Laravel Models)   (Validation)       (Type 2)        (Grain Check)   (Business Logic)
```

### Real-Time Data Flow
```
Application Events ──► Kafka Topics ──► Flink Processing ──► Redis Cache ──► WebSocket ──► Dashboard
     │                        │                │                │                │
     │                        │                │                │                │
     ▼                        ▼                ▼                ▼                ▼
  User Actions         Event Streaming   Real-time KPIs   Low-latency     Live Updates
  (API Calls)          (Partitioned)     (Aggregations)   Storage         (Push Updates)
```

## Model Validation Rules

### Business Rules
- **Patient Key Uniqueness**: Each patient_key must be unique across the system
- **Appointment Status Flow**: Scheduled → Confirmed → In Progress → Completed/Cancelled
- **Doctor-Patient Assignment**: Patients can only be assigned to active doctors in the same hospital
- **Financial Consistency**: total_cost = insurance_covered + patient_paid + adjustments
- **Date Integrity**: effective_start_date < effective_end_date for SCD records

### Data Quality Rules
- **Completeness**: Required fields cannot be null
- **Accuracy**: Data matches source system validation rules
- **Consistency**: Referential integrity maintained across relationships
- **Timeliness**: Data loaded within defined SLA windows

## Indexing Strategy

### Dimension Table Indexes
```sql
-- DimUser indexes
CREATE INDEX idx_dim_user_active ON dim_user (is_active, effective_end_date);
CREATE INDEX idx_dim_user_hospital ON dim_user (hospital_id, role);
CREATE INDEX idx_dim_user_email ON dim_user (email);

-- DimPatient indexes
CREATE INDEX idx_dim_patient_active ON dim_patient (is_active, effective_end_date);
CREATE INDEX idx_dim_patient_doctor ON dim_patient (primary_doctor_id);
CREATE INDEX idx_dim_patient_hospital ON dim_patient (hospital_id);
```

### Fact Table Indexes
```sql
-- FactAppointments indexes
CREATE INDEX idx_fact_appt_date ON fact_appointments (date_key);
CREATE INDEX idx_fact_appt_patient ON fact_appointments (patient_key);
CREATE INDEX idx_fact_appt_doctor ON fact_appointments (doctor_key);
CREATE INDEX idx_fact_appt_hospital ON fact_appointments (hospital_key);
CREATE INDEX idx_fact_appt_status_date ON fact_appointments (status, date_key);

-- Composite indexes for common queries
CREATE INDEX idx_fact_appt_perf ON fact_appointments (hospital_key, date_key, status, patient_satisfaction_score);
```

### Aggregate Table Indexes
```sql
-- AggDailyKpis indexes
CREATE INDEX idx_agg_daily_date_hosp ON agg_daily_kpis (date_key, hospital_key);
CREATE INDEX idx_agg_daily_hosp_date ON agg_daily_kpis (hospital_key, date_key);

-- AggMonthlyKpis indexes
CREATE INDEX idx_agg_monthly_period ON agg_monthly_kpis (year, month);
CREATE INDEX idx_agg_monthly_hosp ON agg_monthly_kpis (hospital_key);
```

## Partitioning Strategy

### Fact Table Partitioning
```sql
-- Partition fact_appointments by month
PARTITION BY RANGE (YEAR(scheduled_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Partition fact_financial_transactions by quarter
PARTITION BY RANGE (QUARTER(transaction_date)) (
    PARTITION q1_2024 VALUES LESS THAN (5),
    PARTITION q2_2024 VALUES LESS THAN (9),
    PARTITION q3_2024 VALUES LESS THAN (13),
    PARTITION q4_2024 VALUES LESS THAN (17)
);
```

### Index Partitioning
- Local indexes on partitioned tables
- Global indexes for cross-partition queries
- Partition-wise joins for performance

## Data Archival Strategy

### Archive Criteria
- **Age-based**: Data older than 7 years moved to archive
- **Access-based**: Rarely accessed data archived after 2 years
- **Compliance**: Data retained per regulatory requirements

### Archive Process
1. Identify data for archival based on criteria
2. Create archive tables with same structure
3. Move data using partition exchange
4. Update indexes and constraints
5. Compress archived data
6. Update metadata tables

### Archive Access
- Separate archive database for old data
- Read-only access for compliance queries
- Uncompressed on-demand for analysis
- Automated restoration for audits
