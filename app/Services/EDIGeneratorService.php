<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @template T of Claim
 */

class EDIGeneratorService
{
    protected const SEGMENT_TERMINATOR = '~';
    protected const ELEMENT_SEPARATOR = '*';
    protected const SUBELEMENT_SEPARATOR = ':';

    /**
     * Generate 837P (Professional) EDI for claims
     */
    public function generate837P(Collection $claims, ClearinghouseAccount $account): string
    {
        $edi = '';

        // ISA - Interchange Control Header
        $edi .= $this->generateISA($account);

        // GS - Functional Group Header
        $edi .= $this->generateGS($account, 'HC');

        // ST - Transaction Set Header
        $edi .= $this->generateST('837', $this->generateControlNumber());

        // BHT - Beginning of Hierarchical Transaction
        $edi .= $this->generateBHT();

        // 1000A - Submitter Name
        $edi .= $this->generateSubmitterName($account);

        // 1000B - Receiver Name
        $edi .= $this->generateReceiverName($account);

        // 2000A - Billing Provider Hierarchical Level
        $edi .= $this->generateBillingProvider($claims->first());

        // Process each claim
        /** @var Claim $claim */
        foreach ($claims as $index => $claim) {
            $edi .= $this->generateClaimLoop($claim, $index + 1);
        }

        // SE - Transaction Set Trailer
        $edi .= $this->generateSE();

        // GE - Functional Group Trailer
        $edi .= $this->generateGE();

        // IEA - Interchange Control Trailer
        $edi .= $this->generateIEA();

        return $edi;
    }

    /**
     * Generate 837I (Institutional) EDI for claims
     */
    public function generate837I(Collection $claims, ClearinghouseAccount $account): string
    {
        $edi = '';

        // ISA - Interchange Control Header
        $edi .= $this->generateISA($account);

        // GS - Functional Group Header
        $edi .= $this->generateGS($account, 'HI');

        // ST - Transaction Set Header
        $edi .= $this->generateST('837', $this->generateControlNumber());

        // BHT - Beginning of Hierarchical Transaction
        $edi .= $this->generateBHT();

        // 1000A - Submitter Name
        $edi .= $this->generateSubmitterName($account);

        // 1000B - Receiver Name
        $edi .= $this->generateReceiverName($account);

        // 2000A - Billing Provider Hierarchical Level
        $edi .= $this->generateInstitutionalBillingProvider($claims->first());

        // Process each claim
        /** @var Claim $claim */
        foreach ($claims as $index => $claim) {
            $edi .= $this->generateInstitutionalClaimLoop($claim, $index + 1);
        }

        // SE - Transaction Set Trailer
        $edi .= $this->generateSE();

        // GE - Functional Group Trailer
        $edi .= $this->generateGE();

        // IEA - Interchange Control Trailer
        $edi .= $this->generateIEA();

        return $edi;
    }

    /**
     * Validate EDI content
     */
    public function validateEDI(string $edi): array
    {
        $errors = [];

        // Basic validation checks
        if (empty($edi)) {
            $errors[] = 'EDI content is empty';
            return $errors;
        }

        // Check for required segments
        if (!str_contains($edi, 'ISA' . self::ELEMENT_SEPARATOR)) {
            $errors[] = 'Missing ISA segment';
        }

        if (!str_contains($edi, 'GS' . self::ELEMENT_SEPARATOR)) {
            $errors[] = 'Missing GS segment';
        }

        if (!str_contains($edi, 'ST' . self::ELEMENT_SEPARATOR)) {
            $errors[] = 'Missing ST segment';
        }

        // Check segment terminators
        $segments = explode(self::SEGMENT_TERMINATOR, $edi);
        foreach ($segments as $segment) {
            if (empty(trim($segment))) continue;

            // Validate segment structure
            if (!str_contains($segment, self::ELEMENT_SEPARATOR)) {
                $errors[] = "Invalid segment structure: {$segment}";
            }
        }

        return $errors;
    }

