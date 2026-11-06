<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Support\Database;
use App\Support\FinancialYear;
use App\Services\DocumentNumberService;

final class CreditNoteTest extends TestCase
{
    private \PDO $pdo;
    private int $businessId = 1;
    private ?int $createdCnId = null;
    private ?int $testInvoiceId = null;
    private float $initialBalance = 0.0;

    protected function setUp(): void
    {
        $this->pdo = Database::pdo();

        // Find or create an invoice with a positive balance due
        $stmt = $this->pdo->prepare("SELECT id, balance_due FROM invoices WHERE business_id = ? AND status IN ('issued', 'partially_paid') AND balance_due > 0 LIMIT 1");
        $stmt->execute([$this->businessId]);
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($inv) {
            $this->testInvoiceId = (int)$inv['id'];
            $this->initialBalance = (float)$inv['balance_due'];
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdCnId) {
            $this->pdo->prepare("DELETE FROM credit_note_items WHERE credit_note_id = ?")->execute([$this->createdCnId]);
            $this->pdo->prepare("DELETE FROM credit_notes WHERE id = ?")->execute([$this->createdCnId]);
        }

        if ($this->testInvoiceId && $this->initialBalance > 0) {
            $this->pdo->prepare("UPDATE invoices SET balance_due = ? WHERE id = ?")->execute([$this->initialBalance, $this->testInvoiceId]);
        }
    }

    public function testCreditNoteCreationAndApplication(): void
    {
        if (!$this->testInvoiceId) {
            $this->markTestSkipped('No invoice with balance due found to test');
        }

        $fy = FinancialYear::current();
        $cnNumber = DocumentNumberService::generateNextNumber($this->businessId, 'credit_note', 'CN', 4, $fy);
        $this->assertLessThanOrEqual(16, strlen($cnNumber));

        $creditAmount = 150.00;
        $uuid = 'test-cn-phpunit-' . bin2hex(random_bytes(4));

        $ins = $this->pdo->prepare("
            INSERT INTO credit_notes (
                business_id, customer_id, invoice_id, uuid, credit_note_number, financial_year,
                credit_note_date, original_invoice_number, reason, status, supply_type,
                taxable_value, total_amount, applied_amount, unapplied_amount, created_by_user_id, created_at
            ) VALUES (
                ?, 1, ?, ?, ?, ?,
                CURDATE(), 'TEST-INV', 'sales_return', 'applied', 'intra_state',
                ?, ?, ?, 0.00, 1, NOW()
            )
        ");
        $ins->execute([
            $this->businessId, $this->testInvoiceId, $uuid, $cnNumber, $fy,
            $creditAmount, $creditAmount, $creditAmount
        ]);
        $this->createdCnId = (int)$this->pdo->lastInsertId();

        // Apply deduction to invoice
        $this->pdo->prepare("UPDATE invoices SET balance_due = balance_due - ? WHERE id = ?")
                  ->execute([$creditAmount, $this->testInvoiceId]);

        $check = $this->pdo->query("SELECT balance_due FROM invoices WHERE id = {$this->testInvoiceId}")->fetchColumn();
        $this->assertEquals(round($this->initialBalance - $creditAmount, 2), round((float)$check, 2));
    }
}

