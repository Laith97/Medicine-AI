<?php

namespace App\Services;

use App\Models\ClearinghouseSubmission;
use App\Models\ClearinghouseResponse;
use App\Models\Claim;
use Illuminate\Support\Facades\Log;

class ResponseProcessorService
{
    /**
     * Process 277CA Claim Acknowledgement response
     */
    public function process277CA(ClearinghouseSubmission $submission, array $responseData): ClearinghouseResponse
    {
        $response = ClearinghouseResponse::create([
            'clearinghouse_account_id' => $submission->clearinghouse_account_id,
            'clearinghouse_submission_id' => $submission->id,
            'response_type' => '277CA',
            'transaction_set_id' => $responseData['transaction_set_id'] ?? null,
            'batch_id' => $responseData['batch_id'] ?? $submission->batch_id,
            'status' => 'received',
            'response_content' => $responseData['content'] ?? '',
            'parsed_data' => $this->parse277CA($responseData),
            'claim_count' => $responseData['claim_count'] ?? 0,
            'received_at' => $responseData['received_at'] ?? now(),
            'metadata' => $responseData['metadata'] ?? [],
        ]);

        // Update submission response received timestamp
        $submission->update(['response_received_at' => now()]);

        // Process individual claim acknowledgements
        $this->processClaimAcknowledgements($response);

        $response->markAsProcessed();

        return $response;
    }

    /**
     * Process 835 Remittance Advice response
     */
    public function process835(ClearinghouseSubmission $submission, array $responseData): ClearinghouseResponse
    {
        $parsedData = $this->parse835($responseData);

        $response = ClearinghouseResponse::create([
            'clearinghouse_account_id' => $submission->clearinghouse_account_id,
            'clearinghouse_submission_id' => $submission->id,
            'response_type' => '835',
            'transaction_set_id' => $responseData['transaction_set_id'] ?? null,
            'batch_id' => $responseData['batch_id'] ?? $submission->batch_id,
            'status' => 'received',
            'response_content' => $responseData['content'] ?? '',
            'parsed_data' => $parsedData,
            'claim_count' => $parsedData['claim_count'] ?? 0,
            'total_paid_amount' => $parsedData['total_paid_amount'] ?? 0,
            'total_adjustment_amount' => $parsedData['total_adjustment_amount'] ?? 0,
            'received_at' => $responseData['received_at'] ?? now(),
            'metadata' => $responseData['metadata'] ?? [],
        ]);

        // Update submission response received timestamp
        $submission->update(['response_received_at' => now()]);

        // Process payment information
        $this->processRemittancePayments($response);

        $response->markAsProcessed();

        return $response;
    }

    /**
     * Process 999 Implementation Acknowledgement response
     */
    public function process999(ClearinghouseSubmission $submission, array $responseData): ClearinghouseResponse
    {
        $response = ClearinghouseResponse::create([
            'clearinghouse_account_id' => $submission->clearinghouse_account_id,
            'clearinghouse_submission_id' => $submission->id,
            'response_type' => '999',
            'transaction_set_id' => $responseData['transaction_set_id'] ?? null,
            'batch_id' => $responseData['batch_id'] ?? $submission->batch_id,
            'status' => 'received',
            'response_content' => $responseData['content'] ?? '',
            'parsed_data' => $this->parse999($responseData),
            'received_at' => $responseData['received_at'] ?? now(),
            'metadata' => $responseData['metadata'] ?? [],
        ]);

        // Check for implementation errors
        $this->checkImplementationErrors($response);

        $response->markAsProcessed();

        return $response;
    }

