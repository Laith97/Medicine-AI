#!/usr/bin/env python3
"""
Claim Denial Prediction Model Training Script

This script trains an XGBoost model to predict claim denial risk based on historical claims data.
Features include patient demographics, medical codes, payer information, and claim details.
"""

import json
import pandas as pd
import numpy as np
from datetime import datetime
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.metrics import classification_report, roc_auc_score
from xgboost import XGBClassifier
import joblib
import os
import sys

def load_normalized_data(filepath):
    """Load normalized claims data from JSON file"""
    with open(filepath, 'r') as f:
        data = json.load(f)

    # If data is a list of claims, convert to DataFrame
    if isinstance(data, list):
        df = pd.DataFrame(data)
    else:
        df = pd.DataFrame([data])  # Single claim

    return df

def calculate_age(birth_date_str):
    """Calculate age from birth date string"""
    if not birth_date_str:
        return np.nan
    try:
        birth_date = datetime.strptime(birth_date_str, '%Y-%m-%d')
        today = datetime.now()
        age = today.year - birth_date.year - ((today.month, today.day) < (birth_date.month, birth_date.day))
        return age
    except:
        return np.nan

def calculate_claim_length(service_date_str, submission_date_str):
    """Calculate claim processing length in days"""
    if not service_date_str or not submission_date_str:
        return np.nan
    try:
        service_date = datetime.strptime(service_date_str, '%Y-%m-%d')
        submission_date = datetime.strptime(submission_date_str, '%Y-%m-%d')
        return (submission_date - service_date).days
    except:
        return np.nan

def engineer_features(df):
    """Create features for the model"""
    # Patient demographics
    df['patient_age'] = df['patient_age'].fillna(df['patient_age'].median())
    df['patient_gender_encoded'] = LabelEncoder().fit_transform(df['patient_gender'].fillna('unknown'))

    # Medical codes
    df['icd10_count'] = df['icd10_codes'].apply(lambda x: len(x) if isinstance(x, list) else 0)
    df['cpt_count'] = df['cpt_codes'].apply(lambda x: len(x) if isinstance(x, list) else 0)

    # Payer encoding
    df['payer_encoded'] = LabelEncoder().fit_transform(df['payer'].fillna('unknown'))

    # Provider (using primary_doctor_id as proxy)
    df['provider_id'] = df['primary_doctor_id'].fillna(0).astype(int)

    # Claim details
    df['claim_amount'] = df['expected_amount'].fillna(0)
    df['claim_length'] = df.apply(lambda row: calculate_claim_length(row['service_date'], row['submission_date']), axis=1)
    df['claim_length'] = df['claim_length'].fillna(df['claim_length'].median())

    return df

def prepare_training_data(df):
    """Prepare data for training"""
    # Define features
    feature_columns = [
        'patient_age',
        'patient_gender_encoded',
        'icd10_count',
        'cpt_count',
        'payer_encoded',
        'provider_id',
        'claim_amount',
        'claim_length'
    ]

    # Target: 1 for denied, 0 for approved
    df['target'] = df['claim_status'].apply(lambda x: 1 if x == 'denied' else 0)

    # Select features and target
    X = df[feature_columns]
    y = df['target']

    return X, y

def train_model(X_train, y_train, X_test, y_test):
    """Train XGBoost model"""
    # Initialize model with balanced class weights
    scale_pos_weight = len(y_train[y_train == 0]) / len(y_train[y_train == 1])
    model = XGBClassifier(
        n_estimators=100,
        max_depth=6,
        learning_rate=0.1,
        scale_pos_weight=scale_pos_weight,
        random_state=42,
        eval_metric='auc'
    )

    # Train model
    model.fit(X_train, y_train)

    # Evaluate
    y_pred = model.predict(X_test)
    y_pred_proba = model.predict_proba(X_test)[:, 1]

    print("Model Performance:")
    print(classification_report(y_test, y_pred))
    print(f"AUC-ROC: {roc_auc_score(y_test, y_pred_proba):.4f}")

    return model

def main():
    if len(sys.argv) != 2:
        print("Usage: python train_denial_predictor.py <normalized_data.json>")
        sys.exit(1)

    data_filepath = sys.argv[1]

    if not os.path.exists(data_filepath):
        print(f"Error: Data file {data_filepath} not found")
        sys.exit(1)

    # Load data
    print("Loading normalized claims data...")
    df = load_normalized_data(data_filepath)
    print(f"Loaded {len(df)} claims")

    # Engineer features
    print("Engineering features...")
    df = engineer_features(df)

    # Prepare training data
    print("Preparing training data...")
    X, y = prepare_training_data(df)

    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    print(f"Training set: {len(X_train)} samples")
    print(f"Test set: {len(X_test)} samples")

    # Train model
    print("Training model...")
    model = train_model(X_train, y_train, X_test, y_test)

    # Save model
    model_path = os.path.join(os.path.dirname(__file__), '..', 'models', 'denial_predictor_v1.joblib')
    os.makedirs(os.path.dirname(model_path), exist_ok=True)
    joblib.dump(model, model_path)
    print(f"Model saved to {model_path}")

    # Save feature names for prediction
    feature_names = list(X.columns)
    feature_names_path = os.path.join(os.path.dirname(__file__), '..', 'models', 'feature_names_v1.json')
    with open(feature_names_path, 'w') as f:
        json.dump(feature_names, f)
    print(f"Feature names saved to {feature_names_path}")

if __name__ == "__main__":
    main()
