<?php

namespace App\Services\Rules\Conditions;

class DiagnosisCodeCondition
{
    /**
     * Build condition for diagnosis code contains.
     *
     * @param string $code
     * @return array
     */
    public static function contains(string $code): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'contains',
            'value' => $code,
        ];
    }

    /**
     * Build condition for diagnosis code not contains.
     *
     * @param string $code
     * @return array
     */
    public static function notContains(string $code): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'not_contains',
            'value' => $code,
        ];
    }

    /**
     * Build condition for diagnosis codes empty.
     *
     * @return array
     */
    public static function isEmpty(): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'empty',
            'value' => null,
        ];
    }

    /**
     * Build condition for diagnosis codes not empty.
     *
     * @return array
     */
    public static function isNotEmpty(): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'not_empty',
            'value' => null,
        ];
    }

    /**
     * Build condition for multiple diagnosis codes (any match).
     *
     * @param array $codes
     * @return array
     */
    public static function containsAny(array $codes): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'contains_any',
            'value' => $codes,
        ];
    }

    /**
     * Build condition for diagnosis code pattern match.
     *
     * @param string $pattern
     * @return array
     */
    public static function matchesPattern(string $pattern): array
    {
        return [
            'type' => 'diagnosis_code',
            'operator' => 'matches_pattern',
            'value' => $pattern,
        ];
    }
}
