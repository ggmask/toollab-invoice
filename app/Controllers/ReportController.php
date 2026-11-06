<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\ExportService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class ReportController
{
    public function index(Request $request): Response
    {
        return new Response(View::render('reports.index', [
            'title' => 'Financial Reports & Analytics',
        ]));
    }

    public function sales(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $status = $request->get('status', '');
        $customerId = $request->get('customer_id', '');
        $export = $request->get('export', '');

        $where = ["i.business_id = ?", "i.issue_date BETWEEN ? AND ?"];
        $params = [$businessId, $startDate, $endDate];

        if ($status !== '') {
            $where[] = "i.status = ?";
            $params[] = $status;
        } else {
            $where[] = "i.status != 'void'";
        }

        if ($customerId !== '') {
            $where[] = "i.customer_id = ?";
            $params[] = (int)$customerId;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name, c.gstin as customer_gstin
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE {$whereSql}
            ORDER BY i.issue_date ASC, i.id ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aggregate Totals
        $totTaxable = 0.0;
        $totCgst = 0.0;
        $totSgst = 0.0;
        $totIgst = 0.0;
        $totAmount = 0.0;
        $totPaid = 0.0;
        $totBalance = 0.0;

        foreach ($rows as $r) {
            $totTaxable += (float)$r['taxable_value'];
            $totCgst += (float)$r['cgst_amount'];
            $totSgst += (float)$r['sgst_amount'];
            $totIgst += (float)$r['igst_amount'];
            $totAmount += (float)$r['total_amount'];
            $totPaid += (float)$r['paid_amount'];
            $totBalance += (float)$r['balance_due'];
        }

        if ($export === 'csv' || $export === 'excel') {
            AuditService::log('report.exported', 'report', null, ['type' => 'sales', 'format' => $export]);
            $headers = ['Invoice #', 'Date', 'Due Date', 'Customer', 'GSTIN', 'Supply Type', 'Place of Supply', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Amount', 'Paid Amount', 'Balance Due', 'Status'];
            $data = [];
            foreach ($rows as $r) {
                $isInter = ($r['supply_type'] ?? '') === 'inter_state' || (float)($r['igst_amount'] ?? 0) > 0;
                $pos = trim(($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') . ($r['place_of_supply_state'] ?? ''));
                $data[] = [
                    $r['invoice_number'],
                    $r['issue_date'],
                    $r['due_date'],
                    $r['customer_name'],
                    $r['customer_gstin'] ?: 'Unregistered',
                    $isInter ? 'Inter-State' : 'Intra-State',
                    $pos ?: 'Default',
                    (float)$r['taxable_value'],
                    (float)$r['cgst_amount'],
                    (float)$r['sgst_amount'],
                    (float)$r['igst_amount'],
                    (float)$r['total_amount'],
                    (float)$r['paid_amount'],
                    (float)$r['balance_due'],
                    ucfirst(str_replace('_', ' ', $r['status'])),
                ];
            }
            // Add total summary row
            $data[] = ['TOTAL', '', '', '', '', $totTaxable, $totCgst, $totSgst, $totIgst, $totAmount, $totPaid, $totBalance, ''];

            $filename = "sales-report-{$startDate}-to-{$endDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml('Sales Report', $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        $cStmt = $pdo->prepare("SELECT id, name FROM customers WHERE business_id = ? ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('reports.sales', [
            'title' => 'Sales Register Report',
            'rows' => $rows,
            'customers' => $customers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'customerId' => $customerId,
            'totals' => [
                'taxable' => $totTaxable,
                'cgst' => $totCgst,
                'sgst' => $totSgst,
                'igst' => $totIgst,
                'total' => $totAmount,
                'paid' => $totPaid,
                'balance' => $totBalance,
            ],
        ]));
    }

    public function gstSales(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $section = $request->get('section', 'all');
        $export = $request->get('export', '');

        // Fetch invoice line items for detailed GST breakdown
        $stmt = $pdo->prepare("
            SELECT 
                i.id as invoice_id, i.invoice_number, i.issue_date, i.customer_name, i.customer_gstin,
                i.place_of_supply_state, i.place_of_supply_state_code, i.supply_type, i.is_reverse_charge,
                ii.hsn_sac, ii.name as item_name, ii.quantity, ii.unit,
                ii.taxable_value, ii.gst_rate, ii.cgst_amount, ii.sgst_amount, ii.igst_amount, ii.cess_amount, ii.line_total,
                i.total_amount as invoice_total, i.document_type
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            WHERE i.business_id = ? 
              AND i.status IN ('issued', 'partially_paid', 'paid')
              AND i.issue_date BETWEEN ? AND ?
            ORDER BY i.issue_date ASC, i.id ASC, ii.item_order ASC
        ");
        $stmt->execute([$businessId, $startDate, $endDate]);
        $rawSlabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also incorporate any freight / additional charges with GST so tax summary matches invoice totals exactly
        $invStmt = $pdo->prepare("
            SELECT 
                i.id as invoice_id, i.invoice_number, i.issue_date,
                i.additional_charges,
                i.cgst_amount as inv_cgst, i.sgst_amount as inv_sgst, i.igst_amount as inv_igst,
                COALESCE(SUM(ii.cgst_amount), 0) as items_cgst,
                COALESCE(SUM(ii.sgst_amount), 0) as items_sgst,
                COALESCE(SUM(ii.igst_amount), 0) as items_igst
            FROM invoices i
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            WHERE i.business_id = ? 
              AND i.status IN ('issued', 'partially_paid', 'paid')
              AND i.issue_date BETWEEN ? AND ?
              AND i.additional_charges > 0
            GROUP BY i.id
        ");
        $invStmt->execute([$businessId, $startDate, $endDate]);
        $freightInvoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

        $slabMap = [];
        foreach ($rawSlabs as $r) {
            $rateKey = (string)(float)$r['gst_rate'];
            $slabMap[$rateKey] = [
                'gst_rate' => (float)$r['gst_rate'],
                'invoice_count' => (int)$r['invoice_count'],
                'total_taxable' => (float)$r['total_taxable'],
                'total_cgst' => (float)$r['total_cgst'],
                'total_sgst' => (float)$r['total_sgst'],
                'total_igst' => (float)$r['total_igst'],
                'total_cess' => (float)$r['total_cess'],
                'total_tax' => (float)$r['total_tax'],
                'total_amount' => (float)$r['total_amount'],
            ];
        }

        foreach ($freightInvoices as $fi) {
            $addTaxable = (float)$fi['additional_charges'];
            $addCgst = max(0.0, (float)$fi['inv_cgst'] - (float)$fi['items_cgst']);
            $addSgst = max(0.0, (float)$fi['inv_sgst'] - (float)$fi['items_sgst']);
            $addIgst = max(0.0, (float)$fi['inv_igst'] - (float)$fi['items_igst']);
            $addTax = $addCgst + $addSgst + $addIgst;

            $freightRate = ($addTaxable > 0 && $addTax > 0) ? round(($addTax / $addTaxable) * 100, 1) : 0.0;
            $rateKey = (string)$freightRate;

            if (!isset($slabMap[$rateKey])) {
                $slabMap[$rateKey] = [
                    'gst_rate' => $freightRate,
                    'invoice_count' => 0,
                    'total_taxable' => 0.0,
                    'total_cgst' => 0.0,
                    'total_sgst' => 0.0,
                    'total_igst' => 0.0,
                    'total_cess' => 0.0,
                    'total_tax' => 0.0,
                    'total_amount' => 0.0,
                ];
            }
            $slabMap[$rateKey]['total_taxable'] += $addTaxable;
            $slabMap[$rateKey]['total_cgst'] += $addCgst;
            $slabMap[$rateKey]['total_sgst'] += $addSgst;
            $slabMap[$rateKey]['total_igst'] += $addIgst;
            $slabMap[$rateKey]['total_tax'] += $addTax;
            $slabMap[$rateKey]['total_amount'] += round($addTaxable + $addTax, 2);
        }
        ksort($slabMap, SORT_NUMERIC);
        $rows = array_values($slabMap);

        $b2bRows = [];
        $b2cLargeRows = [];
        $b2cSmallRows = [];
        $nilExemptRows = [];
        $hsnMap = [];

        foreach ($rows as $r) {
            $hasGstin = !empty(trim($r['customer_gstin'] ?? ''));
            $isInterState = ($r['supply_type'] === 'inter_state');
            $invTotal = (float)$r['invoice_total'];
            $isZeroOrExempt = ((float)$r['gst_rate'] == 0 || in_array($r['document_type'], ['bill_of_supply', 'exempt']));

            if ($isZeroOrExempt) {
                $nilExemptRows[] = $r;
            }

            if ($hasGstin) {
                $b2bRows[] = $r;
            } else {
                if ($isInterState && $invTotal > 250000) {
                    $b2cLargeRows[] = $r;
                } else {
                    $b2cSmallRows[] = $r;
                }
            }

            // HSN Aggregation
            $hsnCode = !empty($r['hsn_sac']) ? trim($r['hsn_sac']) : 'OTHER';
            $uqc = !empty($r['unit']) ? strtoupper(trim($r['unit'])) : 'OTH';
            $hKey = $hsnCode . '_' . $uqc;

            if (!isset($hsnMap[$hKey])) {
                $hsnMap[$hKey] = [
                    'hsn_sac' => $hsnCode,
                    'description' => $r['item_name'],
                    'uqc' => $uqc,
                    'total_qty' => 0.0,
                    'total_value' => 0.0,
                    'taxable_value' => 0.0,
                    'igst_amount' => 0.0,
                    'cgst_amount' => 0.0,
                    'sgst_amount' => 0.0,
                    'cess_amount' => 0.0,
                    'total_tax' => 0.0,
                ];
            }
            $hsnMap[$hKey]['total_qty'] += (float)$r['quantity'];
            $hsnMap[$hKey]['total_value'] += (float)$r['line_total'];
            $hsnMap[$hKey]['taxable_value'] += (float)$r['taxable_value'];
            $hsnMap[$hKey]['igst_amount'] += (float)$r['igst_amount'];
            $hsnMap[$hKey]['cgst_amount'] += (float)$r['cgst_amount'];
            $hsnMap[$hKey]['sgst_amount'] += (float)$r['sgst_amount'];
            $hsnMap[$hKey]['cess_amount'] += (float)$r['cess_amount'];
            $hsnMap[$hKey]['total_tax'] += ((float)$r['igst_amount'] + (float)$r['cgst_amount'] + (float)$r['sgst_amount'] + (float)$r['cess_amount']);
        }
        $hsnSummary = array_values($hsnMap);

        if ($export === 'csv' || $export === 'excel') {
            AuditService::log('report.exported', 'report', null, ['type' => 'gst_sales', 'section' => $section, 'format' => $export]);

            if ($section === 'hsn') {
                $headers = ['HSN/SAC', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Taxable Value', 'Integrated Tax', 'Central Tax', 'State/UT Tax', 'Cess Amount', 'Total Tax'];
                $data = [];
                foreach ($hsnSummary as $h) {
                    $data[] = [
                        $h['hsn_sac'],
                        $h['description'],
                        $h['uqc'],
                        (float)$h['total_qty'],
                        (float)$h['total_value'],
                        (float)$h['taxable_value'],
                        (float)$h['igst_amount'],
                        (float)$h['cgst_amount'],
                        (float)$h['sgst_amount'],
                        (float)$h['cess_amount'],
                        (float)$h['total_tax'],
                    ];
                }
                $reportTitle = 'GSTR-1 Table 12 HSN Summary';
            } elseif ($section === 'b2b') {
                $headers = ['GSTIN/UIN', 'Receiver Name', 'Invoice #', 'Invoice Date', 'Invoice Value', 'Place of Supply', 'Reverse Charge', 'Rate %', 'Taxable Value', 'CGST', 'SGST', 'IGST'];
                $data = [];
                foreach ($b2bRows as $r) {
                    $data[] = [
                        $r['customer_gstin'],
                        $r['customer_name'],
                        $r['invoice_number'],
                        $r['issue_date'],
                        (float)$r['invoice_total'],
                        ($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') . $r['place_of_supply_state'],
                        $r['is_reverse_charge'] ? 'Y' : 'N',
                        (float)$r['gst_rate'] . '%',
                        (float)$r['taxable_value'],
                        (float)$r['cgst_amount'],
                        (float)$r['sgst_amount'],
                        (float)$r['igst_amount'],
                    ];
                }
                $reportTitle = 'GSTR-1 Table 4 B2B Invoices';
            } else {
                $headers = ['Invoice #', 'Date', 'Customer Name', 'GSTIN', 'Place of Supply', 'Reverse Charge', 'HSN/SAC', 'Description', 'Qty', 'Unit', 'Taxable Value', 'GST Rate %', 'CGST', 'SGST', 'IGST', 'Cess', 'Line Total'];
                $data = [];
                $exportRows = ($section === 'b2c') ? array_merge($b2cLargeRows, $b2cSmallRows) : $rows;
                foreach ($exportRows as $r) {
                    $data[] = [
                        $r['invoice_number'],
                        $r['issue_date'],
                        $r['customer_name'],
                        $r['customer_gstin'] ?: 'Unregistered',
                        ($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') . $r['place_of_supply_state'],
                        $r['is_reverse_charge'] ? 'Yes' : 'No',
                        $r['hsn_sac'],
                        $r['item_name'],
                        (float)$r['quantity'],
                        $r['unit'],
                        (float)$r['taxable_value'],
                        (float)$r['gst_rate'] . '%',
                        (float)$r['cgst_amount'],
                        (float)$r['sgst_amount'],
                        (float)$r['igst_amount'],
                        (float)$r['cess_amount'],
                        (float)$r['line_total'],
                    ];
                }
                $reportTitle = 'GST Sales Register';
            }

            $filename = "gstr1-{$section}-{$startDate}-to-{$endDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml($reportTitle, $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        return new Response(View::render('reports.gst-sales', [
            'title' => 'GST Sales Register (GSTR-1 Ready)',
            'rows' => $rows,
            'b2bRows' => $b2bRows,
            'b2cLargeRows' => $b2cLargeRows,
            'b2cSmallRows' => $b2cSmallRows,
            'nilExemptRows' => $nilExemptRows,
            'hsnSummary' => $hsnSummary,
            'section' => $section,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]));
    }

    public function payments(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $method = $request->get('method', '');
        $export = $request->get('export', '');

        $where = ["p.business_id = ?", "p.payment_date BETWEEN ? AND ?"];
        $params = [$businessId, $startDate, $endDate];

        if ($method !== '') {
            $where[] = "p.payment_method = ?";
            $params[] = $method;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE {$whereSql}
            ORDER BY p.payment_date ASC, p.id ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totReceived = 0.0;
        $totAllocated = 0.0;
        $totUnallocated = 0.0;

        foreach ($rows as $r) {
            if ($r['status'] === 'posted') {
                $totReceived += (float)$r['amount'];
                $totAllocated += (float)$r['allocated_amount'];
                $totUnallocated += (float)$r['unallocated_amount'];
            }
        }

        if ($export === 'csv' || $export === 'excel') {
            AuditService::log('report.exported', 'report', null, ['type' => 'payments', 'format' => $export]);
            $headers = ['Receipt #', 'Date', 'Customer', 'Method', 'Reference #', 'Amount Received', 'Allocated', 'Unallocated', 'Status'];
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    $r['payment_number'],
                    $r['payment_date'],
                    $r['customer_name'],
                    strtoupper(str_replace('_', ' ', $r['payment_method'])),
                    $r['reference_number'] ?: '-',
                    (float)$r['amount'],
                    (float)$r['allocated_amount'],
                    (float)$r['unallocated_amount'],
                    ucfirst($r['status']),
                ];
            }
            $data[] = ['TOTAL', '', '', '', '', $totReceived, $totAllocated, $totUnallocated, ''];

            $filename = "payment-collections-{$startDate}-to-{$endDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml('Collections Report', $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        $paymentMethods = Config::get('constants.payment_methods', []);

        return new Response(View::render('reports.payments', [
            'title' => 'Payments & Collections Report',
            'rows' => $rows,
            'paymentMethods' => $paymentMethods,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'method' => $method,
            'totals' => [
                'received' => $totReceived,
                'allocated' => $totAllocated,
                'unallocated' => $totUnallocated,
            ],
        ]));
    }

    public function receivables(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $asOfDate = $request->get('as_of_date', date('Y-m-d'));
        $customerId = $request->get('customer_id', '');
        $export = $request->get('export', '');

        $where = ["i.business_id = ?", "i.status IN ('issued', 'partially_paid')"];
        $params = [$businessId];

        if ($customerId !== '') {
            $where[] = "i.customer_id = ?";
            $params[] = (int)$customerId;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name, c.phone as customer_phone,
                   DATEDIFF(?, i.due_date) as days_overdue
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE {$whereSql}
            ORDER BY days_overdue DESC, i.due_date ASC
        ");
        $stmt->execute(array_merge([$asOfDate], $params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ageing Buckets
        $buckets = [
            'not_due' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
            'total' => 0.0,
        ];

        foreach ($rows as &$r) {
            $days = (int)$r['days_overdue'];
            $bal = (float)$r['balance_due'];
            $buckets['total'] += $bal;

            if ($days <= 0) {
                $r['bucket'] = 'Not Yet Due';
                $buckets['not_due'] += $bal;
            } elseif ($days <= 30) {
                $r['bucket'] = '1–30 Days';
                $buckets['1_30'] += $bal;
            } elseif ($days <= 60) {
                $r['bucket'] = '31–60 Days';
                $buckets['31_60'] += $bal;
            } elseif ($days <= 90) {
                $r['bucket'] = '61–90 Days';
                $buckets['61_90'] += $bal;
            } else {
                $r['bucket'] = '90+ Days';
                $buckets['90_plus'] += $bal;
            }
        }
        unset($r);

        if ($export === 'csv' || $export === 'excel') {
            AuditService::log('report.exported', 'report', null, ['type' => 'receivables', 'format' => $export]);
            $headers = ['Customer', 'Invoice #', 'Issue Date', 'Due Date', 'Days Overdue', 'Ageing Bucket', 'Invoice Amount', 'Paid Amount', 'Balance Due'];
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    $r['customer_name'],
                    $r['invoice_number'],
                    $r['issue_date'],
                    $r['due_date'],
                    max(0, (int)$r['days_overdue']),
                    $r['bucket'],
                    (float)$r['total_amount'],
                    (float)$r['paid_amount'],
                    (float)$r['balance_due'],
                ];
            }
            $data[] = ['TOTAL', '', '', '', '', '', '', '', $buckets['total']];

            $filename = "outstanding-receivables-{$asOfDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml('Outstanding Receivables', $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        $cStmt = $pdo->prepare("SELECT id, name FROM customers WHERE business_id = ? ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('reports.receivables', [
            'title' => 'Outstanding Receivables & Ageing Analysis',
            'rows' => $rows,
            'buckets' => $buckets,
            'asOfDate' => $asOfDate,
            'customerId' => $customerId,
            'customers' => $customers,
        ]));
    }

    public function customerStatement(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $customerId = (int)$request->get('customer_id', 0);
        $startDate = $request->get('start_date', date('Y-01-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $export = $request->get('export', '');

        $cStmt = $pdo->prepare("SELECT id, name FROM customers WHERE business_id = ? ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $selectedCustomer = null;
        $ledger = [];
        $runningBalance = 0.0;

        if ($customerId > 0) {
            $custStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
            $custStmt->execute([$customerId, $businessId]);
            $selectedCustomer = $custStmt->fetch(PDO::FETCH_ASSOC);

            if ($selectedCustomer) {
                // Pre-period Opening Balance calculation
                $priorInvStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) 
                    FROM invoices 
                    WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'partially_paid', 'paid')
                      AND issue_date < ?
                ");
                $priorInvStmt->execute([$businessId, $customerId, $startDate]);
                $priorInvoiced = (float)$priorInvStmt->fetchColumn();

                $priorPayStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM payments 
                    WHERE business_id = ? AND customer_id = ? AND status = 'posted'
                      AND payment_date < ?
                ");
                $priorPayStmt->execute([$businessId, $customerId, $startDate]);
                $priorPaid = (float)$priorPayStmt->fetchColumn();

                $priorCnStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM credit_notes
                    WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'applied')
                      AND credit_note_date < ?
                ");
                $priorCnStmt->execute([$businessId, $customerId, $startDate]);
                $priorCreditNotes = (float)$priorCnStmt->fetchColumn();

                $openingBalance = round($priorInvoiced - $priorPaid - $priorCreditNotes, 2);
                $runningBalance = $openingBalance;

                $openingRow = [
                    'type' => 'opening_balance',
                    'date' => $startDate,
                    'ref_no' => 'OPENING',
                    'description' => 'Opening Balance as of ' . date('d M Y', strtotime($startDate)),
                    'debit' => ($openingBalance > 0) ? $openingBalance : 0.00,
                    'credit' => ($openingBalance < 0) ? abs($openingBalance) : 0.00,
                    'balance' => $runningBalance,
                ];

                // Invoices in period
                $invStmt = $pdo->prepare("
                    SELECT 'invoice' as type, issue_date as date, invoice_number as ref_no, 
                           total_amount as debit, 0.00 as credit, 'Sales Invoice' as description
                    FROM invoices 
                    WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'partially_paid', 'paid')
                      AND issue_date BETWEEN ? AND ?
                ");
                $invStmt->execute([$businessId, $customerId, $startDate, $endDate]);
                $invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

                // Payments in period
                $payStmt = $pdo->prepare("
                    SELECT 'payment' as type, payment_date as date, payment_number as ref_no, 
                           0.00 as debit, amount as credit, CONCAT('Payment Receipt (', UPPER(payment_method), ')') as description
                    FROM payments 
                    WHERE business_id = ? AND customer_id = ? AND status = 'posted'
                      AND payment_date BETWEEN ? AND ?
                ");
                $payStmt->execute([$businessId, $customerId, $startDate, $endDate]);
                $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

                // Credit Notes in period
                $cnStmt = $pdo->prepare("
                    SELECT 'credit_note' as type, credit_note_date as date, credit_note_number as ref_no,
                           0.00 as debit, total_amount as credit, CONCAT('Credit Note (', UPPER(reason), ')') as description
                    FROM credit_notes 
                    WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'applied')
                      AND credit_note_date BETWEEN ? AND ?
                ");
                $cnStmt->execute([$businessId, $customerId, $startDate, $endDate]);
                $creditNotes = $cnStmt->fetchAll(PDO::FETCH_ASSOC);

                // Merge and sort chronologically
                $periodTransactions = array_merge($invoices, $payments, $creditNotes);
                usort($periodTransactions, fn($a, $b) => strcmp($a['date'], $b['date']));

                foreach ($periodTransactions as &$item) {
                    $runningBalance = round($runningBalance + (float)$item['debit'] - (float)$item['credit'], 2);
                    $item['balance'] = $runningBalance;
                }
                unset($item);

                $ledger = array_merge([$openingRow], $periodTransactions);
            }
        }

        if (($export === 'csv' || $export === 'excel') && $selectedCustomer) {
            AuditService::log('report.exported', 'report', null, ['type' => 'customer_statement', 'customer_id' => $customerId]);
            $headers = ['Date', 'Type', 'Reference #', 'Description', 'Debit (Invoice)', 'Credit (Payment)', 'Running Balance'];
            $data = [];
            foreach ($ledger as $l) {
                $data[] = [
                    $l['date'],
                    ucfirst($l['type']),
                    $l['ref_no'],
                    $l['description'],
                    (float)$l['debit'],
                    (float)$l['credit'],
                    (float)$l['balance'],
                ];
            }

            $filename = "statement-" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $selectedCustomer['name']) . "-{$startDate}-to-{$endDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml('Customer Statement', $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        return new Response(View::render('reports.customer-statement', [
            'title' => 'Customer Account Statement',
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'customerId' => $customerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'ledger' => $ledger,
            'openingBalance' => $openingBalance ?? 0.00,
            'closingBalance' => $runningBalance,
        ]));
    }

    public function taxSummary(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $export = $request->get('export', '');

        // Group by tax rate
        $stmt = $pdo->prepare("
            SELECT 
                ii.gst_rate,
                COUNT(DISTINCT i.id) as invoice_count,
                SUM(ii.taxable_value) as total_taxable,
                SUM(ii.cgst_amount) as total_cgst,
                SUM(ii.sgst_amount) as total_sgst,
                SUM(ii.igst_amount) as total_igst,
                SUM(ii.cess_amount) as total_cess,
                SUM(ii.total_tax) as total_tax,
                SUM(ii.line_total) as total_amount
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            WHERE i.business_id = ? 
              AND i.status IN ('issued', 'partially_paid', 'paid')
              AND i.issue_date BETWEEN ? AND ?
            GROUP BY ii.gst_rate
            ORDER BY ii.gst_rate ASC
        ");
        $stmt->execute([$businessId, $startDate, $endDate]);
        $rawSlabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also incorporate any freight / additional charges with GST so tax summary matches invoice totals exactly
        $invStmt = $pdo->prepare("
            SELECT 
                i.id as invoice_id, i.invoice_number, i.issue_date,
                i.additional_charges,
                i.cgst_amount as inv_cgst, i.sgst_amount as inv_sgst, i.igst_amount as inv_igst,
                COALESCE(SUM(ii.cgst_amount), 0) as items_cgst,
                COALESCE(SUM(ii.sgst_amount), 0) as items_sgst,
                COALESCE(SUM(ii.igst_amount), 0) as items_igst
            FROM invoices i
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            WHERE i.business_id = ? 
              AND i.status IN ('issued', 'partially_paid', 'paid')
              AND i.issue_date BETWEEN ? AND ?
              AND i.additional_charges > 0
            GROUP BY i.id
        ");
        $invStmt->execute([$businessId, $startDate, $endDate]);
        $freightInvoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

        $slabMap = [];
        foreach ($rawSlabs as $r) {
            $rateKey = (string)(float)$r['gst_rate'];
            $slabMap[$rateKey] = [
                'gst_rate' => (float)$r['gst_rate'],
                'invoice_count' => (int)$r['invoice_count'],
                'total_taxable' => (float)$r['total_taxable'],
                'total_cgst' => (float)$r['total_cgst'],
                'total_sgst' => (float)$r['total_sgst'],
                'total_igst' => (float)$r['total_igst'],
                'total_cess' => (float)$r['total_cess'],
                'total_tax' => (float)$r['total_tax'],
                'total_amount' => (float)$r['total_amount'],
            ];
        }

        foreach ($freightInvoices as $fi) {
            $addTaxable = (float)$fi['additional_charges'];
            $addCgst = max(0.0, (float)$fi['inv_cgst'] - (float)$fi['items_cgst']);
            $addSgst = max(0.0, (float)$fi['inv_sgst'] - (float)$fi['items_sgst']);
            $addIgst = max(0.0, (float)$fi['inv_igst'] - (float)$fi['items_igst']);
            $addTax = $addCgst + $addSgst + $addIgst;

            $freightRate = ($addTaxable > 0 && $addTax > 0) ? round(($addTax / $addTaxable) * 100, 1) : 0.0;
            $rateKey = (string)$freightRate;

            if (!isset($slabMap[$rateKey])) {
                $slabMap[$rateKey] = [
                    'gst_rate' => $freightRate,
                    'invoice_count' => 0,
                    'total_taxable' => 0.0,
                    'total_cgst' => 0.0,
                    'total_sgst' => 0.0,
                    'total_igst' => 0.0,
                    'total_cess' => 0.0,
                    'total_tax' => 0.0,
                    'total_amount' => 0.0,
                ];
            }
            $slabMap[$rateKey]['total_taxable'] += $addTaxable;
            $slabMap[$rateKey]['total_cgst'] += $addCgst;
            $slabMap[$rateKey]['total_sgst'] += $addSgst;
            $slabMap[$rateKey]['total_igst'] += $addIgst;
            $slabMap[$rateKey]['total_tax'] += $addTax;
            $slabMap[$rateKey]['total_amount'] += round($addTaxable + $addTax, 2);
        }
        ksort($slabMap, SORT_NUMERIC);
        $rows = array_values($slabMap);

        if ($export === 'csv' || $export === 'excel') {
            AuditService::log('report.exported', 'report', null, ['type' => 'tax_summary', 'format' => $export]);
            $headers = ['GST Rate %', 'Invoices Count', 'Taxable Turnover', 'CGST', 'SGST', 'IGST', 'Cess', 'Total Tax', 'Total Value'];
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    (float)$r['gst_rate'] . '%',
                    (int)$r['invoice_count'],
                    (float)$r['total_taxable'],
                    (float)$r['total_cgst'],
                    (float)$r['total_sgst'],
                    (float)$r['total_igst'],
                    (float)$r['total_cess'],
                    (float)$r['total_tax'],
                    (float)$r['total_amount'],
                ];
            }

            $filename = "gst-tax-summary-{$startDate}-to-{$endDate}." . ($export === 'excel' ? 'xls' : 'csv');
            $content = ($export === 'excel')
                ? ExportService::toExcelXml('Tax Summary', $headers, $data)
                : ExportService::toCsv($headers, $data);

            $mime = ($export === 'excel') ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';
            return Response::download($content, $filename, $mime);
        }

        return new Response(View::render('reports.tax-summary', [
            'title' => 'GST Tax Liability Summary',
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]));
    }
}
