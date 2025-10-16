# Configuration file for Python ML scripts
import os

# Model paths
ICD10_NER_MODEL_PATH = os.getenv('ICD10_NER_MODEL_PATH', './models/icd10_ner')
CPT_CLASSIFIER_MODEL_PATH = os.getenv('CPT_CLASSIFIER_MODEL_PATH', './models/cpt_classifier')
DENIAL_PREDICTOR_MODEL_PATH = os.getenv('DENIAL_PREDICTOR_MODEL_PATH', './models/denial_predictor_v1.joblib')

# Code mapping files
ICD10_CODES_FILE = os.getenv('ICD10_CODES_FILE', 'icd10_codes.json')
CPT_CODES_FILE = os.getenv('CPT_CODES_FILE', 'cpt_codes.json')

# Processing limits
MAX_TEXT_LENGTH = int(os.getenv('MAX_TEXT_LENGTH', '10000'))
MAX_BATCH_SIZE = int(os.getenv('MAX_BATCH_SIZE', '32'))

# Model settings
DEVICE = os.getenv('DEVICE', 'cpu')  # 'cpu' or 'cuda'
