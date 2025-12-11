<?php

namespace App\Services;

use App\Models\ComplianceRule;
use App\Services\AuditLoggingService;
use Illuminate\Support\Facades\Log;

class ComplianceMonitoringService
{
    /**
     * Evaluate compliance rules for a given event and model
     */
    public function evaluateRules(string $eventType, string $modelType, array $data, $model = null): array
    {
        $violations = [];
        $compliantActions = [];

        // Get active rules for this event and model type, ordered by priority
        $rules = ComplianceRule::active()
            ->byEventType($eventType)
            ->byModelType($modelType)
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            try {
                $isCompliant = $rule->matchesConditions($data);

                if (!$isCompliant) {
                    // Rule violation detected
                    $violations[] = [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                        'description' => $rule->description,
                        'conditions' => $rule->conditions,
                        'data' => $data,
                        'model_id' => $model ? $model->id : null,
                        'model_type' => $modelType,
                        'event_type' => $eventType,
                    ];

                    // Execute violation actions
                    $this->executeActions($rule->getActions(), $data, $model, $rule, false);
                } else {
                    // Rule passed - execute compliant actions if any
                    $compliantActions[] = [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                    ];

                    $this->executeActions($rule->getActions(), $data, $model, $rule, true);
                }
            } catch (\Exception $e) {
                Log::error('Compliance rule evaluation error', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'error' => $e->getMessage(),
                    'event_type' => $eventType,
                    'model_type' => $modelType,
                ]);

                // Log the error as a compliance audit event
                AuditLoggingService::logComplianceAudit('rule_evaluation_error', null, [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'error' => $e->getMessage(),
                    'event_type' => $eventType,
                    'model_type' => $modelType,
                ]);
            }
        }

        return [
            'violations' => $violations,
            'compliant_actions' => $compliantActions,
            'total_rules_evaluated' => $rules->count(),
        ];
    }

    /**
     * Execute actions for a compliance rule
     */
    protected function executeActions(array $actions, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        foreach ($actions as $action) {
            try {
                $this->executeAction($action, $data, $model, $rule, $isCompliant);
            } catch (\Exception $e) {
                Log::error('Compliance action execution error', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'action' => $action,
                    'error' => $e->getMessage(),
                ]);

                AuditLoggingService::logComplianceAudit('action_execution_error', null, [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'action' => $action,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Execute a single action
     */
    protected function executeAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        $actionType = $action['type'] ?? null;

        switch ($actionType) {
            case 'log_audit':
                $this->executeLogAuditAction($action, $data, $model, $rule, $isCompliant);
                break;

            case 'send_notification':
                $this->executeSendNotificationAction($action, $data, $model, $rule, $isCompliant);
                break;

            case 'update_model':
                $this->executeUpdateModelAction($action, $data, $model, $rule, $isCompliant);
                break;

            case 'trigger_workflow':
                $this->executeTriggerWorkflowAction($action, $data, $model, $rule, $isCompliant);
                break;

            case 'block_operation':
                $this->executeBlockOperationAction($action, $data, $model, $rule, $isCompliant);
                break;

            default:
                Log::warning('Unknown compliance action type', [
                    'action_type' => $actionType,
                    'rule_id' => $rule->id,
                ]);
        }
    }

    /**
     * Execute log audit action
     */
    protected function executeLogAuditAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        $eventType = $action['event_type'] ?? ($isCompliant ? 'compliance_rule_passed' : 'compliance_rule_violation');

        AuditLoggingService::logComplianceAudit($eventType, null, [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'rule_type' => $rule->rule_type,
            'is_compliant' => $isCompliant,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'event_data' => $data,
            'action_config' => $action,
        ]);
    }

    /**
     * Execute send notification action
     */
    protected function executeSendNotificationAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        try {
            // Use the advanced alert service for comprehensive alert handling
            $alertService = app(\App\Services\AdvancedAlertService::class);

            $eventType = $isCompliant ? 'compliance_rule_passed' : 'compliance_rule_violation';
            $modelType = $model ? get_class($model) : null;

            $result = $alertService->processComplianceEvent($eventType, $modelType, $data, $model);

            Log::info('Compliance alert processed', [
                'rule_id' => $rule->id,
                'event_type' => $eventType,
                'alerts_created' => count($result['alerts_created']),
                'errors' => count($result['errors']),
                'is_compliant' => $isCompliant,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process compliance alert', [
                'rule_id' => $rule->id,
                'action' => $action,
                'error' => $e->getMessage(),
                'is_compliant' => $isCompliant,
            ]);
        }
    }

    /**
     * Execute update model action
     */
    protected function executeUpdateModelAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        if (!$model || !isset($action['updates'])) {
            return;
        }

        $updates = [];
        foreach ($action['updates'] as $field => $value) {
            $updates[$field] = $value;
        }

        $model->update($updates);

        AuditLoggingService::logComplianceAudit('model_updated_by_rule', null, [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'updates' => $updates,
            'is_compliant' => $isCompliant,
        ]);
    }

    /**
     * Execute trigger workflow action
     */
    protected function executeTriggerWorkflowAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        $workflowType = $action['workflow_type'] ?? 'document_review';

        try {
            // Import the DocumentWorkflowEngine
            $workflowEngine = app(\App\Services\DocumentWorkflowEngine::class);

            if ($model instanceof \App\Models\Document) {
                // If the model is already a Document, trigger workflow directly
                $this->triggerDocumentWorkflow($workflowEngine, $model, $action, $data, $rule, $isCompliant);
            } elseif ($this->shouldCreateDocumentFromModel($model, $action)) {
                // Create a document from the model and trigger workflow
                $document = $this->createDocumentFromModel($model, $action, $data);
                $this->triggerDocumentWorkflow($workflowEngine, $document, $action, $data, $rule, $isCompliant);
            } else {
                Log::info('Compliance workflow action: no document workflow triggered', [
                    'rule_id' => $rule->id,
                    'model_type' => get_class($model),
                    'workflow_type' => $workflowType,
                    'is_compliant' => $isCompliant,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Compliance workflow action failed', [
                'rule_id' => $rule->id,
                'workflow_type' => $workflowType,
                'error' => $e->getMessage(),
                'is_compliant' => $isCompliant,
            ]);
        }
    }

    /**
     * Trigger workflow for a document
     */
    protected function triggerDocumentWorkflow($workflowEngine, $document, array $action, array $data, ComplianceRule $rule, bool $isCompliant): void
    {
        $workflowType = $action['workflow_type'] ?? 'document_review';

        switch ($workflowType) {
            case 'auto_submit':
                if ($isCompliant && $document->workflow_state === 'created') {
                    $workflowEngine->submitForReview($document, $data['user'] ?? null, [
                        'auto_submitted' => true,
                        'compliance_rule_id' => $rule->id,
                        'trigger_reason' => 'compliance_rule_auto_submit',
                    ]);
                }
                break;

            case 'escalate':
                if (!$isCompliant && in_array($document->workflow_state, ['under_review', 'submitted'])) {
                    $workflowEngine->escalateDocument(
                        $document,
                        $data['user'] ?? null,
                        "Escalated due to compliance violation: {$rule->name}",
                        [
                            'compliance_rule_id' => $rule->id,
                            'violation_details' => $data,
                        ]
                    );
                }
                break;

            case 'block_submission':
                if (!$isCompliant && $document->workflow_state === 'draft') {
                    // Mark document as blocked
                    $document->metadata = array_merge($document->metadata ?? [], [
                        'blocked_by_compliance' => true,
                        'blocking_rule_id' => $rule->id,
                        'blocking_reason' => $rule->description,
                    ]);
                    $document->save();

                    Log::info('Document submission blocked by compliance rule', [
                        'document_id' => $document->id,
                        'rule_id' => $rule->id,
                    ]);
                }
                break;

            default:
                Log::info('Unknown workflow type in compliance action', [
                    'workflow_type' => $workflowType,
                    'rule_id' => $rule->id,
                ]);
        }
    }

    /**
     * Check if we should create a document from the model
     */
    protected function shouldCreateDocumentFromModel($model, array $action): bool
    {
        $createDocument = $action['create_document'] ?? false;

        if (!$createDocument) {
            return false;
        }

        // Define which models can be converted to documents
        $convertibleModels = [
            \App\Models\Claim::class,
            \App\Models\Prescription::class,
            \App\Models\Diagnosis::class,
        ];

        return in_array(get_class($model), $convertibleModels);
    }

    /**
     * Create a document from a model
     */
    protected function createDocumentFromModel($model, array $action, array $data): \App\Models\Document
    {
        $templateService = app(\App\Services\DocumentTemplateService::class);

        // Determine document type and template
        $documentType = $this->getDocumentTypeFromModel($model);
        $template = $templateService->getDefaultTemplate($documentType);

        if (!$template) {
            throw new \Exception("No default template found for document type: {$documentType}");
        }

        // Prepare document data
        $documentData = $this->extractDocumentDataFromModel($model, $action);

        // Create document
        return $templateService->renderDocument($template, $documentData, $data['user'] ?? null);
    }

    /**
     * Get document type from model
     */
    protected function getDocumentTypeFromModel($model): string
    {
        return match (get_class($model)) {
            \App\Models\Claim::class => 'claim',
            \App\Models\Prescription::class => 'prescription',
            \App\Models\Diagnosis::class => 'diagnosis',
            default => 'custom',
        };
    }

    /**
     * Extract document data from model
     */
    protected function extractDocumentDataFromModel($model, array $action): array
    {
        $baseData = [
            'title' => $this->getModelTitle($model),
            'description' => $this->getModelDescription($model),
            'model_id' => $model->id,
            'model_type' => get_class($model),
            'compliance_triggered' => true,
        ];

        // Add model-specific data
        return array_merge($baseData, $this->getModelSpecificData($model));
    }

    /**
     * Get title for model
     */
    protected function getModelTitle($model): string
    {
        return match (get_class($model)) {
            \App\Models\Claim::class => "Claim #{$model->id}",
            \App\Models\Prescription::class => 'Prescription for ' . ($model->patient_name ?? 'Patient'),
            \App\Models\Diagnosis::class => "Diagnosis Report",
            default => "Document from " . class_basename($model),
        };
    }

    /**
     * Get description for model
     */
    protected function getModelDescription($model): string
    {
        return match (get_class($model)) {
            \App\Models\Claim::class => "Insurance claim requiring review and approval",
            \App\Models\Prescription::class => "Prescription requiring clinical review",
            \App\Models\Diagnosis::class => "Diagnosis requiring compliance verification",
            default => "Document created from " . class_basename($model),
        };
    }

    /**
     * Get model-specific data
     */
    protected function getModelSpecificData($model): array
    {
        // Extract relevant data from the model for template rendering
        return match (get_class($model)) {
            \App\Models\Claim::class => [
                'claim_number' => ($model->claim_number ?? $model->id),
                'patient_name' => ($model->patient_name ?? 'Unknown'),
                'provider_name' => ($model->provider_name ?? 'Unknown'),
                'service_date' => $model->service_date?->format('Y-m-d'),
                'total_amount' => $model->total_amount,
                'diagnosis_codes' => $model->diagnosis_codes,
                'procedure_codes' => $model->procedure_codes,
            ],
            \App\Models\Prescription::class => [
                'patient_name' => ($model->patient_name ?? 'Unknown'),
                'medication' => ($model->medication ?? 'Unknown'),
                'dosage' => $model->dosage,
                'frequency' => $model->frequency,
                'prescribing_doctor' => ($model->doctor_name ?? 'Unknown'),
                'prescription_date' => $model->created_at?->format('Y-m-d'),
            ],
            \App\Models\Diagnosis::class => [
                'patient_name' => ($model->patient_name ?? 'Unknown'),
                'diagnosis' => ($model->diagnosis ?? 'Unknown'),
                'confidence_score' => $model->confidence_score,
                'doctor_name' => ($model->doctor_name ?? 'Unknown'),
                'diagnosis_date' => $model->created_at?->format('Y-m-d'),
            ],
            default => [],
        };
    }

    /**
     * Execute block operation action
     */
    protected function executeBlockOperationAction(array $action, array $data, $model, ComplianceRule $rule, bool $isCompliant): void
    {
        // This would throw an exception to block the operation
        if (!$isCompliant) {
            $message = $action['message'] ?? 'Operation blocked due to compliance violation';
            throw new \Exception($message);
        }
    }

    /**
     * Monitor document creation
     */
    public function monitorDocumentCreation($document): array
    {
        return $this->evaluateRules(
            'document_created',
            get_class($document),
            $document->toArray(),
            $document
        );
    }

    /**
     * Monitor document updates
     */
    public function monitorDocumentUpdate($document): array
    {
        return $this->evaluateRules(
            'document_updated',
            get_class($document),
            $document->toArray(),
            $document
        );
    }

    /**
     * Monitor document submissions
     */
    public function monitorDocumentSubmission($document): array
    {
        return $this->evaluateRules(
            'document_submitted',
            get_class($document),
            $document->toArray(),
            $document
        );
    }
}
