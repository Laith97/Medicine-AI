import sys
import json
import os
from transformers import AutoTokenizer, AutoModelForTokenClassification, AutoModelForSequenceClassification, pipeline
import torch

# Model paths
ICD10_NER_MODEL_PATH = os.getenv('ICD10_NER_MODEL_PATH', './models/icd10_ner')
CPT_CLASSIFIER_MODEL_PATH = os.getenv('CPT_CLASSIFIER_MODEL_PATH', './models/cpt_classifier')

# Code mapping files
ICD10_CODES_FILE = os.getenv('ICD10_CODES_FILE', 'icd10_codes.json')
CPT_CODES_FILE = os.getenv('CPT_CODES_FILE', 'cpt_codes.json')

# Processing limits
MAX_TEXT_LENGTH = int(os.getenv('MAX_TEXT_LENGTH', '10000'))

# Load models and mappings with error handling
try:
    # Load ICD-10 NER model
    icd10_tokenizer = AutoTokenizer.from_pretrained(ICD10_NER_MODEL_PATH)
    icd10_model = AutoModelForTokenClassification.from_pretrained(ICD10_NER_MODEL_PATH)
    icd10_ner = pipeline('token-classification', model=icd10_model, tokenizer=icd10_tokenizer, aggregation_strategy="simple")
except Exception as e:
    print(json.dumps({"error": f"Failed to load ICD-10 NER model: {str(e)}"}))
    sys.exit(1)

try:
    # Load CPT classifier
    cpt_tokenizer = AutoTokenizer.from_pretrained(CPT_CLASSIFIER_MODEL_PATH)
    cpt_model = AutoModelForSequenceClassification.from_pretrained(CPT_CLASSIFIER_MODEL_PATH)
    cpt_classifier = pipeline('text-classification', model=cpt_model, tokenizer=cpt_tokenizer)
except Exception as e:
    print(json.dumps({"error": f"Failed to load CPT classifier model: {str(e)}"}))
    sys.exit(1)

# Load code mappings with error handling
try:
    with open(ICD10_CODES_FILE, 'r') as f:
        icd10_map = json.load(f)
except Exception as e:
    print(json.dumps({"error": f"Failed to load ICD-10 codes mapping: {str(e)}"}))
    sys.exit(1)

try:
    with open(CPT_CODES_FILE, 'r') as f:
        cpt_map = json.load(f)
except Exception as e:
    print(json.dumps({"error": f"Failed to load CPT codes mapping: {str(e)}"}))
    sys.exit(1)

def suggest_codes(clinical_text):
    try:
        # ICD-10 prediction
        icd10_entities = icd10_ner(clinical_text)
        suggested_icd10 = []
        for entity in icd10_entities:
            if 'word' in entity and entity['word']:
                code = entity['word'].strip()
                if code in icd10_map:
                    suggested_icd10.append({
                        'code': code,
                        'description': icd10_map.get(code, 'Unknown description')
                    })
    except Exception as e:
        print(json.dumps({"error": f"ICD-10 prediction failed: {str(e)}"}))
        suggested_icd10 = []

    try:
        # CPT prediction
        cpt_result = cpt_classifier(clinical_text)
        suggested_cpt = []
        if cpt_result and len(cpt_result) > 0:
            cpt_label = cpt_result[0].get('label', '').strip()
            if cpt_label and cpt_label in cpt_map:
                suggested_cpt.append({
                    'code': cpt_label,
                    'description': cpt_map.get(cpt_label, 'Unknown description')
                })
    except Exception as e:
        print(json.dumps({"error": f"CPT prediction failed: {str(e)}"}))
        suggested_cpt = []

    return {
        'suggested_icd10': suggested_icd10,
        'suggested_cpt': suggested_cpt
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No clinical text provided"}))
        sys.exit(1)

    clinical_text = sys.argv[1]

    # Validate input
    if not clinical_text or not clinical_text.strip():
        print(json.dumps({"error": "Clinical text cannot be empty"}))
        sys.exit(1)

    if len(clinical_text) > MAX_TEXT_LENGTH:
        print(json.dumps({"error": f"Clinical text too long (max {MAX_TEXT_LENGTH} characters)"}))
        sys.exit(1)

    # Sanitize input
    clinical_text = clinical_text.replace('\0', '').replace('\r', '').replace('\n', '')

    try:
        result = suggest_codes(clinical_text)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": f"Processing failed: {str(e)}"}))
        sys.exit(1)
