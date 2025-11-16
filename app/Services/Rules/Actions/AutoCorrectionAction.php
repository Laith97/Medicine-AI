<?php

namespace App\Services\Rules\Actions;

class AutoCorrectionAction
{
    /**
     * Build auto-correction action to update a field.
     *
     * @param string $field
     * @param mixed $newValue
     * @param string $message
     * @return array
     */
    public static function updateField(string $field, $newValue, string $message = ''): array
    {
        if (!$message) {
            $message = "Auto-corrected {$field} to {$newValue}";
        }

        return [
            'type' => 'auto_correction',
            'field' => $field,
            'new_value' => $newValue,
            'message' => $message,
        ];
    }

    /**
     * Build auto-correction action to update diagnosis codes.
     *
     * @param array $newCodes
     * @param string $reason
     * @return array
     */
    public static function updateDiagnosisCodes(array $newCodes, string $reason = ''): array
    {
        $message = 'Auto-corrected diagnosis codes';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return self::updateField('icd10_codes', $newCodes, $message);
    }

    /**
     * Build auto-correction action to update procedure codes.
     *
     * @param array $newCodes
     * @param string $reason
     * @return array
     */
    public static function updateProcedureCodes(array $newCodes, string $reason = ''): array
    {
        $message = 'Auto-corrected procedure codes';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return self::updateField('cpt_codes', $newCodes, $message);
    }

    /**
     * Build auto-correction action to update claim amount.
     *
     * @param float $newAmount
     * @param string $reason
     * @return array
     */
    public static function updateAmount(float $newAmount, string $reason = ''): array
    {
        $message = "Auto-corrected amount to \${$newAmount}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return self::updateField('expected_amount', $newAmount, $message);
    }

    /**
     * Build auto-correction action to add modifier to procedure codes.
     *
     * @param string $modifier
     * @param string $reason
     * @return array
     */
    public static function addModifier(string $modifier, string $reason = ''): array
    {
        $message = "Added modifier {$modifier} to procedure codes";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return [
            'type' => 'auto_correction',
            'action' => 'add_modifier',
            'modifier' => $modifier,
            'message' => $message,
        ];
    }

    /**
     * Build auto-correction action to update place of service.
     *
     * @param string $pos
     * @param string $reason
     * @return array
     */
    public static function updatePlaceOfService(string $pos, string $reason = ''): array
    {
        $message = "Updated place of service to {$pos}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return self::updateField('place_of_service', $pos, $message);
    }

    /**
     * Build auto-correction action to update units.
     *
     * @param int $units
     * @param string $reason
     * @return array
     */
    public static function updateUnits(int $units, string $reason = ''): array
    {
        $message = "Updated units to {$units}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return self::updateField('units', $units, $message);
    }
}
