<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\AppealWorkflow;
use App\Models\WorkflowTask;
use App\Services\ClaimDenialPredictionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AppealWorkflowService
{
    protected ClaimDenialPredictionService $denialPredictionService;

    public function __construct(ClaimDenialPredictionService $denialPredictionService)
    {
        $this->denialPredictionService = $denialPredictionService;
    }

    /**
     * Create appeal workflow for denied claim
     */
    public function createAppealWorkflow(Claim $claim): ?AppealWorkflow
    {
        if ($claim->claim_status !== 'denied') {
            return null;
        }

        // Determine denial category
        $denialCategory = $this->categorizeDenial($claim);

        // Check if appeal is recommended
        $appealRecommendation = $this->getAppealRecommendation($claim, $denialCategory);

        $workflow = AppealWorkflow::create([
            'claim_id' => $claim->id,
            'denial_category' => $denialCategory,
            'workflow_steps' => $this->getWorkflowSteps($denialCategory),
            'deadline' => $this->calculateAppealDeadline($claim, $denialCategory),
            'auto_appeal_eligible' => $appealRecommendation['eligible'],
            'appeal_probability' => $appealRecommendation['probability'],
            'appeal_reason' => $appealRecommendation['reason'],
            'required_documents' => $this->getRequiredDocuments($denialCategory),
        ]);

        // Create initial workflow tasks
        $this->createInitialWorkflowTasks($workflow);

        Log::info('Created appeal workflow', [
            'claim_id' => $claim->id,
            'workflow_id' => $workflow->id,
            'denial_category' => $denialCategory,
            'auto_eligible' => $appealRecommendation['eligible']
        ]);

        return $workflow;
    }

    /**
     * Categorize denial based on denial code and claim data
     */
    protected function categorizeDenial(Claim $claim): string
    {
        // Use existing normalization if available
        if ($claim->normalized_denial_category) {
            return $claim->normalized_denial_category;
        }

        // Fallback to manual categorization
        $rawCode = $claim->raw_denial_code ?? '';
        return Claim::normalizeDenialCode($rawCode);
    }

    /**
     * Get appeal recommendation based on denial category and claim data
     */
    protected function getAppealRecommendation(Claim $claim, string $denialCategory): array
    {
        // High success rate categories for auto-appeal
        $autoAppealCategories = [
            'documentation_missing',
            'coding_error',
            'timely_filing'
        ];

        $eligible = in_array($denialCategory, $autoAppealCategories);
        $probability = 0.0;
        $reason = '';

        if ($eligible) {
            // Use AI to predict appeal success
            try {
                $prediction = $this->denialPredictionService->predictAppealSuccess($claim, $denialCategory);
                $probability = $prediction['probability'] ?? 0.0;
                $reason = $prediction['reason'] ?? 'AI prediction';

                // Only auto-appeal if probability is high enough
                $eligible = $probability >= 0.7;
            } catch (\Exception $e) {
                Log::warning('Failed to get appeal prediction', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage()
                ]);
                $reason = 'Fallback: category-based recommendation';
            }
        } else {
            $reason = 'Category not eligible for auto-appeal';
        }

        return [
            'eligible' => $eligible,
            'probability' => $probability,
            'reason' => $reason
        ];
    }

    /**
     * Get workflow steps for denial category
     */
    protected function getWorkflowSteps(string $denialCategory): array
    {
        $baseSteps = [
            'initial_review',
            'gather_evidence',
            'prepare_appeal_letter',
            'submit_appeal',
            'follow_up'
        ];

        // Category-specific steps
        $categorySteps = match ($denialCategory) {
            'documentation_missing' => [
                'locate_missing_documents',
                'verify_document_completeness',
            ],
            'coding_error' => [
                'review_coding_accuracy',
                'correct_coding_errors',
            ],
            'medical_necessity' => [
                'obtain_medical_necessity_justification',
                'consult_with_provider',
            ],
            'timely_filing' => [
                'verify_filing_timeline',
                'prepare_timely_filing_appeal',
            ],
            default => []
        };

        // Insert category-specific steps after initial review
        array_splice($baseSteps, 1, 0, $categorySteps);

        return $baseSteps;
    }

    /**
     * Calculate appeal deadline based on denial category and payer
     */
    protected function calculateAppealDeadline(Claim $claim, string $denialCategory): ?\Carbon\Carbon
    {
        $baseDays = match ($denialCategory) {
            'timely_filing' => 180, // 6 months for timely filing appeals
            'medical_necessity' => 120, // 4 months
            default => 60 // 2 months standard
        };

        // Adjust based on payer-specific rules
        $payer = strtolower($claim->payer ?? '');
        if (str_contains($payer, 'medicare')) {
            $baseDays = 120; // Medicare often allows more time
        } elseif (str_contains($payer, 'medicaid')) {
            $baseDays = 90;
        }

        return now()->addDays($baseDays);
    }

    /**
     * Get required documents for appeal based on denial category
     */
    protected function getRequiredDocuments(string $denialCategory): array
    {
        $baseDocuments = [
            'original_claim',
            'denial_letter',
            'appeal_cover_letter'
        ];

        $categoryDocuments = match ($denialCategory) {
            'documentation_missing' => [
                'medical_records',
                'progress_notes',
                'lab_results',
                'imaging_reports'
            ],
            'coding_error' => [
                'coding_justification',
                'procedure_notes',
                'coding_references'
            ],
            'medical_necessity' => [
                'treatment_plan',
                'medical_necessity_letter',
                'clinical_guidelines'
            ],
            'timely_filing' => [
                'submission_logs',
                'postage_receipts',
                'electronic_submission_confirmations'
            ],
            default => []
        };

        return array_merge($baseDocuments, $categoryDocuments);
    }

    /**
     * Create initial workflow tasks
     */
    protected function createInitialWorkflowTasks(AppealWorkflow $workflow): void
    {
        $nextStep = $workflow->getNextStep();

        if ($workflow->auto_appeal_eligible) {
            // Create auto-appeal task
            WorkflowTask::create([
                'task_type' => 'auto_appeal_processing',
                'taskable_type' => AppealWorkflow::class,
                'taskable_id' => $workflow->id,
                'title' => "Process auto-appeal for claim {$workflow->claim->claim_id}",
                'description' => "Automatically process appeal for {$workflow->denial_category} denial",
                'task_data' => [
                    'current_step' => $nextStep,
                    'appeal_probability' => $workflow->appeal_probability,
                ],
                'priority' => 'high',
                'due_date' => now()->addHours(24),
            ]);
        } else {
            // Create manual review task
            WorkflowTask::create([
                'task_type' => 'manual_appeal_review',
                'taskable_type' => AppealWorkflow::class,
                'taskable_id' => $workflow->id,
                'title' => "Review appeal for claim {$workflow->claim->claim_id}",
                'description' => "Review and process appeal for {$workflow->denial_category} denial",
                'task_data' => [
                    'current_step' => $nextStep,
                    'required_documents' => $workflow->required_documents,
                    'deadline' => $workflow->deadline?->format('Y-m-d'),
                ],
                'priority' => 'high',
                'due_date' => now()->addDays(3), // Give staff 3 days to review
            ]);
        }
    }

    /**
     * Process auto-appeal workflows
     */
    public function processAutoAppeals(): void
    {
        $workflows = AppealWorkflow::autoAppealEligible()
            ->inProgress()
            ->where('deadline', '>', now())
            ->get();

        foreach ($workflows as $workflow) {
            try {
                $this->processAutoAppealWorkflow($workflow);
            } catch (\Exception $e) {
                Log::error('Failed to process auto-appeal workflow', [
                    'workflow_id' => $workflow->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process individual auto-appeal workflow
     */
    protected function processAutoAppealWorkflow(AppealWorkflow $workflow): void
    {
        $currentStep = $workflow->current_step;

        switch ($currentStep) {
            case 'initial_review':
                $this->completeInitialReview($workflow);
                break;
            case 'gather_evidence':
                $this->gatherAppealEvidence($workflow);
                break;
            case 'prepare_appeal_letter':
                $this->prepareAppealLetter($workflow);
                break;
            case 'submit_appeal':
                $this->submitAppeal($workflow);
                break;
            case 'follow_up':
                $this->scheduleFollowUp($workflow);
                break;
        }
    }

    /**
     * Complete initial review step
     */
    protected function completeInitialReview(AppealWorkflow $workflow): void
    {
        // Mark initial review as complete
        $workflow->completeStep('initial_review');

        // Move to next step
        $workflow->current_step = $workflow->getNextStep();
        $workflow->save();

        Log::info('Completed initial appeal review', [
            'workflow_id' => $workflow->id,
            'next_step' => $workflow->current_step
        ]);
    }

    /**
     * Gather evidence for appeal
     */
    protected function gatherAppealEvidence(AppealWorkflow $workflow): void
    {
        $claim = $workflow->claim;
        $evidence = [];

        // Gather evidence based on denial category
        switch ($workflow->denial_category) {
            case 'documentation_missing':
                $evidence = $this->gatherDocumentationEvidence($claim);
                break;
            case 'coding_error':
                $evidence = $this->gatherCodingEvidence($claim);
                break;
            case 'medical_necessity':
                $evidence = $this->gatherMedicalNecessityEvidence($claim);
                break;
        }

        if (!empty($evidence)) {
            $workflow->completeStep('gather_evidence');
            $workflow->current_step = $workflow->getNextStep();
            $workflow->save();

            Log::info('Gathered appeal evidence', [
                'workflow_id' => $workflow->id,
                'evidence_count' => count($evidence)
            ]);
        }
    }

    /**
     * Prepare appeal letter
     */
    protected function prepareAppealLetter(AppealWorkflow $workflow): void
    {
        // Generate appeal letter content
        $letterContent = $this->generateAppealLetter($workflow);

        $workflow->appeal_reason = $letterContent;
        $workflow->completeStep('prepare_appeal_letter');
        $workflow->current_step = $workflow->getNextStep();
        $workflow->save();

        Log::info('Prepared appeal letter', [
            'workflow_id' => $workflow->id
        ]);
    }

    /**
     * Submit appeal
     */
    protected function submitAppeal(AppealWorkflow $workflow): void
    {
        // Here you would integrate with clearinghouse for appeal submission
        // For now, mark as submitted
        $workflow->completeStep('submit_appeal');
        $workflow->current_step = $workflow->getNextStep();
        $workflow->status = 'completed';
        $workflow->save();

        // Update claim status
        $workflow->claim->update([
            'claim_status' => 'appealed',
            'appeal_submitted_at' => now()
        ]);

        Log::info('Submitted appeal', [
            'workflow_id' => $workflow->id,
            'claim_id' => $workflow->claim->id
        ]);
    }

    /**
     * Schedule follow-up
     */
    protected function scheduleFollowUp(AppealWorkflow $workflow): void
    {
        // Create follow-up task
        WorkflowTask::create([
            'task_type' => 'appeal_followup',
            'taskable_type' => AppealWorkflow::class,
            'taskable_id' => $workflow->id,
            'title' => "Follow up on appeal for claim {$workflow->claim->claim_id}",
            'description' => "Check status of submitted appeal",
            'priority' => 'medium',
            'due_date' => now()->addDays(30), // Follow up in 30 days
        ]);

        $workflow->completeStep('follow_up');
        $workflow->status = 'completed';
        $workflow->save();
    }

    /**
     * Helper methods for gathering evidence
     */
    protected function gatherDocumentationEvidence(Claim $claim): array
    {
        // This would integrate with document management system
        return ['medical_records_available' => true];
    }

    protected function gatherCodingEvidence(Claim $claim): array
    {
        return ['coding_verified' => true];
    }

    protected function gatherMedicalNecessityEvidence(Claim $claim): array
    {
        return ['medical_necessity_documented' => true];
    }

    protected function generateAppealLetter(AppealWorkflow $workflow): string
    {
        // Generate appeal letter content based on workflow
        return "Appeal letter for {$workflow->denial_category} denial on claim {$workflow->claim->claim_id}";
    }

    /**
     * Check for overdue appeals and create reminder tasks
     */
    public function checkOverdueAppeals(): void
    {
        $overdueWorkflows = AppealWorkflow::overdue()->get();

        foreach ($overdueWorkflows as $workflow) {
            WorkflowTask::create([
                'task_type' => 'overdue_appeal_reminder',
                'taskable_type' => AppealWorkflow::class,
                'taskable_id' => $workflow->id,
                'title' => "URGENT: Overdue appeal for claim {$workflow->claim->claim_id}",
                'description' => "Appeal deadline has passed. Immediate action required.",
                'priority' => 'urgent',
                'due_date' => now()->addHours(4), // Urgent - handle within 4 hours
            ]);

            Log::warning('Overdue appeal detected', [
                'workflow_id' => $workflow->id,
                'claim_id' => $workflow->claim->id,
                'deadline' => $workflow->deadline
            ]);
        }
    }
}
