<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\InvoiceCalculationService;

final class InvoiceCalculationServiceTest extends TestCase
{
    public function testIntraStateGstSplit(): void
    {
        $items = [
            [
                'name' => 'Web Design Service',
                'quantity' => 1,
                'unit_price' => 10000.00,
                'gst_rate' => 18.0,
            ]
        ];

        $res = InvoiceCalculationService::calculate($items, 'intra_state');

        $this->assertEquals(10000.00, $res['taxable_value']);
        $this->assertEquals(900.00, $res['cgst_amount']);
        $this->assertEquals(900.00, $res['sgst_amount']);
        $this->assertEquals(0.00, $res['igst_amount']);
        $this->assertEquals(1800.00, $res['tax_total']);
        $this->assertEquals(11800.00, $res['total_amount']);
    }

    public function testInterStateIgstCalculation(): void
    {
        $items = [
            [
                'name' => 'Consulting',
                'quantity' => 2,
                'unit_price' => 5000.00,
                'gst_rate' => 18.0,
            ]
        ];

        $res = InvoiceCalculationService::calculate($items, 'inter_state');

        $this->assertEquals(10000.00, $res['taxable_value']);
        $this->assertEquals(0.00, $res['cgst_amount']);
        $this->assertEquals(0.00, $res['sgst_amount']);
        $this->assertEquals(1800.00, $res['igst_amount']);
        $this->assertEquals(1800.00, $res['tax_total']);
        $this->assertEquals(11800.00, $res['total_amount']);
    }

    public function testReverseChargeMechanismRcm(): void
    {
        $items = [
            [
                'name' => 'Legal Advisory Services',
                'quantity' => 1,
                'unit_price' => 50000.00,
                'gst_rate' => 18.0,
            ]
        ];

        $res = InvoiceCalculationService::calculate(
            $items,
            'intra_state',
            'flat',
            0.0,
            0.0,
            0.0,
            true // isReverseCharge = true
        );

        $this->assertEquals(50000.00, $res['taxable_value']);
        $this->assertEquals(4500.00, $res['cgst_amount'], 'CGST calculated for statutory disclosure');
        $this->assertEquals(4500.00, $res['sgst_amount'], 'SGST calculated for statutory disclosure');
        $this->assertEquals(9000.00, $res['tax_total']);
        $this->assertEquals(50000.00, $res['total_amount'], 'RCM taxes must be excluded from customer payable total');
    }

    public function testTaxableFreightCharge(): void
    {
        $items = [
            [
                'name' => 'Hardware Widget',
                'quantity' => 10,
                'unit_price' => 100.00,
                'gst_rate' => 18.0,
            ]
        ];

        // 1000 base + 100 freight with 18% GST (freight taxable under Section 15(2)(c))
        $res = InvoiceCalculationService::calculate(
            $items,
            'intra_state',
            'flat',
            0.0,
            100.0, // freight
            18.0   // freight GST rate
        );

        $this->assertEquals(1100.00, $res['taxable_value'], 'Taxable value includes goods (1000) + freight (100)');
        $this->assertEquals(99.00, $res['cgst_amount'], 'CGST covers goods (90) + freight (9)');
        $this->assertEquals(99.00, $res['sgst_amount'], 'SGST covers goods (90) + freight (9)');
        $this->assertEquals(198.00, $res['tax_total']);
        $this->assertEquals(1298.00, $res['total_amount']);
    }

    public function testTaxInclusivePricing(): void
    {
        $items = [
            [
                'name' => 'Retail Item (MRP)',
                'quantity' => 1,
                'unit_price' => 1180.00,
                'gst_rate' => 18.0,
                'is_tax_inclusive' => true,
            ]
        ];

        $res = InvoiceCalculationService::calculate($items, 'intra_state');

        $this->assertEquals(1000.00, $res['taxable_value']);
        $this->assertEquals(90.00, $res['cgst_amount']);
        $this->assertEquals(90.00, $res['sgst_amount']);
        $this->assertEquals(180.00, $res['tax_total']);
        $this->assertEquals(1180.00, $res['total_amount']);
    }

    public function testLineAndInvoiceDiscountsWithRounding(): void
    {
        $items = [
            [
                'name' => 'Item A',
                'quantity' => 3,
                'unit_price' => 333.33,
                'discount_type' => 'percentage',
                'discount_value' => 10.0, // 10% line discount
                'gst_rate' => 18.0,
            ]
        ];

        $res = InvoiceCalculationService::calculate(
            $items,
            'intra_state',
            'flat',
            50.0 // 50 flat invoice discount
        );

        $this->assertIsFloat($res['rounding_adjustment']);
        $this->assertEquals(round($res['total_amount']), $res['total_amount'], 'Total amount must be rounded');
    }
}
