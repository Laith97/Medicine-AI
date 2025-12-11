<?php

namespace App\Services\Rules\Conditions;

class ProcedureCodeCondition
{
    /**
     * Build condition for procedure code contains.
     *
     * @param string $code
     * @return array
     */
    public static function contains(string $code): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'contains',
            'value' => $code,
        ];
    }

    /**
     * Build condition for procedure code not contains.
     *
     * @param string $code
     * @return array
     */
    public static function notContains(string $code): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'not_contains',
            'value' => $code,
        ];
    }

    /**
     * Build condition for procedure codes empty.
     *
     * @return array
     */
    public static function isEmpty(): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'empty',
            'value' => null,
        ];
    }

    /**
     * Build condition for procedure codes not empty.
     *
     * @return array
     */
    public static function isNotEmpty(): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'not_empty',
            'value' => null,
        ];
    }

    /**
     * Build condition for multiple procedure codes (any match).
     *
     * @param array $codes
     * @return array
     */
    public static function containsAny(array $codes): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'contains_any',
            'value' => $codes,
        ];
    }

    /**
     * Build condition for procedure code pattern match.
     *
     * @param string $pattern
     * @return array
     */
    public static function matchesPattern(string $pattern): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'matches_pattern',
            'value' => $pattern,
        ];
    }

    /**
     * Build condition for procedure code category match.
     *
     * @param string $category
     * @return array
     */
    public static function inCategory(string $category): array
    {
        return [
            'type' => 'procedure_code',
            'operator' => 'in_category',
            'value' => $category,
        ];
    }
}