    /**
     * Parse 277CA response data
     */
    protected function parse277CA(array $responseData): array
    {
        $content = $responseData['content'] ?? '';

        // Basic parsing - in real implementation, this would use proper EDI parsing
        $parsed = [
            'acknowledgements' => [],
            'errors' => [],
            'warnings' => [],
        ];

        // Extract claim acknowledgements from EDI content
        $segments = explode('~', $content);
        $currentClaim = null;

        foreach ($segments as $segment) {
            $elements = explode('*', trim($segment));

            switch ($elements[0]) {
                case 'HL':
                    // Hierarchical Level - new claim
                    if (isset($elements[3]) && $elements[3] === 'PT') {
                        $currentClaim = [
                            'claim_id' => $elements[1] ?? null,
                            'status' => 'unknown',
                            'errors' => [],
                        ];
                    }
                    break;

                case 'STC':
                    // Status Information
                    if ($currentClaim) {
                        $statusCode = $elements[1] ?? '';
                        $currentClaim['status'] = $this->mapStatusCode($statusCode);
                        $currentClaim['status_code'] = $statusCode;

                        if (isset($elements[3])) {
                            $currentClaim['errors'][] = [
                                'code' => $elements[3],
                                'message' => $elements[4] ?? 'Unknown error',
                            ];
                        }
                    }
                    break;

                case 'SE':
                    // Transaction Set Trailer
                    if ($currentClaim) {
                        $parsed['acknowledgements'][] = $currentClaim;
                        $currentClaim = null;
                    }
                    break;
            }
        }

        return $parsed;
    }

    /**
     * Parse 835 response data
     */
    protected function parse835(array $responseData): array
    {
        $content = $responseData['content'] ?? '';

        $parsed = [
            'payments' => [],
            'adjustments' => [],
            'total_paid_amount' => 0,
            'total_adjustment_amount' => 0,
            'claim_count' => 0,
        ];

        // Extract payment information from EDI content
        $segments = explode('~', $content);
        $currentClaim = null;

        foreach ($segments as $segment) {
            $elements = explode('*', trim($segment));

            switch ($elements[0]) {
                case 'CLP':
                    // Claim Payment Information
                    $currentClaim = [
                        'patient_control_number' => $elements[1] ?? '',
                        'claim_status_code' => $elements[2] ?? '',
                        'total_charge_amount' => (float)($elements[3] ?? 0),
                        'payment_amount' => (float)($elements[4] ?? 0),
                        'adjustments' => [],
                    ];
                    $parsed['claim_count']++;
                    break;

                case 'CAS':
                    // Claim Adjustment
                    if ($currentClaim) {
                        $adjustment = [
                            'group_code' => $elements[1] ?? '',
                            'reason_code' => $elements[2] ?? '',
                            'amount' => (float)($elements[3] ?? 0),
                            'quantity' => $elements[4] ?? '',
                            'description' => $elements[5] ?? '',
                        ];
                        $currentClaim['adjustments'][] = $adjustment;
                        $parsed['total_adjustment_amount'] += $adjustment['amount'];
                    }
                    break;

                case 'PLB':
                    // Provider Adjustment
                    // Handle provider-level adjustments if needed
                    break;

                default:
                    // Handle other segments as needed
                    break;
            }

            // Save completed claim
            if ($currentClaim && in_array($elements[0], ['CLP', 'SE'])) {
                $parsed['payments'][] = $currentClaim;
                $parsed['total_paid_amount'] += $currentClaim['payment_amount'];
                $currentClaim = null;
            }
        }

        return $parsed;
    }

    /**
     * Parse 999 response data
     */
    protected function parse999(array $responseData): array
    {
        $content = $responseData['content'] ?? '';

        $parsed = [
            'acknowledgements' => [],
            'errors' => [],
        ];

        // Extract implementation acknowledgements from EDI content
        $segments = explode('~', $content);

        foreach ($segments as $segment) {
            $elements = explode('*', trim($segment));

            if ($elements[0] === 'IK3') {
                // Implementation Segment Error
                $parsed['errors'][] = [
                    'segment_id' => $elements[1] ?? '',
                    'segment_position' => $elements[2] ?? '',
                    'loop_id' => $elements[3] ?? '',
                    'error_code' => $elements[4] ?? '',
                    'error_description' => $elements[5] ?? '',
                ];
            } elseif ($elements[0] === 'IK4') {
                // Implementation Data Element Error
                $parsed['errors'][] = [
                    'element_position' => $elements[1] ?? '',
                    'component_position' => $elements[2] ?? '',
                    'error_code' => $elements[3] ?? '',
                    'error_description' => $elements[4] ?? '',
                ];
            }
        }

        return $parsed;
    }

