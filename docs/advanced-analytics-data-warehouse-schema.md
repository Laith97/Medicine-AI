# Advanced Analytics Dashboards - Data Warehouse Schema

## Overview
This document defines the data warehouse schema for the Advanced Analytics Dashboards feature. The schema is designed to support efficient querying, aggregation, and real-time analytics for healthcare KPIs.

## Architecture Principles

### Design Approach
- **Star Schema**: Fact tables surrounded by dimension tables for optimal query performance
- **Slowly Changing Dimensions (SCD)**: Type 2 for historical tracking
- **Fact Table Granularity**: Event-level granularity for maximum flexibility
- **Partitioning Strategy**: Time-based partitioning for performance and maintenance

### Data Layers
1. **Staging Layer**: Raw data ingestion and cleansing
2. **Core Layer**: Cleaned, transformed data in star schema
3. **Aggregation Layer**: Pre-computed summaries and KPIs
4. **Serving Layer**: Optimized views for dashboard consumption

## Dimension Tables

### dim_date
```sql
CREATE TABLE dim_date (
    date_key INT PRIMARY KEY,
    date DATE NOT NULL,
    year INT NOT NULL,
    quarter INT NOT NULL,
    month INT NOT NULL,
    month_name VARCHAR(20) NOT NULL,
    week_of_year INT NOT NULL,
    day_of_week INT NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    is_weekend BOOLEAN NOT NULL,
    is_holiday BOOLEAN NOT NULL,
    fiscal_year INT,
    fiscal_quarter INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_time
```sql
CREATE TABLE dim_time (
    time_key INT PRIMARY KEY,
    time TIME NOT NULL,
    hour INT NOT NULL,
    minute INT NOT NULL,
    hour_of_day INT NOT NULL,
    time_of_day VARCHAR(20) NOT NULL, -- Morning, Afternoon, Evening, Night
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_user
```sql
CREATE TABLE dim_user (
    user_key INT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    specialty VARCHAR(100),
    department VARCHAR(100),
    hospital_id INT,
    subscription_plan VARCHAR(100),
    subscription_status VARCHAR(50),
    trial_start_date DATE,
    trial_end_date DATE,
    created_date DATE NOT NULL,
    updated_date DATE NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    effective_start_date DATE NOT NULL,
    effective_end_date DATE NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_patient
```sql
CREATE TABLE dim_patient (
    patient_key INT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_key VARCHAR(100) UNIQUE, -- External patient identifier
    date_of_birth DATE,
    gender VARCHAR(20),
    ethnicity VARCHAR(50),
    primary_language VARCHAR(50),
    insurance_provider VARCHAR(100),
    insurance_plan_type VARCHAR(50),
    risk_score DECIMAL(5,2),
    chronic_conditions TEXT, -- JSON array of conditions
    allergies TEXT, -- JSON array of allergies
    primary_doctor_id INT,
    hospital_id INT,
    first_visit_date DATE,
    last_visit_date DATE,
    total_visits INT DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    effective_start_date DATE NOT NULL,
    effective_end_date DATE NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_doctor
```sql
CREATE TABLE dim_doctor (
    doctor_key INT PRIMARY KEY,
    doctor_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(100),
    license_number VARCHAR(100),
    years_experience INT,
    hospital_id INT,
    department_id INT,
    consultation_fee DECIMAL(10,2),
    rating DECIMAL(3,2),
    total_reviews INT DEFAULT 0,
    availability_score DECIMAL(5,2), -- Percentage of time available
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    effective_start_date DATE NOT NULL,
    effective_end_date DATE NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_hospital
```sql
CREATE TABLE dim_hospital (
    hospital_key INT PRIMARY KEY,
    hospital_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50), -- General, Specialty, Clinic
    location VARCHAR(255),
    bed_count INT,
    specialty_focus TEXT, -- JSON array
    accreditation_status VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    effective_start_date DATE NOT NULL,
    effective_end_date DATE NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### dim_service
```sql
CREATE TABLE dim_service (
    service_key INT PRIMARY KEY,
    service_id INT NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    service_category VARCHAR(100),
    cpt_code VARCHAR(20),
    icd_code VARCHAR(20),
    average_duration_minutes INT,
    average_cost DECIMAL(10,2),
    reimbursement_rate DECIMAL(5,2),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    effective_start_date DATE NOT NULL,
    effective_end_date DATE NOT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Fact Tables

### fact_appointments
```sql
CREATE TABLE fact_appointments (
    appointment_key BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_key INT NOT NULL,
    time_key INT NOT NULL,
    patient_key INT NOT NULL,
    doctor_key INT NOT NULL,
    hospital_key INT NOT NULL,
    service_key INT,
    appointment_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    actual_start_time TIME,
    actual_end_time TIME,
    status VARCHAR(50) NOT NULL, -- Scheduled, Completed, Cancelled, No-show
    appointment_type VARCHAR(50),
    booking_method VARCHAR(50), -- Online, Phone, Walk-in
    wait_time_minutes INT,
    consultation_duration_minutes INT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_scheduled BOOLEAN DEFAULT FALSE,
    patient_satisfaction_score DECIMAL(3,2),
    doctor_notes TEXT,
    total_cost DECIMAL(10,2),
    insurance_covered_amount DECIMAL(10,2),
    patient_paid_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (date_key) REFERENCES dim_date(date_key),
    FOREIGN KEY (time_key) REFERENCES dim_time(time_key),
    FOREIGN KEY (patient_key) REFERENCES dim_patient(patient_key),
    FOREIGN KEY (doctor_key) REFERENCES dim_doctor(doctor_key),
    FOREIGN KEY (hospital_key) REFERENCES dim_hospital(hospital_key),
    FOREIGN KEY (service_key) REFERENCES dim_service(service_key)
);

-- Partition by month for performance
PARTITION BY RANGE (YEAR(scheduled_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

### fact_financial_transactions
```sql
CREATE TABLE fact_financial_transactions (
    transaction_key BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_key INT NOT NULL,
    patient_key INT NOT NULL,
    doctor_key INT,
    hospital_key INT,
    transaction_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type VARCHAR(50) NOT NULL, -- Payment, Refund, Adjustment
    payment_method VARCHAR(50), -- Credit Card, Insurance, Cash
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    insurance_adjustment DECIMAL(10,2) DEFAULT 0,
    net_amount DECIMAL(10,2) NOT NULL,
    claim_id INT,
    invoice_id INT,
    subscription_id INT,
    description TEXT,
    status VARCHAR(50) NOT NULL, -- Pending, Completed, Failed
    processed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (date_key) REFERENCES dim_date(date_key),
    FOREIGN KEY (patient_key) REFERENCES dim_patient(patient_key),
    FOREIGN KEY (doctor_key) REFERENCES dim_doctor(doctor_key),
    FOREIGN KEY (hospital_key) REFERENCES dim_hospital(hospital_key)
);

PARTITION BY RANGE (YEAR(transaction_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

### fact_clinical_outcomes
```sql
CREATE TABLE fact_clinical_outcomes (
    outcome_key BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_key INT NOT NULL,
    patient_key INT NOT NULL,
    doctor_key INT NOT NULL,
    service_key INT,
    diagnosis_id INT NOT NULL,
    outcome_date DATE NOT NULL,
    diagnosis_code VARCHAR(20),
    procedure_code VARCHAR(20),
    outcome_category VARCHAR(100), -- Successful, Complication, Readmission
    outcome_score DECIMAL(5,2), -- 0-1 scale
    length_of_stay_days INT,
    readmission_within_30_days BOOLEAN DEFAULT FALSE,
    complication_occurred BOOLEAN DEFAULT FALSE,
    patient_satisfaction DECIMAL(3,2),
    treatment_cost DECIMAL(10,2),
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (date_key) REFERENCES dim_date(date_key),
    FOREIGN KEY (patient_key) REFERENCES dim_patient(patient_key),
    FOREIGN KEY (doctor_key) REFERENCES dim_doctor(doctor_key),
    FOREIGN KEY (service_key) REFERENCES dim_service(service_key)
);

PARTITION BY RANGE (YEAR(outcome_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

### fact_user_activity
```sql
CREATE TABLE fact_user_activity (
    activity_key BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_key INT NOT NULL,
    time_key INT NOT NULL,
    user_key INT NOT NULL,
    session_id VARCHAR(255),
    activity_date DATE NOT NULL,
    activity_time TIME NOT NULL,
    activity_type VARCHAR(100) NOT NULL, -- Login, Page View, Feature Use
    page_url VARCHAR(500),
    feature_name VARCHAR(100),
    action VARCHAR(100),
    duration_seconds INT,
    device_type VARCHAR(50),
    browser VARCHAR(100),
    ip_address VARCHAR(45),
    location VARCHAR(255),
    referrer_url VARCHAR(500),
    campaign_source VARCHAR(100),
    campaign_medium VARCHAR(100),
    campaign_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (date_key) REFERENCES dim_date(date_key),
    FOREIGN KEY (time_key) REFERENCES dim_time(time_key),
    FOREIGN KEY (user_key) REFERENCES dim_user(user_key)
);

PARTITION BY RANGE (YEAR(activity_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

## Aggregate Tables

### agg_daily_kpis
```sql
CREATE TABLE agg_daily_kpis (
    date_key INT PRIMARY KEY,
    hospital_key INT,
    total_appointments INT DEFAULT 0,
    completed_appointments INT DEFAULT 0,
    cancelled_appointments INT DEFAULT 0,
    no_show_appointments INT DEFAULT 0,
    average_wait_time_minutes DECIMAL(8,2),
    average_consultation_duration DECIMAL(8,2),
    patient_satisfaction_score DECIMAL(5,2),
    total_revenue DECIMAL(12,2),
    insurance_revenue DECIMAL(12,2),
    patient_revenue DECIMAL(12,2),
    total_patients_seen INT DEFAULT 0,
    new_patients INT DEFAULT 0,
    returning_patients INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_registrations INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (date_key) REFERENCES dim_date(date_key),
    FOREIGN KEY (hospital_key) REFERENCES dim_hospital(hospital_key)
);
```

### agg_monthly_kpis
```sql
CREATE TABLE agg_monthly_kpis (
    year INT NOT NULL,
    month INT NOT NULL,
    hospital_key INT,
    total_appointments INT DEFAULT 0,
    completed_appointments INT DEFAULT 0,
    revenue DECIMAL(15,2),
    patient_satisfaction DECIMAL(5,2),
    provider_utilization DECIMAL(5,2),
    average_wait_time DECIMAL(8,2),
    churn_rate DECIMAL(5,2),
    growth_rate DECIMAL(5,2),
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (year, month, hospital_key),
    FOREIGN KEY (hospital_key) REFERENCES dim_hospital(hospital_key)
);
```

## Data Loading Strategy

### ETL Process
1. **Extract**: Pull data from source systems (MySQL, APIs, logs)
2. **Transform**: Clean, validate, and denormalize data
3. **Load**: Insert into staging tables, then core warehouse
4. **Aggregate**: Build summary tables for performance

### Incremental Loading
- Use change data capture (CDC) for real-time updates
- Timestamp-based incremental loads for batch processes
- Merge operations for slowly changing dimensions

### Data Quality
- Null value handling and defaults
- Data type validation and conversion
- Referential integrity checks
- Duplicate detection and removal

## Performance Optimization

### Indexing Strategy
- Composite indexes on common query patterns
- Bitmap indexes for low-cardinality columns
- Partition-wise indexes for partitioned tables

### Query Optimization
- Materialized views for complex aggregations
- Query result caching
- Parallel query execution
- Query rewrite for aggregate navigation

### Storage Optimization
- Columnar storage for analytical queries
- Compression for historical data
- Archive partitions for old data
- SSD storage for active partitions
