<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ClearinghouseSubmission;

class Claim extends Model
{
    use HasFactory;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($claim) {
            if ($claim->isDirty()) {
                // Increment version for optimistic locking
                $claim->version = $claim->version + 1;
            }
        });
    }

    protected $fillable = [
        'claim_id',
        'doctor_id',
        'patient_id',
        'diagnosis_text',
        'procedure_text',
        'icd10_codes',
        'cpt_codes',
        'payer',
        'claim_status',
        'denial_reason',
        'raw_denial_code',
        'normalized_denial_category',
        'expected_amount',
        'paid_amount',
        'payment_difference',
        'era_eob_data',
        'service_date',
        'submission_date',
        'payment_date',
        'eligibility_warning',
        'version',
    ];

    protected $casts = [
        'icd10_codes' => 'array',
        'cpt_codes' => 'array',
        'era_eob_data' => 'array',
        'expected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_difference' => 'decimal:2',
        'service_date' => 'date',
        'submission_date' => 'date',
        'payment_date' => 'date',
    ];

    /**
     * Get the doctor that owns the claim.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the patient that owns the claim.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the clearinghouse submission for this claim.
     */
    public function clearinghouseSubmission(): BelongsTo
    {
        return $this->belongsTo(ClearinghouseSubmission::class, 'clearinghouse_submission_id');
    }

    /**
     * Scope for claims by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('claim_status', $status);
    }

    /**
     * Scope for claims by payer
     */
    public function scopeByPayer($query, $payer)
    {
        return $query->where('payer', $payer);
    }

    /**
     * Scope for denied claims
     */
    public function scopeDenied($query)
    {
        return $query->where('claim_status', 'denied');
    }

    /**
     * Scope for paid claims
     */
    public function scopePaid($query)
    {
        return $query->whereIn('claim_status', ['paid', 'partially_paid']);
    }

    /**
     * Calculate payment difference
     */
    public function calculatePaymentDifference(): float
    {
        return $this->expected_amount - $this->paid_amount;
    }

    /**
     * Get normalized denial category from raw denial code
     */
    public static function normalizeDenialCode(string $rawCode): string
    {
        $code = strtoupper(trim($rawCode));

        // Common denial code mappings
        $mappings = [
            // Documentation missing
            '16' => 'documentation_missing',
            '18' => 'documentation_missing',
            '19' => 'documentation_missing',
            '31' => 'documentation_missing',
            '96' => 'documentation_missing',

            // Coding error
            '4' => 'coding_error',
            '5' => 'coding_error',
            '6' => 'coding_error',
            '7' => 'coding_error',
            '8' => 'coding_error',
            '12' => 'coding_error',
            '15' => 'coding_error',

            // Coverage issue
            '1' => 'coverage_issue',
            '2' => 'coverage_issue',
            '3' => 'coverage_issue',
            '109' => 'coverage_issue',
            '110' => 'coverage_issue',

            // Medical necessity
            '50' => 'medical_necessity',
            '97' => 'medical_necessity',
            '98' => 'medical_necessity',

            // Timely filing
            '54' => 'timely_filing',
            '55' => 'timely_filing',
            '56' => 'timely_filing',

            // Duplicate claim
            '18' => 'duplicate_claim', // Can overlap with documentation
            '19' => 'duplicate_claim',
        ];

        return $mappings[$code] ?? 'other';
    }
}
