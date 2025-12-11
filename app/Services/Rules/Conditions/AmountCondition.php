<?php

namespace App\Services\Rules\Conditions;

class AmountCondition
{
    /**
     * Build condition for amount equals.
     *
     * @param float $amount
     * @return array
     */
    public static function equals(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'equals',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount not equals.
     *
     * @param float $amount
     * @return array
     */
    public static function notEquals(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'not_equals',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount greater than.
     *
     * @param float $amount
     * @return array
     */
    public static function greaterThan(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'greater_than',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount less than.
     *
     * @param float $amount
     * @return array
     */
    public static function lessThan(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'less_than',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount greater than or equal.
     *
     * @param float $amount
     * @return array
     */
    public static function greaterThanOrEqual(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'greater_equal',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount less than or equal.
     *
     * @param float $amount
     * @return array
     */
    public static function lessThanOrEqual(float $amount): array
    {
        return [
            'type' => 'amount',
            'operator' => 'less_equal',
            'value' => $amount,
        ];
    }

    /**
     * Build condition for amount between range.
     *
     * @param float $min
     * @param float $max
     * @return array
     */
    public static function between(float $min, float $max): array
    {
        return [
            'type' => 'amount',
            'operator' => 'between',
            'value' => ['min' => $min, 'max' => $max],
        ];
    }

    /**
     * Build condition for amount in percentage range of another field.
     *
     * @param string $field
     * @param float $percentage
     * @param string $direction 'above' or 'below'
     * @return array
     */
    public static function percentageOf(string $field, float $percentage, string $direction = 'above'): array
    {
        return [
            'type' => 'amount',
            'operator' => 'percentage_of',
            'value' => [
                'field' => $field,
                'percentage' => $percentage,
                'direction' => $direction,
            ],
        ];
    }
}