    /**
     * Generate ISA segment
     */
    protected function generateISA(ClearinghouseAccount $account): string
    {
        $credentials = $account->getDecryptedCredentials();

        $isa = 'ISA' . self::ELEMENT_SEPARATOR;
        $isa .= '00' . self::ELEMENT_SEPARATOR; // Authorization Information Qualifier
        $isa .= str_pad('', 10, ' ') . self::ELEMENT_SEPARATOR; // Authorization Information
        $isa .= '00' . self::ELEMENT_SEPARATOR; // Security Information Qualifier
        $isa .= str_pad('', 10, ' ') . self::ELEMENT_SEPARATOR; // Security Information
        $isa .= str_pad($credentials['sender_id'] ?? 'SENDERID', 15, ' ') . self::ELEMENT_SEPARATOR; // Interchange ID Qualifier
        $isa .= str_pad($credentials['sender_id'] ?? 'SENDERID', 15, ' ') . self::ELEMENT_SEPARATOR; // Interchange Sender ID
        $isa .= str_pad($credentials['receiver_id'] ?? 'RECEIVERID', 15, ' ') . self::ELEMENT_SEPARATOR; // Interchange ID Qualifier
        $isa .= str_pad($credentials['receiver_id'] ?? 'RECEIVERID', 15, ' ') . self::ELEMENT_SEPARATOR; // Interchange Receiver ID
        $isa .= now()->format('ymd') . self::ELEMENT_SEPARATOR; // Interchange Date
        $isa .= now()->format('Hi') . self::ELEMENT_SEPARATOR; // Interchange Time
        $isa .= '^' . self::ELEMENT_SEPARATOR; // Repetition Separator
        $isa .= '00501' . self::ELEMENT_SEPARATOR; // Interchange Control Version Number
        $isa .= str_pad($this->generateControlNumber(), 9, '0', STR_PAD_LEFT) . self::ELEMENT_SEPARATOR; // Interchange Control Number
        $isa .= '1' . self::ELEMENT_SEPARATOR; // Acknowledgment Requested
        $isa .= 'P' . self::ELEMENT_SEPARATOR; // Usage Indicator
        $isa .= ':' . self::SEGMENT_TERMINATOR; // Component Element Separator

        return $isa . "\n";
    }

    /**
     * Generate GS segment
     */
    protected function generateGS(ClearinghouseAccount $account, string $functionalId): string
    {
        $credentials = $account->getDecryptedCredentials();

        $gs = 'GS' . self::ELEMENT_SEPARATOR;
        $gs .= $functionalId . self::ELEMENT_SEPARATOR; // Functional Identifier Code
        $gs .= $credentials['sender_id'] ?? 'SENDERID' . self::ELEMENT_SEPARATOR; // Application Sender's Code
        $gs .= $credentials['receiver_id'] ?? 'RECEIVERID' . self::ELEMENT_SEPARATOR; // Application Receiver's Code
        $gs .= now()->format('Ymd') . self::ELEMENT_SEPARATOR; // Date
        $gs .= now()->format('Hi') . self::ELEMENT_SEPARATOR; // Time
        $gs .= $this->generateControlNumber() . self::ELEMENT_SEPARATOR; // Group Control Number
        $gs .= 'X' . self::ELEMENT_SEPARATOR; // Responsible Agency Code
        $gs .= '005010X222A1' . self::SEGMENT_TERMINATOR; // Version/Release/Industry Identifier Code

        return $gs . "\n";
    }

    /**
     * Generate ST segment
     */
    protected function generateST(string $transactionSetId, string $controlNumber): string
    {
        $st = 'ST' . self::ELEMENT_SEPARATOR;
        $st .= $transactionSetId . self::ELEMENT_SEPARATOR; // Transaction Set Identifier Code
        $st .= $controlNumber . self::ELEMENT_SEPARATOR; // Transaction Set Control Number
        $st .= '005010X222A1' . self::SEGMENT_TERMINATOR; // Implementation Convention Reference

        return $st . "\n";
    }

