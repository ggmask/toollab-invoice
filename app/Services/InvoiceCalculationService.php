<?php

namespace App\Services;

class InvoiceCalculationService
{
    /**
     * Calculate financial totals for line items and document-level adjustments.
     */
    public static function calculate(
        array $items,
        string $supplyType = 'intra_state',
        string $discountType = 'flat',
        float $discountValue = 0.0,
        float $additionalCharges = 0.0
    ): array {
        $calculatedItems = [];
        $subtotalGross = 0.0;
        $lineDiscountsTotal = 0.0;
        $preDiscountTaxableTotal = 0.0;

        // Step 1: Calculate raw line gross, discounts, and pre-invoice-discount taxable value
        foreach ($items as $index => $item) {
            $qty = max(0.001, (float)($item['quantity'] ?? 1));
            $unitPrice = max(0.0, (float)($item['unit_price'] ?? 0));
            $gross = round($qty * $unitPrice, 2);

            $discType = ($item['discount_type'] ?? 'percentage') === 'flat' ? 'flat' : 'percentage';
            $discVal = max(0.0, (float)($item['discount_value'] ?? 0));

            if ($discType === 'percentage') {
                $lineDiscAmt = round($gross * ($discVal / 100), 2);
            } else {
                $lineDiscAmt = min($gross, round($discVal, 2));
            }

            $lineTaxable = max(0.0, round($gross - $lineDiscAmt, 2));

            $subtotalGross += $gross;
            $lineDiscountsTotal += $lineDiscAmt;
            $preDiscountTaxableTotal += $lineTaxable;

            $calculatedItems[] = [
                'item_order' => $index + 1,
                'product_id' => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                'name' => trim($item['name'] ?? 'Item'),
                'description' => trim($item['description'] ?? ''),
                'hsn_sac' => trim($item['hsn_sac'] ?? ''),
                'item_type' => ($item['item_type'] ?? 'goods') === 'services' ? 'services' : 'goods',
                'quantity' => $qty,
                'unit' => trim($item['unit'] ?? 'PCS'),
                'unit_price' => $unitPrice,
                'gross_amount' => $gross,
                'discount_type' => $discType,
                'discount_value' => $discVal,
                'discount_amount' => $lineDiscAmt,
                'pre_disc_taxable' => $lineTaxable,
                'taxable_value' => $lineTaxable,
                'tax_category' => $item['tax_category'] ?? 'taxable',
                'gst_rate' => max(0.0, (float)($item['gst_rate'] ?? 18)),
                'cess_rate' => max(0.0, (float)($item['cess_rate'] ?? 0)),
            ];
        }

        // Step 2: Calculate invoice-level discount amount
        $invoiceDiscountAmount = 0.0;
        if ($preDiscountTaxableTotal > 0 && $discountValue > 0) {
            if ($discountType === 'percentage') {
                $invoiceDiscountAmount = round($preDiscountTaxableTotal * ($discountValue / 100), 2);
            } else {
                $invoiceDiscountAmount = min($preDiscountTaxableTotal, round($discountValue, 2));
            }
        }

        // Step 3: Pro-rate invoice-level discount across lines and compute taxes
        $totalTaxable = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;
        $totalCess = 0.0;
        $allocatedDiscountSum = 0.0;
        $itemCount = count($calculatedItems);

        foreach ($calculatedItems as $idx => &$line) {
            if ($invoiceDiscountAmount > 0 && $preDiscountTaxableTotal > 0) {
                // If last item, reconcile any cent rounding difference
                if ($idx === $itemCount - 1) {
                    $proRatedLineDiscount = round($invoiceDiscountAmount - $allocatedDiscountSum, 2);
                } else {
                    $proportion = $line['pre_disc_taxable'] / $preDiscountTaxableTotal;
                    $proRatedLineDiscount = round($invoiceDiscountAmount * $proportion, 2);
                    $allocatedDiscountSum += $proRatedLineDiscount;
                }
                $finalLineTaxable = max(0.0, round($line['pre_disc_taxable'] - $proRatedLineDiscount, 2));
            } else {
                $finalLineTaxable = $line['pre_disc_taxable'];
            }

            $line['taxable_value'] = $finalLineTaxable;

            // Calculate GST
            $taxes = TaxCalculationService::calculateTax(
                $finalLineTaxable,
                $line['gst_rate'],
                $supplyType,
                $line['tax_category'],
                $line['cess_rate']
            );

            $line['cgst_rate'] = $taxes['cgst_rate'];
            $line['cgst_amount'] = $taxes['cgst_amount'];
            $line['sgst_rate'] = $taxes['sgst_rate'];
            $line['sgst_amount'] = $taxes['sgst_amount'];
            $line['igst_rate'] = $taxes['igst_rate'];
            $line['igst_amount'] = $taxes['igst_amount'];
            $line['cess_rate'] = $taxes['cess_rate'];
            $line['cess_amount'] = $taxes['cess_amount'];
            $line['total_tax'] = $taxes['total_tax'];
            $line['line_total'] = round($finalLineTaxable + $taxes['total_tax'], 2);

            $totalTaxable += $finalLineTaxable;
            $totalCgst += $taxes['cgst_amount'];
            $totalSgst += $taxes['sgst_amount'];
            $totalIgst += $taxes['igst_amount'];
            $totalCess += $taxes['cess_amount'];
            unset($line['pre_disc_taxable']);
        }
        unset($line);

        $taxTotal = round($totalCgst + $totalSgst + $totalIgst + $totalCess, 2);
        $subtotalWithTaxAndCharges = round($totalTaxable + $taxTotal + $additionalCharges, 2);

        // Document level rounding to nearest rupee
        $finalTotal = round($subtotalWithTaxAndCharges);
        $roundingAdj = round($finalTotal - $subtotalWithTaxAndCharges, 2);

        return [
            'items' => $calculatedItems,
            'subtotal_gross' => round($subtotalGross, 2),
            'line_discounts_total' => round($lineDiscountsTotal, 2),
            'invoice_discount_type' => $discountType,
            'invoice_discount_value' => $discountValue,
            'invoice_discount_amount' => round($invoiceDiscountAmount, 2),
            'taxable_value' => round($totalTaxable, 2),
            'cgst_amount' => round($totalCgst, 2),
            'sgst_amount' => round($totalSgst, 2),
            'igst_amount' => round($totalIgst, 2),
            'cess_amount' => round($totalCess, 2),
            'tax_total' => $taxTotal,
            'additional_charges' => round($additionalCharges, 2),
            'rounding_adjustment' => $roundingAdj,
            'total_amount' => $finalTotal,
        ];
    }
}
