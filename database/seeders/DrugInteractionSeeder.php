<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DrugInteraction;
use App\Models\DrugContraindication;

class DrugInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Drug Interactions
        $interactions = [
            [
                'drug_1' => 'warfarin',
                'drug_2' => 'aspirin',
                'description' => 'Increased risk of bleeding due to additive anticoagulant effects',
                'severity' => 'moderate',
                'clinical_consequence' => 'Gastrointestinal bleeding, intracranial hemorrhage',
                'recommendation' => 'Monitor INR closely, consider alternative pain management',
                'evidence_sources' => ['FDA Drug Interactions', 'Lexicomp']
            ],
            [
                'drug_1' => 'warfarin',
                'drug_2' => 'ibuprofen',
                'description' => 'Increased risk of bleeding due to additive anticoagulant effects',
                'severity' => 'moderate',
                'clinical_consequence' => 'Upper gastrointestinal bleeding',
                'recommendation' => 'Use lowest effective dose, monitor for bleeding signs',
                'evidence_sources' => ['FDA Drug Interactions', 'AHFS Drug Information']
            ],
            [
                'drug_1' => 'warfarin',
                'drug_2' => 'amiodarone',
                'description' => 'Amiodarone increases warfarin effect leading to excessive anticoagulation',
                'severity' => 'severe',
                'clinical_consequence' => 'Major bleeding events',
                'recommendation' => 'Reduce warfarin dose by 30-50%, monitor INR frequently',
                'evidence_sources' => ['FDA Drug Interactions', 'Drug Interaction Facts']
            ],
            [
                'drug_1' => 'simvastatin',
                'drug_2' => 'grapefruit',
                'description' => 'Grapefruit juice inhibits statin metabolism',
                'severity' => 'moderate',
                'clinical_consequence' => 'Increased statin levels, rhabdomyolysis risk',
                'recommendation' => 'Avoid grapefruit products or separate administration by 12 hours',
                'evidence_sources' => ['FDA Drug Interactions', 'Pharmacotherapy Principles']
            ],
            [
                'drug_1' => 'ciprofloxacin',
                'drug_2' => 'tizanidine',
                'description' => 'Ciprofloxacin inhibits tizanidine metabolism',
                'severity' => 'severe',
                'clinical_consequence' => 'Severe hypotension, bradycardia',
                'recommendation' => 'Contraindicated combination - avoid concurrent use',
                'evidence_sources' => ['FDA Drug Interactions', 'Drug Interaction Facts']
            ],
        ];

        foreach ($interactions as $interaction) {
            DrugInteraction::create($interaction);
        }

        // Drug Contraindications
        $contraindications = [
            [
                'drug_name' => 'isotretinoin',
                'condition' => 'pregnancy',
                'reason' => 'Teratogenic effects - causes severe birth defects',
                'severity' => 'severe',
                'alternative_options' => 'Consider alternative acne treatments like topical retinoids',
                'monitoring_required' => 'Pregnancy testing required before, during, and after treatment',
                'evidence_sources' => ['FDA Black Box Warning', 'Teratology Information Services']
            ],
            [
                'drug_name' => 'methotrexate',
                'condition' => 'pregnancy',
                'reason' => 'Teratogenic and abortifacient effects',
                'severity' => 'severe',
                'alternative_options' => 'Consider folate antagonists with different mechanisms',
                'monitoring_required' => 'Effective contraception required during treatment',
                'evidence_sources' => ['FDA Pregnancy Categories', 'Rheumatology Guidelines']
            ],
            [
                'drug_name' => 'warfarin',
                'condition' => 'pregnancy',
                'reason' => 'Crosses placenta, can cause fetal abnormalities and bleeding',
                'severity' => 'severe',
                'alternative_options' => 'Consider heparin derivatives during pregnancy',
                'monitoring_required' => 'Switch to heparin before planned pregnancy',
                'evidence_sources' => ['ACOG Guidelines', 'FDA Pregnancy Categories']
            ],
            [
                'drug_name' => 'nsaids',
                'condition' => 'chronic kidney disease',
                'reason' => 'Reduces renal blood flow and glomerular filtration rate',
                'severity' => 'moderate',
                'alternative_options' => 'Acetaminophen, topical NSAIDs, or disease-modifying agents',
                'monitoring_required' => 'Monitor renal function, creatinine levels',
                'evidence_sources' => ['KDIGO Guidelines', 'Nephrology literature']
            ],
            [
                'drug_name' => 'metformin',
                'condition' => 'renal failure',
                'reason' => 'Risk of lactic acidosis in renal impairment',
                'severity' => 'severe',
                'alternative_options' => 'Sulfonylureas, DPP-4 inhibitors, or insulin',
                'monitoring_required' => 'Contraindicated if CrCl < 30 mL/min',
                'evidence_sources' => ['FDA Labeling', 'ADA Guidelines']
            ],
            [
                'drug_name' => 'acetaminophen',
                'condition' => 'liver disease',
                'reason' => 'Hepatotoxicity risk increased in liver impairment',
                'severity' => 'moderate',
                'alternative_options' => 'NSAIDs (with caution), opioids for severe pain',
                'monitoring_required' => 'Limit to < 2g/day, monitor liver enzymes',
                'evidence_sources' => ['FDA Drug Interactions', 'Hepatology Guidelines']
            ],
            [
                'drug_name' => 'pioglitazone',
                'condition' => 'heart failure',
                'reason' => 'Fluid retention and exacerbation of heart failure',
                'severity' => 'moderate',
                'alternative_options' => 'DPP-4 inhibitors, GLP-1 agonists, or SGLT2 inhibitors',
                'monitoring_required' => 'Monitor for signs of heart failure exacerbation',
                'evidence_sources' => ['FDA Black Box Warning', 'ACC/AHA Guidelines']
            ],
        ];

        foreach ($contraindications as $contraindication) {
            DrugContraindication::create($contraindication);
        }
    }
}