    /**
     * Generate BHT segment
     */
    protected function generateBHT(): string
    {
        $bht = 'BHT' . self::ELEMENT_SEPARATOR;
        $bht .= '0019' . self::ELEMENT_SEPARATOR; // Hierarchical Structure Code
        $bht .= '00' . self::ELEMENT_SEPARATOR; // Transaction Set Purpose Code
        $bht .= $this->generateControlNumber() . self::ELEMENT_SEPARATOR; // Originator Application Transaction Identifier
        $bht .= now()->format('Ymd') . self::ELEMENT_SEPARATOR; // Transaction Set Creation Date
        $bht .= now()->format('Hi') . self::ELEMENT_SEPARATOR; // Transaction Set Creation Time
        $bht .= 'CH' . self::SEGMENT_TERMINATOR; // Claim or Encounter Identifier

        return $bht . "\n";
    }

    /**
     * Generate submitter name (1000A loop)
     */
    protected function generateSubmitterName(ClearinghouseAccount $account): string
    {
        $edi = '';

        // NM1 - Submitter Name
        $edi .= 'NM1' . self::ELEMENT_SEPARATOR;
        $edi .= '41' . self::ELEMENT_SEPARATOR; // Entity Identifier Code
        $edi .= '2' . self::ELEMENT_SEPARATOR; // Entity Type Qualifier
        $edi .= $account->name . self::ELEMENT_SEPARATOR; // Organization Name
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name First
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Middle
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Prefix
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Suffix
        $edi .= '46' . self::ELEMENT_SEPARATOR; // Identification Code Qualifier
        $edi .= 'SUBMITTERID' . self::SEGMENT_TERMINATOR; // Identification Code

        // PER - Submitter EDI Contact Information
        $edi .= 'PER' . self::ELEMENT_SEPARATOR;
        $edi .= 'IC' . self::ELEMENT_SEPARATOR; // Contact Function Code
        $edi .= 'SUBMITTER CONTACT' . self::ELEMENT_SEPARATOR; // Name
        $edi .= 'TE' . self::ELEMENT_SEPARATOR; // Communication Number Qualifier
        $edi .= '5551234567' . self::SEGMENT_TERMINATOR; // Communication Number

        return $edi . "\n";
    }

    /**
     * Generate receiver name (1000B loop)
     */
    protected function generateReceiverName(ClearinghouseAccount $account): string
    {
        $edi = 'NM1' . self::ELEMENT_SEPARATOR;
        $edi .= '40' . self::ELEMENT_SEPARATOR; // Entity Identifier Code
        $edi .= '2' . self::ELEMENT_SEPARATOR; // Entity Type Qualifier
        $edi .= $account->provider . self::ELEMENT_SEPARATOR; // Organization Name
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name First
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Middle
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Prefix
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Suffix
        $edi .= '46' . self::ELEMENT_SEPARATOR; // Identification Code Qualifier
        $edi .= 'RECEIVERID' . self::SEGMENT_TERMINATOR; // Identification Code

        return $edi . "\n";
    }

