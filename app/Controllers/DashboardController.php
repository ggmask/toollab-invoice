<?php

namespace App\Controllers;

use App\Support\Database;
use App\Support\FinancialYear;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class DashboardController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        // Date Filter
        $period = $request->get('period', 'this_month');
        $today = date('Y-m-d');

        switch ($period) {
            case 'today':
                $startDate = $today;
                $endDate = $today;
                break;
            case 'last_month':
                $startDate = date('Y-m-01', strtotime('first day of last month'));
                $endDate = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'this_fy':
                [$startDate, $endDate] = FinancialYear::dateRange(FinancialYear::current());
                break;
            case 'all':
                $startDate = '2000-01-01';
                $endDate = '2099-12-31';
                break;
            case 'this_month':
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $period = 'this_month';
                break;
        }

        // 1. Total Invoiced in Period (excluding draft and void)
        $invStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as total_invoiced,
                COALESCE(SUM(taxable_value), 0) as total_taxable,
                COALESCE(SUM(tax_total), 0) as total_tax
            FROM invoices
            WHERE business_id = ? 
              AND status IN ('issued', 'partially_paid', 'paid')
              AND issue_date BETWEEN ? AND ?
        ");
        $invStmt->execute([$businessId, $startDate, $endDate]);
        $invoiceStats = $invStmt->fetch(PDO::FETCH_ASSOC);

        // 2. Payments Received in Period (posted status only, not reversed)
        $payStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total_received
            FROM payments
            WHERE business_id = ? 
              AND status = 'posted'
              AND payment_date BETWEEN ? AND ?
        ");
        $payStmt->execute([$businessId, $startDate, $endDate]);
        $paymentStats = $payStmt->fetch(PDO::FETCH_ASSOC);

        // 3. Outstanding Receivables as of Today (overall active balance due)
        $recStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as unpaid_count,
                COALESCE(SUM(balance_due), 0) as total_outstanding
            FROM invoices
            WHERE business_id = ? 
              AND status IN ('issued', 'partially_paid')
        ");
        $recStmt->execute([$businessId]);
        $receivableStats = $recStmt->fetch(PDO::FETCH_ASSOC);

        // 4. Overdue Amount as of Today
        $overdueStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as overdue_count,
                COALESCE(SUM(balance_due), 0) as overdue_amount
            FROM invoices
            WHERE business_id = ? 
              AND status IN ('issued', 'partially_paid')
              AND due_date < ?
        ");
        $overdueStmt->execute([$businessId, $today]);
        $overdueStats = $overdueStmt->fetch(PDO::FETCH_ASSOC);

        // 5. Recent Invoices
        $recentStmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.business_id = ?
            ORDER BY i.created_at DESC
            LIMIT 8
        ");
        $recentStmt->execute([$businessId]);
        $recentInvoices = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Overdue / Due Soon Invoices requiring attention
        $attentionStmt = $pdo->prepare("
            SELECT i.*, c.name as customer_name,
                   DATEDIFF(?, i.due_date) as days_overdue
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.business_id = ? 
              AND i.status IN ('issued', 'partially_paid')
            ORDER BY i.due_date ASC
            LIMIT 5
        ");
        $attentionStmt->execute([$today, $businessId]);
        $attentionInvoices = $attentionStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('dashboard.index', [
            'title' => 'Dashboard Overview',
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'invoiceStats' => $invoiceStats,
            'paymentStats' => $paymentStats,
            'receivableStats' => $receivableStats,
            'overdueStats' => $overdueStats,
            'recentInvoices' => $recentInvoices,
            'attentionInvoices' => $attentionInvoices,
        ]));
    }
}
