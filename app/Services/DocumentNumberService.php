<?php

namespace App\Services;

use App\Support\Database;
use App\Support\FinancialYear;
use PDO;

class DocumentNumberService
{
    /**
     * Atomically get and increment the next document number within a transaction.
     */
    public static function generateNextNumber(
        int $businessId,
        string $documentType, // 'invoice', 'estimate', 'payment'
        string $prefix = 'INV',
        int $padding = 4,
        ?string $financialYear = null
    ): string {
        $fy = $financialYear ?: FinancialYear::current();
        $pdo = Database::pdo();

        // Lock sequence row or insert if not present
        $stmt = $pdo->prepare("
            SELECT id, last_number 
            FROM document_sequences 
            WHERE business_id = ? AND document_type = ? AND financial_year = ?
            FOR UPDATE
        ");
        $stmt->execute([$businessId, $documentType, $fy]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $nextNumber = ((int)$row['last_number']) + 1;
            $update = $pdo->prepare("
                UPDATE document_sequences 
                SET last_number = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([$nextNumber, $row['id']]);
        } else {
            $nextNumber = 1;
            $insert = $pdo->prepare("
                INSERT INTO document_sequences 
                (business_id, document_type, financial_year, last_number, updated_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert->execute([$businessId, $documentType, $fy, $nextNumber]);
        }

        $padded = str_pad((string)$nextNumber, $padding, '0', STR_PAD_LEFT);
        return sprintf('%s-%s-%s', $prefix, $fy, $padded);
    }

    /**
     * Preview the next document number without incrementing the counter.
     */
    public static function previewNextNumber(
        int $businessId,
        string $documentType,
        string $prefix = 'INV',
        int $padding = 4,
        ?string $financialYear = null
    ): string {
        $fy = $financialYear ?: FinancialYear::current();
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT last_number 
            FROM document_sequences 
            WHERE business_id = ? AND document_type = ? AND financial_year = ?
        ");
        $stmt->execute([$businessId, $documentType, $fy]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $nextNumber = $row ? ((int)$row['last_number']) + 1 : 1;
        $padded = str_pad((string)$nextNumber, $padding, '0', STR_PAD_LEFT);
        return sprintf('%s-%s-%s', $prefix, $fy, $padded);
    }
}