    /**
     * Generate billing provider (2000A loop) - Professional
     */
    protected function generateBillingProvider(Claim $claim): string
    {
        $edi = '';

        // HL - Billing Provider Hierarchical Level
        $edi .= 'HL' . self::ELEMENT_SEPARATOR;
        $edi .= '1' . self::ELEMENT_SEPARATOR; // Hierarchical ID Number
        $edi .= '' . self::ELEMENT_SEPARATOR; // Hierarchical Parent ID Number
        $edi .= '20' . self::ELEMENT_SEPARATOR; // Hierarchical Level Code
        $edi .= '1' . self::SEGMENT_TERMINATOR; // Hierarchical Child Code

        // PRV - Billing Provider Specialty Information
        $edi .= 'PRV' . self::ELEMENT_SEPARATOR;
        $edi .= 'BI' . self::ELEMENT_SEPARATOR; // Provider Code
        $edi .= 'PXC' . self::ELEMENT_SEPARATOR; // Reference Identification Qualifier
        $edi .= '203BA1200N' . self::SEGMENT_TERMINATOR; // Reference Identification

        // NM1 - Billing Provider Name
        $edi .= 'NM1' . self::ELEMENT_SEPARATOR;
        $edi .= '85' . self::ELEMENT_SEPARATOR; // Entity Identifier Code
        $edi .= '2' . self::ELEMENT_SEPARATOR; // Entity Type Qualifier
        $edi .= $claim->provider_name ?? 'PROVIDER NAME' . self::ELEMENT_SEPARATOR; // Organization Name
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name First
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Middle
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Prefix
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Suffix
        $edi .= 'XX' . self::ELEMENT_SEPARATOR; // Identification Code Qualifier
        $edi .= $claim->provider_npi ?? '1234567890' . self::SEGMENT_TERMINATOR; // Identification Code

        // N3 - Billing Provider Address
        $edi .= 'N3' . self::ELEMENT_SEPARATOR;
        $edi .= '123 PROVIDER ST' . self::SEGMENT_TERMINATOR; // Address Information

        // N4 - Billing Provider City, State, ZIP Code
        $edi .= 'N4' . self::ELEMENT_SEPARATOR;
        $edi .= 'PROVIDER CITY' . self::ELEMENT_SEPARATOR; // City Name
        $edi .= 'NY' . self::ELEMENT_SEPARATOR; // State or Province Code
        $edi .= '12345' . self::SEGMENT_TERMINATOR; // Postal Code

        // REF - Billing Provider Tax Identification
        $edi .= 'REF' . self::ELEMENT_SEPARATOR;
        $edi .= 'EI' . self::ELEMENT_SEPARATOR; // Reference Identification Qualifier
        $edi .= '123456789' . self::SEGMENT_TERMINATOR; // Reference Identification

        return $edi . "\n";
    }

    /**
     * Generate institutional billing provider (2000A loop) - Institutional
     */
    protected function generateInstitutionalBillingProvider(Claim $claim): string
    {
        // Similar to professional but with institutional-specific segments
        return $this->generateBillingProvider($claim);
    }

    /**
     * Generate claim loop (2300) - Professional
     */
    protected function generateClaimLoop(Claim $claim, int $claimNumber): string
    {
        $edi = '';

        // CLM - Claim Information
        $edi .= 'CLM' . self::ELEMENT_SEPARATOR;
        $edi .= $claim->id . self::ELEMENT_SEPARATOR; // Claim Submitter's Identifier
        $edi .= number_format($claim->total_amount ?? 0, 2) . self::ELEMENT_SEPARATOR; // Monetary Amount
        $edi .= '' . self::ELEMENT_SEPARATOR; // Claim Filing Indicator Code
        $edi .= '' . self::ELEMENT_SEPARATOR; // Non-Institutional Claim Type Code
        $edi .= 'Y' . self::ELEMENT_SEPARATOR; // Health Care Service Location Information
        $edi .= 'A' . self::ELEMENT_SEPARATOR; // Provider Accept Assignment Code
        $edi .= 'Y' . self::ELEMENT_SEPARATOR; // Assignment of Benefits Indicator
        $edi .= 'I' . self::SEGMENT_TERMINATOR; // Release of Information Code

        // DTP - Date - Onset of Current Illness or Symptom
        if ($claim->service_date) {
            $edi .= 'DTP' . self::ELEMENT_SEPARATOR;
            $edi .= '431' . self::ELEMENT_SEPARATOR; // Date/Time Qualifier
            $edi .= 'D8' . self::ELEMENT_SEPARATOR; // Date Time Period Format Qualifier
            $edi .= $claim->service_date->format('Ymd') . self::SEGMENT_TERMINATOR; // Date Time Period
        }

        // REF - Claim Identifier for Transmission Intermediaries
        $edi .= 'REF' . self::ELEMENT_SEPARATOR;
        $edi .= 'D9' . self::ELEMENT_SEPARATOR; // Reference Identification Qualifier
        $edi .= $claim->id . self::SEGMENT_TERMINATOR; // Reference Identification

        // HI - Health Care Diagnosis Code
        if (!empty($claim->icd10_codes)) {
            $edi .= 'HI' . self::ELEMENT_SEPARATOR;
            $edi .= 'BK' . self::ELEMENT_SEPARATOR . ($claim->icd10_codes[0] ?? '') . self::SEGMENT_TERMINATOR;
        }

        // NM1 - Subscriber Name
        $edi .= 'NM1' . self::ELEMENT_SEPARATOR;
        $edi .= 'IL' . self::ELEMENT_SEPARATOR; // Entity Identifier Code
        $edi .= '1' . self::ELEMENT_SEPARATOR; // Entity Type Qualifier
        $edi .= $claim->patient_name ?? 'PATIENT NAME' . self::ELEMENT_SEPARATOR; // Name Last or Organization Name
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name First
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Middle
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Prefix
        $edi .= '' . self::ELEMENT_SEPARATOR; // Name Suffix
        $edi .= 'MI' . self::ELEMENT_SEPARATOR; // Identification Code Qualifier
        $edi .= $claim->patient_insurance_id ?? 'INSURANCEID' . self::SEGMENT_TERMINATOR; // Identification Code

        // Generate service lines
        $edi .= $this->generateServiceLines($claim);

        return $edi . "\n";
    }

