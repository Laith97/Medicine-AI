<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'template_type',
        'description',
        'template_content',
        'placeholders',
        'compliance_rules',
        'metadata',
        'is_active',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'compliance_rules' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the template.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get documents created from this template.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope for default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get default template for a type
     */
    public static function getDefaultForType(string $type): ?self
    {
        return self::active()->byType($type)->default()->first();
    }

    /**
     * Extract placeholders from template content
     */
    public function extractPlaceholders(): array
    {
        $placeholders = [];

        // Find all {{placeholder}} patterns
        preg_match_all('/\{\{([^}]+)\}\}/', $this->template_content, $matches);

        foreach ($matches[1] as $placeholder) {
            $parts = explode(':', $placeholder);
            $key = trim($parts[0]);
            $type = $parts[1] ?? 'text';
            $default = $parts[2] ?? null;

            $placeholders[$key] = [
                'type' => $type,
                'default' => $default,
                'required' => !str_contains($placeholder, '?'), // Optional placeholders end with ?
            ];
        }

        return $placeholders;
    }

    /**
     * Validate template content has required placeholders
     */
    public function validatePlaceholders(array $data): array
    {
        $placeholders = $this->placeholders ?? $this->extractPlaceholders();
        $errors = [];

        foreach ($placeholders as $key => $config) {
            if ($config['required'] && !isset($data[$key])) {
                $errors[] = "Required placeholder '{$key}' is missing";
            }
        }

        return $errors;
    }

    /**
     * Render template with data
     */
    public function render(array $data = []): string
    {
        $content = $this->template_content;

        // Replace placeholders
        foreach ($data as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
            $content = str_replace("{{$key}}}", $value, $content); // Also support single braces
        }

        // Handle optional placeholders
        $content = preg_replace('/\{\{[^}]+\?\}\}/', '', $content);

        // Handle compliance placeholders
        $content = $this->renderCompliancePlaceholders($content, $data);

        return $content;
    }

    /**
     * Render compliance-specific placeholders
     */
    protected function renderCompliancePlaceholders(string $content, array $data): string
    {
        // HIPAA compliance placeholders
        $hipaaPlaceholders = [
            '{{hipaa_consent_date}}' => date('Y-m-d'),
            '{{hipaa_consent_time}}' => date('H:i:s'),
            '{{hipaa_privacy_officer}}' => $data['privacy_officer'] ?? 'Privacy Officer',
            '{{hipaa_data_retention}}' => $data['data_retention_period'] ?? '7 years',
        ];

        foreach ($hipaaPlaceholders as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        // Audit trail placeholders
        $auditPlaceholders = [
            '{{audit_created_by}}' => $data['created_by_name'] ?? 'System',
            '{{audit_created_at}}' => $data['created_at'] ?? now()->format('Y-m-d H:i:s'),
            '{{audit_last_modified}}' => $data['updated_at'] ?? now()->format('Y-m-d H:i:s'),
            '{{audit_version}}' => $data['version'] ?? '1.0',
        ];

        foreach ($auditPlaceholders as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Get compliance rules for this template
     */
    public function getComplianceRules(): array
    {
        return $this->compliance_rules ?? [];
    }

    /**
     * Check if template meets compliance requirements
     */
    public function validateCompliance(array $data = []): array
    {
        $rules = $this->getComplianceRules();
        $violations = [];

        foreach ($rules as $rule) {
            $ruleType = $rule['type'] ?? null;

            switch ($ruleType) {
                case 'hipaa_required_fields':
                    $violations = array_merge($violations, $this->validateHipaaFields($rule, $data));
                    break;

                case 'audit_trail_required':
                    $violations = array_merge($violations, $this->validateAuditTrail($rule, $data));
                    break;

                case 'data_retention_policy':
                    $violations = array_merge($violations, $this->validateRetentionPolicy($rule, $data));
                    break;
            }
        }

        return $violations;
    }

    /**
     * Validate HIPAA required fields
     */
    protected function validateHipaaFields(array $rule, array $data): array
    {
        $violations = [];
        $requiredFields = $rule['required_fields'] ?? [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $violations[] = "HIPAA required field '{$field}' is missing or empty";
            }
        }

        return $violations;
    }

    /**
     * Validate audit trail requirements
     */
    protected function validateAuditTrail(array $rule, array $data): array
    {
        $violations = [];

        if ($rule['require_created_by'] ?? false) {
            if (!isset($data['created_by']) || empty($data['created_by'])) {
                $violations[] = "Audit trail requires 'created_by' field";
            }
        }

        if ($rule['require_timestamps'] ?? false) {
            if (!isset($data['created_at'])) {
                $violations[] = "Audit trail requires 'created_at' timestamp";
            }
        }

        return $violations;
    }

    /**
     * Validate data retention policy
     */
    protected function validateRetentionPolicy(array $rule, array $data): array
    {
        $violations = [];

        if (isset($rule['retention_period'])) {
            $retentionDate = now()->addYears($rule['retention_period']);
            if (isset($data['retention_until']) && $data['retention_until'] < $retentionDate) {
                $violations[] = "Data retention period does not meet minimum requirements";
            }
        }

        return $violations;
    }
}
