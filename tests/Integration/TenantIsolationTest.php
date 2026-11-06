<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Support\Database;

final class TenantIsolationTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::pdo();
    }

    public function testInvoiceQueryTenantIsolation(): void
    {
        // Pick an existing invoice from business 1
        $stmt = $this->pdo->query("SELECT id, invoice_number, business_id FROM invoices LIMIT 1");
        $inv = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($inv, 'Must have at least 1 invoice to test');

        $realBizId = (int)$inv['business_id'];
        $wrongBizId = $realBizId + 9999;

        // Query with real business id
        $scopedStmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $scopedStmt->execute([$inv['id'], $realBizId]);
        $found = $scopedStmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($found, 'Invoice must be found under its own business');

        // Query with wrong business id
        $blockedStmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $blockedStmt->execute([$inv['id'], $wrongBizId]);
        $blocked = $blockedStmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertFalse($blocked, 'Invoice must NOT be accessible under a foreign business_id');
    }

    public function testPaymentQueryTenantIsolation(): void
    {
        $stmt = $this->pdo->query("SELECT id, business_id FROM payments LIMIT 1");
        $pay = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pay) {
            $this->markTestSkipped('No payments found to test');
        }

        $realBizId = (int)$pay['business_id'];
        $wrongBizId = $realBizId + 9999;

        $blockedStmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ? AND business_id = ?");
        $blockedStmt->execute([$pay['id'], $wrongBizId]);
        $blocked = $blockedStmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertFalse($blocked, 'Payment record must NOT be accessible under a foreign business_id');
    }
}

