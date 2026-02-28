import pandas as pd
from transformers import AutoTokenizer, AutoModelForSequenceClassification, Trainer, TrainingArguments
from sklearn.model_selection import train_test_split
import torch
from torch.utils.data import Dataset

# Custom Dataset for Sequence Classification
class CPTDataset(Dataset):
    def __init__(self, texts, labels, tokenizer, max_len=512):
        self.texts = texts
        self.labels = labels
        self.tokenizer = tokenizer
        self.max_len = max_len

    def __len__(self):
        return len(self.texts)

    def __getitem__(self, idx):
        text = self.texts[idx]
        label = self.labels[idx]

        encoding = self.tokenizer(
            text,
            truncation=True,
            padding='max_length',
            max_length=self.max_len,
            return_tensors='pt'
        )

        return {
            'input_ids': encoding['input_ids'].flatten(),
            'attention_mask': encoding['attention_mask'].flatten(),
            'labels': torch.tensor(label, dtype=torch.long)
        }

# Load data (assume CSV from Phase 1)
data = pd.read_csv('normalized_cpt_data.csv')  # Columns: text, cpt_code (integer label)

# Map CPT codes to integers if needed
# Assume cpt_code is already integer

# Split data
train_texts, val_texts, train_labels, val_labels = train_test_split(
    data['text'], data['cpt_code'], test_size=0.2
)

# Model and Tokenizer
model_name = "dmis-lab/biobert-base-cased-v1.1"  # Medical BERT
tokenizer = AutoTokenizer.from_pretrained(model_name)
num_labels = len(data['cpt_code'].unique())  # Number of unique CPT codes
model = AutoModelForSequenceClassification.from_pretrained(model_name, num_labels=num_labels)

# Datasets
train_dataset = CPTDataset(train_texts.tolist(), train_labels.tolist(), tokenizer)
val_dataset = CPTDataset(val_texts.tolist(), val_labels.tolist(), tokenizer)

# Training arguments
training_args = TrainingArguments(
    output_dir='./results/cpt_classifier',
    num_train_epochs=3,
    per_device_train_batch_size=8,
    per_device_eval_batch_size=8,
    warmup_steps=500,
    weight_decay=0.01,
    logging_dir='./logs',
    logging_steps=10,
    eval_strategy="epoch",
    save_strategy="epoch",
    load_best_model_at_end=True,
)

# Trainer
trainer = Trainer(
    model=model,
    args=training_args,
    train_dataset=train_dataset,
    eval_dataset=val_dataset,
)

# Train
trainer.train()

# Save model
trainer.save_model('./models/cpt_classifier')
tokenizer.save_pretrained('./models/cpt_classifier')
