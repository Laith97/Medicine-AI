<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Symptom;

class CommonSymptomsSeeder extends Seeder
{
    public function run()
    {
        // Clear existing symptoms
        DB::table('symptoms')->truncate();
        
        // Common medical symptoms
        $symptoms = [
            // General symptoms
            'Fever', 'Fatigue', 'Weakness', 'Weight loss', 'Weight gain', 'Night sweats',
            'Chills', 'Malaise', 'Lethargy', 'Dizziness', 'Fainting', 'Syncope',
            
            // Head and neurological symptoms
            'Headache', 'Migraine', 'Dizziness', 'Vertigo', 'Memory loss', 'Confusion',
            'Seizure', 'Tremor', 'Numbness', 'Tingling', 'Paralysis', 'Speech difficulty',
            'Vision changes', 'Double vision', 'Blurred vision', 'Eye pain', 'Eye redness',
            'Hearing loss', 'Tinnitus', 'Ear pain', 'Ear discharge',
            
            // Respiratory symptoms
            'Cough', 'Shortness of breath', 'Wheezing', 'Chest pain', 'Chest tightness',
            'Sputum production', 'Hemoptysis', 'Sneezing', 'Nasal congestion', 'Runny nose',
            'Sore throat', 'Hoarseness', 'Stridor', 'Apnea',
            
            // Cardiovascular symptoms
            'Chest pain', 'Palpitations', 'Irregular heartbeat', 'Tachycardia', 'Bradycardia',
            'Edema', 'Cyanosis', 'Claudication', 'Hypertension', 'Hypotension',
            
            // Gastrointestinal symptoms
            'Nausea', 'Vomiting', 'Diarrhea', 'Constipation', 'Abdominal pain',
            'Bloating', 'Flatulence', 'Heartburn', 'Dysphagia', 'Odynophagia',
            'Hematemesis', 'Melena', 'Hematochezia', 'Jaundice', 'Anorexia',
            
            // Genitourinary symptoms
            'Dysuria', 'Frequency', 'Urgency', 'Hematuria', 'Incontinence',
            'Retention', 'Flank pain', 'Vaginal discharge', 'Vaginal bleeding',
            'Erectile dysfunction', 'Testicular pain', 'Menstrual irregularities',
            
            // Musculoskeletal symptoms
            'Joint pain', 'Muscle pain', 'Back pain', 'Neck pain', 'Stiffness',
            'Swelling', 'Redness', 'Warmth', 'Limited range of motion', 'Weakness',
            'Gait abnormality', 'Fracture', 'Sprain', 'Strain',
            
            // Skin symptoms
            'Rash', 'Itching', 'Hives', 'Eczema', 'Psoriasis', 'Acne',
            'Dry skin', 'Excessive sweating', 'Hair loss', 'Nail changes',
            'Bruising', 'Petechiae', 'Ulcers', 'Nodules', 'Discoloration',
            
            // Psychiatric symptoms
            'Anxiety', 'Depression', 'Insomnia', 'Hallucinations', 'Delusions',
            'Paranoia', 'Mood swings', 'Irritability', 'Suicidal thoughts',
            'Homicidal thoughts', 'Mania', 'Psychosis',
            
            // Endocrine symptoms
            'Polydipsia', 'Polyuria', 'Polyphagia', 'Heat intolerance',
            'Cold intolerance', 'Goiter', 'Hirsutism', 'Gynecomastia',
            
            // Hematologic symptoms
            'Easy bruising', 'Bleeding tendency', 'Lymphadenopathy', 'Splenomegaly',
            'Pallor', 'Petechiae', 'Ecchymosis',
            
            // Allergic symptoms
            'Sneezing', 'Itchy eyes', 'Runny nose', 'Nasal congestion',
            'Wheezing', 'Shortness of breath', 'Hives', 'Angioedema',
            'Anaphylaxis',
            
            // Infectious disease symptoms
            'Fever', 'Chills', 'Sweats', 'Lymphadenopathy', 'Rash',
            'Cough', 'Sore throat', 'Myalgia', 'Arthralgia'
        ];
        
        // Remove duplicates
        $symptoms = array_unique($symptoms);
        
        // Insert symptoms
        $insertData = [];
        foreach ($symptoms as $symptom) {
            $insertData[] = [
                'name' => $symptom,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        DB::table('symptoms')->insert($insertData);
        
        $this->command->info('Added ' . count($insertData) . ' symptoms to the database.');
    }
}