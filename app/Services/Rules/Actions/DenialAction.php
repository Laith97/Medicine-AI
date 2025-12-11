<?php

namespace App\Services\Rules\Actions;

class DenialAction
{
    /**
     * Build denial action with reason and code.
     *
     * @param string $reason
     * @param string $code
     * @return array
     */
    public static function create(string $reason, string $code = ''): array
    {
        return [
            'type' => 'denial',
            'reason' => $reason,
            'code' => $code,
        ];
    }

    /**
     * Build denial action for missing documentation.
     *
     * @param string $specificDocs
     * @return array
     */
    public static function missingDocumentation(string $specificDocs = ''): array
    {
        $reason = 'Claim denied due to missing documentation';
        if ($specificDocs) {
            $reason .= ": {$specificDocs}";
        }

        return self::create($reason, '16');
    }

    /**
     * Build denial action for coding error.
     *
     * @param string $details
     * @return array
     */
    public static function codingError(string $details = ''): array
    {
        $reason = 'Claim denied due to coding error';
        if ($details) {
            $reason .= ": {$details}";
        }

        return self::create($reason, '4');
    }

    /**
     * Build denial action for medical necessity.
     *
     * @param string $details
     * @return array
     */
    public static function medicalNecessity(string $details = ''): array
    {
        $reason = 'Claim denied due to lack of medical necessity';
        if ($details) {
            $reason .= ": {$details}";
        }

        return self::create($reason, '50');
    }

    /**
     * Build denial action for coverage issue.
     *
     * @param string $details
     * @return array
     */
    public static function coverageIssue(string $details = ''): array
    {
        $reason = 'Claim denied due to coverage limitations';
        if ($details) {
            $reason .= ": {$details}";
        }

        return self::create($reason, '1');
    }

    /**
     * Build denial action for timely filing.
     *
     * @return array
     */
    public static function timelyFiling(): array
    {
        return self::create('Claim denied due to timely filing requirements', '54');
    }

    /**
     * Build denial action for duplicate claim.
     *
     * @return array
     */
    public static function duplicateClaim(): array
    {
        return self::create('Claim denied as duplicate submission', '18');
    }

    /**
     * Build denial action for non-covered service.
     *
     * @param string $service
     * @return array
     */
    public static function nonCoveredService(string $service = ''): array
    {
        $reason = 'Service not covered by plan';
        if ($service) {
            $reason .= ": {$service}";
        }

        return self::create($reason, '109');
    }

    /**
     * Build denial action for authorization required.
     *
     * @return array
     */
    public static function authorizationRequired(): array
    {
        return self::create('Prior authorization required but not obtained', '96');
    }

    /**
     * Build denial action for experimental treatment.
     *
     * @return array
     */
    public static function experimentalTreatment(): array
    {
        return self::create('Treatment considered experimental/investigational', '97');
    }
}