    /**
     * Process individual claim acknowledgements from 277CA
     */
    protected function processClaimAcknowledgements(ClearinghouseResponse $response): void
    {
        $parsedData = $response->parsed_data ?? [];

        foreach ($parsedData['acknowledgements'] ?? [] as $acknowledgement) {
            try {
                $claim = Claim::where('id', $acknowledgement['claim_id'])->first();

                if ($claim) {
                    // Update claim status based on acknowledgement
                    $this->updateClaimFromAcknowledgement($claim, $acknowledgement);

                    // Store acknowledgement details in claim metadata
                    $metadata = $claim->metadata ?? [];
                    $metadata['clearinghouse_acknowledgements'] = array_merge(
                        $metadata['clearinghouse_acknowledgements'] ?? [],
                        [$acknowledgement]
                    );
                    $claim->update(['metadata' => $metadata]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to process claim acknowledgement', [
                    'response_id' => $response->id,
                    'claim_id' => $acknowledgement['claim_id'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process remittance payments from 835
     */
    protected function processRemittancePayments(ClearinghouseResponse $response): void
    {
        $parsedData = $response->parsed_data ?? [];

        foreach ($parsedData['payments'] ?? [] as $payment) {
            try {
                // Find claim by patient control number or clearinghouse claim ID
                $claim = Claim::where('id', $payment['patient_control_number'])
                    ->orWhere('clearinghouse_claim_id', $payment['patient_control_number'])
                    ->first();

                if ($claim) {
                    $this->updateClaimFromPayment($claim, $payment);

                    // Store payment details in claim metadata
                    $metadata = $claim->metadata ?? [];
                    $metadata['clearinghouse_payments'] = array_merge(
                        $metadata['clearinghouse_payments'] ?? [],
                        [$payment]
                    );
                    $claim->update(['metadata' => $metadata]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to process remittance payment', [
                    'response_id' => $response->id,
                    'patient_control_number' => $payment['patient_control_number'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Check for implementation errors in 999 response
     */
    protected function checkImplementationErrors(ClearinghouseResponse $response): void
    {
        $parsedData = $response->parsed_data ?? [];

        if (!empty($parsedData['errors'])) {
            $errorMessage = 'Implementation errors found: ' . json_encode($parsedData['errors']);
            $response->update([
                'processing_errors' => $errorMessage,
                'status' => 'error'
            ]);

            // Mark submission as having errors
            $response->submission->update([
                'error_message' => $errorMessage
            ]);
        }
    }

    /**
     * Update claim status from acknowledgement
     */
    protected function updateClaimFromAcknowledgement(Claim $claim, array $acknowledgement): void
    {
        $status = $acknowledgement['status'] ?? 'unknown';

        // Map acknowledgement status to claim status
        $statusMapping = [
            'accepted' => 'approved',
            'rejected' => 'denied',
            'partial_accept' => 'approved', // Handle partial acceptance
        ];

        if (isset($statusMapping[$status])) {
            $claim->update(['claim_status' => $statusMapping[$status]]);
        }

        // Store any errors
        if (!empty($acknowledgement['errors'])) {
            $metadata = $claim->metadata ?? [];
            $metadata['clearinghouse_errors'] = array_merge(
                $metadata['clearinghouse_errors'] ?? [],
                $acknowledgement['errors']
            );
            $claim->update(['metadata' => $metadata]);
        }
    }

    /**
     * Update claim payment information from 835
     */
    protected function updateClaimFromPayment(Claim $claim, array $payment): void
    {
        $updates = [];

        if (isset($payment['payment_amount'])) {
            $updates['paid_amount'] = $payment['payment_amount'];
        }

        if (isset($payment['total_charge_amount'])) {
            $updates['expected_amount'] = $payment['total_charge_amount'];
        }

        // Calculate payment difference
        if (isset($updates['paid_amount']) && isset($claim->expected_amount)) {
            $updates['payment_difference'] = $claim->expected_amount - $updates['paid_amount'];
        }

        // Update claim status based on payment
        if ($payment['payment_amount'] > 0) {
            $updates['claim_status'] = 'paid';
        } elseif ($payment['payment_amount'] == 0 && !empty($payment['adjustments'])) {
            $updates['claim_status'] = 'denied'; // Denied with adjustments
        }

        if (!empty($updates)) {
            $claim->update($updates);
        }
    }

    /**
     * Map status codes to human-readable statuses
     */
    protected function mapStatusCode(string $code): string
    {
        $mappings = [
            'A0' => 'accepted',
            'A1' => 'accepted',
            'R0' => 'rejected',
            'R1' => 'rejected',
            'P0' => 'partial_accept',
            'P1' => 'partial_accept',
        ];

        return $mappings[$code] ?? 'unknown';
    }
}
