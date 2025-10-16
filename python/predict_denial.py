#!/usr/bin/env python3
"""
Claim Denial Prediction Script

This script loads a trained model and predicts denial risk for new claims.
Uses SHAP to explain the top contributing factors.
"""

import json
import pandas as pd
import numpy as np
from datetime import datetime
import joblib
import os
import sys
import shap

def load_model():
    """Load the trained model and feature names"""
    model_path = os.path.join(os.path.dirname(__file__), '..', 'models', 'denial_predictor_v1.joblib')
    feature_names_path = os.path.join(os.path.dirname(__file__), '..', 'models', 'feature_names_v1.json')

    if not os.path.exists(model_path):
        raise FileNotFoundError(f"Model file not found: {model_path}")

    if not os.path.exists(feature_names_path):
        raise FileNotFoundError(f"Feature names file not found: {feature_names_path}")

    model = joblib.load(model_path)

    with open(feature_names_path, 'r') as f:
        feature_names = json.load(f)

    return model, feature_names

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

def engineer_features_single(claim_data):
    """Engineer features for a single claim"""
    features = {}

    # Patient demographics
    features['patient_age'] = claim_data.get('patient_age', np.nan)
    if pd.isna(features['patient_age']):
        features['patient_age'] = 35  # Default median age

    gender = claim_data.get('patient_gender', 'unknown')
    # Note: In production, use the same LabelEncoder from training
    gender_mapping = {'male': 0, 'female': 1, 'other': 2, 'unknown': 3}
    features['patient_gender_encoded'] = gender_mapping.get(gender.lower(), 3)

    # Medical codes
    icd10_codes = claim_data.get('icd10_codes', [])
    features['icd10_count'] = len(icd10_codes) if isinstance(icd10_codes, list) else 0

    cpt_codes = claim_data.get('cpt_codes', [])
    features['cpt_count'] = len(cpt_codes) if isinstance(cpt_codes, list) else 0

    # Payer (simplified encoding - in production, use saved encoder)
    payer = claim_data.get('payer', 'unknown')
    # This is a placeholder - should use the same encoding as training
    features['payer_encoded'] = hash(payer) % 100  # Simple hash for demo

    # Provider
    features['provider_id'] = claim_data.get('primary_doctor_id', 0)

    # Claim details
    features['claim_amount'] = claim_data.get('expected_amount', 0)
    features['claim_length'] = calculate_claim_length(
        claim_data.get('service_date'),
        claim_data.get('submission_date')
    )
    if pd.isna(features['claim_length']):
        features['claim_length'] = 7  # Default median

    return features

def get_top_factors(model, features_df, feature_names, top_n=5):
    """Get top contributing factors using SHAP"""
    try:
        # Create SHAP explainer
        explainer = shap.TreeExplainer(model)

        # Get SHAP values for the prediction
        shap_values = explainer.shap_values(features_df)

        # For binary classification, shap_values might be a list
        if isinstance(shap_values, list):
            shap_values = shap_values[1]  # Take positive class values

        # Get feature importance for this prediction
        feature_importance = np.abs(shap_values[0])  # First (only) row

        # Get top contributing features
        top_indices = np.argsort(feature_importance)[-top_n:][::-1]
        top_factors = []

        for idx in top_indices:
            factor_name = feature_names[idx]
            importance = float(feature_importance[idx])
            feature_value = float(features_df.iloc[0, idx])

            # Convert encoded values back to readable names
            readable_name = get_readable_factor_name(factor_name, feature_value)

            top_factors.append({
                'factor': readable_name,
                'importance': importance,
                'value': feature_value
            })

        return top_factors

    except Exception as e:
        print(f"SHAP analysis failed: {e}", file=sys.stderr)
        return []

def get_readable_factor_name(factor_name, value):
    """Convert feature names to readable descriptions"""
    if factor_name == 'patient_age':
        return f"Patient Age: {int(value)} years"
    elif factor_name == 'patient_gender_encoded':
        genders = {0: 'Male', 1: 'Female', 2: 'Other', 3: 'Unknown'}
        return f"Patient Gender: {genders.get(int(value), 'Unknown')}"
    elif factor_name == 'icd10_count':
        return f"Number of ICD-10 Codes: {int(value)}"
    elif factor_name == 'cpt_count':
        return f"Number of CPT Codes: {int(value)}"
    elif factor_name == 'payer_encoded':
        return f"Payer Type: Encoded value {int(value)}"
    elif factor_name == 'provider_id':
        return f"Provider ID: {int(value)}"
    elif factor_name == 'claim_amount':
        return ".2f"
    elif factor_name == 'claim_length':
        return f"Claim Processing Time: {int(value)} days"
    else:
        return f"{factor_name}: {value}"

def main():
    if len(sys.argv) != 2:
        print("Usage: python predict_denial.py <claim_data.json>", file=sys.stderr)
        sys.exit(1)

    claim_data_path = sys.argv[1]
    claim_data = {}  # Initialize to empty dict to avoid unbound variable

    try:
        # Load claim data
        with open(claim_data_path, 'r') as f:
            claim_data = json.load(f)

        # Load model and feature names
        model, feature_names = load_model()

        # Engineer features
        features = engineer_features_single(claim_data)
        features_df = pd.DataFrame([features])

        # Ensure features are in the correct order
        features_df = features_df[feature_names]

        # Make prediction
        denial_probability = model.predict_proba(features_df)[0, 1]

        # Get top factors
        top_factors = get_top_factors(model, features_df, feature_names)

        # Prepare response
        response = {
            'claim_id': claim_data.get('claim_id'),
            'denial_risk': float(denial_probability),
            'top_factors': top_factors
        }

        # Output JSON response
        print(json.dumps(response, indent=2))

    except Exception as e:
        error_response = {
            'error': str(e),
            'claim_id': claim_data.get('claim_id'),
            'denial_risk': None,
            'top_factors': []
        }
        print(json.dumps(error_response, indent=2), file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