    /**
     * Generate institutional claim loop (2300) - Institutional
     */
    protected function generateInstitutionalClaimLoop(Claim $claim, int $claimNumber): string
    {
        // Institutional claims have different structure
        return $this->generateClaimLoop($claim, $claimNumber);
    }

    /**
     * Generate service lines (2400 loop)
     */
    protected function generateServiceLines(Claim $claim): string
    {
        $edi = '';

        // LX - Service Line Number
        $edi .= 'LX' . self::ELEMENT_SEPARATOR;
        $edi .= '1' . self::SEGMENT_TERMINATOR;

        // SV1 - Professional Service
        $edi .= 'SV1' . self::ELEMENT_SEPARATOR;
        $edi .= 'HC' . self::ELEMENT_SEPARATOR . ($claim->cpt_codes[0] ?? '99201') . self::ELEMENT_SEPARATOR; // Composite Medical Procedure Identifier
        $edi .= number_format($claim->total_amount ?? 100, 2) . self::ELEMENT_SEPARATOR; // Monetary Amount
        $edi .= 'UN' . self::ELEMENT_SEPARATOR; // Unit or Basis for Measurement Code
        $edi .= '1' . self::ELEMENT_SEPARATOR; // Quantity
        $edi .= '' . self::ELEMENT_SEPARATOR; // Facility Code Value
        $edi .= '' . self::SEGMENT_TERMINATOR; // Service Type Code

        // DTP - Service Date
        if ($claim->service_date) {
            $edi .= 'DTP' . self::ELEMENT_SEPARATOR;
            $edi .= '472' . self::ELEMENT_SEPARATOR; // Date/Time Qualifier
            $edi .= 'D8' . self::ELEMENT_SEPARATOR; // Date Time Period Format Qualifier
            $edi .= $claim->service_date->format('Ymd') . self::SEGMENT_TERMINATOR; // Date Time Period
        }

        return $edi . "\n";
    }

    /**
     * Generate SE segment
     */
    protected function generateSE(): string
    {
        $se = 'SE' . self::ELEMENT_SEPARATOR;
        $se .= '10' . self::ELEMENT_SEPARATOR; // Transaction Segment Count
        $se .= '0001' . self::SEGMENT_TERMINATOR; // Transaction Set Control Number

        return $se . "\n";
    }

    /**
     * Generate GE segment
     */
    protected function generateGE(): string
    {
        $ge = 'GE' . self::ELEMENT_SEPARATOR;
        $ge .= '1' . self::ELEMENT_SEPARATOR; // Number of Transaction Sets Included
        $ge .= '1' . self::SEGMENT_TERMINATOR; // Group Control Number

        return $ge . "\n";
    }

    /**
     * Generate IEA segment
     */
    protected function generateIEA(): string
    {
        $iea = 'IEA' . self::ELEMENT_SEPARATOR;
        $iea .= '1' . self::ELEMENT_SEPARATOR; // Number of Included Functional Groups
        $iea .= '000000001' . self::SEGMENT_TERMINATOR; // Interchange Control Number

        return $iea . "\n";
    }

    /**
     * Generate a unique control number
     */
    protected function generateControlNumber(): string
    {
        return str_pad(mt_rand(1, 999999), 9, '0', STR_PAD_LEFT);
    }
}
