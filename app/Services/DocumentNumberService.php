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

        return self::formatNumber($prefix, $fy, $nextNumber, $padding);
    }

    /**
     * Format a document number respecting CGST Rule 46 (strictly <= 16 characters).
     */
    public static function formatNumber(
        string $prefix,
        string $fy,
        int $number,
        int $padding = 4,
        string $separator = '-'
    ): string {
        $shortFy = (strlen($fy) === 7 && str_contains($fy, '-'))
            ? substr($fy, 2, 2) . '-' . substr($fy, 5, 2)
            : $fy;

        $padded = str_pad((string)$number, $padding, '0', STR_PAD_LEFT);

        // Try standard 4-digit FY: e.g. INV-2026-27-0001 (16 chars)
        $candidate = sprintf('%s%s%s%s%s', $prefix, $separator, $fy, $separator, $padded);
        if (strlen($candidate) <= 16) {
            return $candidate;
        }

        // Try short 2-digit FY: e.g. TOOL-26-27-0001 (15 chars)
        $candidateShort = sprintf('%s%s%s%s%s', $prefix, $separator, $shortFy, $separator, $padded);
        if (strlen($candidateShort) <= 16) {
            return $candidateShort;
        }

        // If prefix is long, format without FY to strictly guarantee <= 16 characters per Rule 46
        return substr(sprintf('%s%s%s', $prefix, $separator, $padded), 0, 16);
    }

    /**
     * Generate a unique sequential draft number.
     */
    public static function generateDraftNumber(
        int $businessId,
        int $padding = 4,
        ?string $financialYear = null
    ): string {
        return self::generateNextNumber($businessId, 'draft', 'DFT', $padding, $financialYear);
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
        return self::formatNumber($prefix, $fy, $nextNumber, $padding);
    }
}
