<?php

namespace App\Services\Rules\Conditions;

class PayerCondition
{
    /**
     * Build condition for payer equals.
     *
     * @param string $payerId
     * @return array
     */
    public static function equals(string $payerId): array
    {
        return [
            'type' => 'payer',
            'operator' => 'equals',
            'value' => $payerId,
        ];
    }

    /**
     * Build condition for payer not equals.
     *
     * @param string $payerId
     * @return array
     */
    public static function notEquals(string $payerId): array
    {
        return [
            'type' => 'payer',
            'operator' => 'not_equals',
            'value' => $payerId,
        ];
    }

    /**
     * Build condition for payer contains.
     *
     * @param string $substring
     * @return array
     */
    public static function contains(string $substring): array
    {
        return [
            'type' => 'payer',
            'operator' => 'contains',
            'value' => $substring,
        ];
    }

    /**
     * Build condition for payer in list.
     *
     * @param array $payerIds
     * @return array
     */
    public static function in(array $payerIds): array
    {
        return [
            'type' => 'payer',
            'operator' => 'in',
            'value' => $payerIds,
        ];
    }

    /**
     * Build condition for payer not in list.
     *
     * @param array $payerIds
     * @return array
     */
    public static function notIn(array $payerIds): array
    {
        return [
            'type' => 'payer',
            'operator' => 'not_in',
            'value' => $payerIds,
        ];
    }

    /**
     * Build condition for payer starts with.
     *
     * @param string $prefix
     * @return array
     */
    public static function startsWith(string $prefix): array
    {
        return [
            'type' => 'payer',
            'operator' => 'starts_with',
            'value' => $prefix,
        ];
    }

    /**
     * Build condition for payer ends with.
     *
     * @param string $suffix
     * @return array
     */
    public static function endsWith(string $suffix): array
    {
        return [
            'type' => 'payer',
            'operator' => 'ends_with',
            'value' => $suffix,
        ];
    }
}
