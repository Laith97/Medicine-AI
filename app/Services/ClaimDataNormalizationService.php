<?php

namespace App\Services;

use App\Models\Claim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClaimDataNormalizationService
{
    /**
     * Normalize denial codes for a collection of claims
     */
    public function normalizeDenialCodes(Collection $claims): Collection
    {
        return $claims->map(function ($claim) {
            if ($claim->raw_denial_code && !$claim->normalized_denial_category) {
                $claim->normalized_denial_category = Claim::normalizeDenialCode($claim->raw_denial_code);
                $claim->save();
            }
            return $claim;
        });
    }

    /**
     * Parse ERA/EOB data and calculate payment differences
     */
    public function parseEraEobData(Collection $claims): Collection
    {
        return $claims->map(function ($claim) {
            if ($claim->era_eob_data) {
                $parsedData = $this->parseEraEobJson($claim->era_eob_data);

                if ($parsedData) {
                    $claim->expected_amount = $parsedData['expected_amount'] ?? $claim->expected_amount;
                    $claim->paid_amount = $parsedData['paid_amount'] ?? $claim->paid_amount;
                    $claim->payment_difference = $claim->calculatePaymentDifference();

                    // Update denial information if present in ERA/EOB
                    if (isset($parsedData['denial_code']) && !$claim->raw_denial_code) {
                        $claim->raw_denial_code = $parsedData['denial_code'];
                        $claim->normalized_denial_category = Claim::normalizeDenialCode($parsedData['denial_code']);
                    }

                    if (isset($parsedData['denial_reason']) && !$claim->denial_reason) {
                        $claim->denial_reason = $parsedData['denial_reason'];
                    }

                    $claim->save();
                }
            }
            return $claim;
        });
    }

    /**
     * Parse ERA/EOB JSON data
     */
    private function parseEraEobJson(array $eraEobData): ?array
    {
        try {
            // This is a simplified parser - in real implementation,
            // you'd parse actual ERA/EOB format (ANSI X12 835)

            $parsed = [
                'expected_amount' => 0,
                'paid_amount' => 0,
                'denial_code' => null,
                'denial_reason' => null,
            ];

            // Look for common ERA/EOB fields
            if (isset($eraEobData['total_charge'])) {
                $parsed['expected_amount'] = (float) $eraEobData['total_charge'];
            }

            if (isset($eraEobData['payment_amount'])) {
                $parsed['paid_amount'] = (float) $eraEobData['payment_amount'];
            }

            if (isset($eraEobData['adjustment_reason_code'])) {
                $parsed['denial_code'] = $eraEobData['adjustment_reason_code'];
            }

            if (isset($eraEobData['adjustment_reason_description'])) {
                $parsed['denial_reason'] = $eraEobData['adjustment_reason_description'];
            }

            // Handle service line level data
            if (isset($eraEobData['service_lines']) && is_array($eraEobData['service_lines'])) {
                foreach ($eraEobData['service_lines'] as $line) {
                    if (isset($line['charge_amount'])) {
                        $parsed['expected_amount'] += (float) $line['charge_amount'];
                    }
                    if (isset($line['payment_amount'])) {
                        $parsed['paid_amount'] += (float) $line['payment_amount'];
                    }
                }
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error('Error parsing ERA/EOB data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract and normalize ICD-10 codes from text
     */
    public function extractIcd10Codes(string $text): array
    {
        $pattern = '/[A-TV-Z][0-9][0-9AB]\.?[0-9A-TV-Z]{0,4}/i';
        preg_match_all($pattern, $text, $matches);

        return array_unique(array_map('strtoupper', $matches[0] ?? []));
    }

    /**
     * Extract and normalize CPT codes from text
     */
    public function extractCptCodes(string $text): array
    {
        $pattern = '/\b\d{4,5}[A-Z]?\b/';
        preg_match_all($pattern, $text, $matches);

        return array_unique($matches[0] ?? []);
    }

    /**
     * Normalize diagnosis and procedure text
     */
    public function normalizeMedicalText(Collection $claims): Collection
    {
        return $claims->map(function ($claim) {
            // Extract ICD-10 codes from diagnosis text
            if ($claim->diagnosis_text && empty($claim->icd10_codes)) {
                $claim->icd10_codes = $this->extractIcd10Codes($claim->diagnosis_text);
            }

            // Extract CPT codes from procedure text
            if ($claim->procedure_text && empty($claim->cpt_codes)) {
                $claim->cpt_codes = $this->extractCptCodes($claim->procedure_text);
            }

            $claim->save();
            return $claim;
        });
    }

    /**
     * Generate normalized data export
     */
    public function generateNormalizedData(Collection $claims): array
    {
        return $claims->map(function ($claim) {
            return [
                'claim_id' => $claim->claim_id,
                'patient_id' => $claim->patient_id,
                'diagnosis_text' => $claim->diagnosis_text,
                'procedure_text' => $claim->procedure_text,
                'icd10_codes' => $claim->icd10_codes ?? [],
                'cpt_codes' => $claim->cpt_codes ?? [],
                'payer' => $claim->payer,
                'claim_status' => $claim->claim_status,
                'denial_reason' => $claim->denial_reason,
                'normalized_denial_category' => $claim->normalized_denial_category,
                'expected_amount' => $claim->expected_amount,
                'paid_amount' => $claim->paid_amount,
                'payment_difference' => $claim->payment_difference,
                'service_date' => $claim->service_date?->format('Y-m-d'),
                'submission_date' => $claim->submission_date?->format('Y-m-d'),
                'payment_date' => $claim->payment_date?->format('Y-m-d'),
            ];
        })->toArray();
    }
}
