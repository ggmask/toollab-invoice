<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * Determine supply type based on state codes or names.
     */
    public static function determineSupplyType(?string $supplierStateCode, ?string $posStateCode): string
    {
        if (empty($supplierStateCode) || empty($posStateCode)) {
            return 'intra_state';
        }

        $s1 = str_pad(trim($supplierStateCode), 2, '0', STR_PAD_LEFT);
        $s2 = str_pad(trim($posStateCode), 2, '0', STR_PAD_LEFT);

        return ($s1 === $s2) ? 'intra_state' : 'inter_state';
    }

    /**
     * Compute GST tax breakdown for a taxable line value.
     */
    public static function calculateTax(
        float $taxableValue,
        float $gstRate,
        string $supplyType = 'intra_state',
        string $taxCategory = 'taxable',
        float $cessRate = 0.0
    ): array {
        if ($taxCategory !== 'taxable' || $gstRate <= 0 || $taxableValue <= 0) {
            return [
                'cgst_rate' => 0.0,
                'cgst_amount' => 0.0,
                'sgst_rate' => 0.0,
                'sgst_amount' => 0.0,
                'igst_rate' => 0.0,
                'igst_amount' => 0.0,
                'cess_rate' => 0.0,
                'cess_amount' => 0.0,
                'total_tax' => 0.0,
            ];
        }

        $cessAmount = round($taxableValue * ($cessRate / 100), 2);

        if ($supplyType === 'intra_state') {
            $halfRate = round($gstRate / 2, 4);
            $cgstAmount = round($taxableValue * ($halfRate / 100), 2);
            $sgstAmount = round($taxableValue * ($halfRate / 100), 2);

            return [
                'cgst_rate' => $halfRate,
                'cgst_amount' => $cgstAmount,
                'sgst_rate' => $halfRate,
                'sgst_amount' => $sgstAmount,
                'igst_rate' => 0.0,
                'igst_amount' => 0.0,
                'cess_rate' => $cessRate,
                'cess_amount' => $cessAmount,
                'total_tax' => round($cgstAmount + $sgstAmount + $cessAmount, 2),
            ];
        } else {
            $igstAmount = round($taxableValue * ($gstRate / 100), 2);

            return [
                'cgst_rate' => 0.0,
                'cgst_amount' => 0.0,
                'sgst_rate' => 0.0,
                'sgst_amount' => 0.0,
                'igst_rate' => $gstRate,
                'igst_amount' => $igstAmount,
                'cess_rate' => $cessRate,
                'cess_amount' => $cessAmount,
                'total_tax' => round($igstAmount + $cessAmount, 2),
            ];
        }
    }
}
