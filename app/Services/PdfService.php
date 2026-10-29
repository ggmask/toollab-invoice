<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Support\View;

class PdfService
{
    public static function generatePdf(string $html, string $paperSize = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('chroot', realpath(__DIR__ . '/../../public'));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    public static function invoicePdf(array $invoice, array $items, array $business, array $customer): string
    {
        $html = View::render('invoices.pdf', [
            'invoice' => $invoice,
            'items' => $items,
            'business' => $business,
            'customer' => $customer,
        ], null);

        return self::generatePdf($html);
    }

    public static function estimatePdf(array $estimate, array $items, array $business, array $customer): string
    {
        $html = View::render('estimates.pdf', [
            'estimate' => $estimate,
            'items' => $items,
            'business' => $business,
            'customer' => $customer,
        ], null);

        return self::generatePdf($html);
    }

    public static function paymentReceiptPdf(array $payment, array $allocations, array $business, array $customer): string
    {
        $html = View::render('payments.pdf', [
            'payment' => $payment,
            'allocations' => $allocations,
            'business' => $business,
            'customer' => $customer,
        ], null);

        return self::generatePdf($html);
    }
}
