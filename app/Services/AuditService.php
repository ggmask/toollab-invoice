<?php

namespace App\Services;

use App\Support\Database;
use App\Support\Session;

class AuditService
{
    public static function log(string $action, string $targetType, ?int $targetId = null, array $details = []): void
    {
        try {
            $user = Session::get('user');
            $business = Session::get('business');
            $userId = $user ? (int)$user['id'] : null;
            $businessId = $business ? (int)$business['id'] : null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs 
                (business_id, user_id, action, target_type, target_id, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $businessId,
                $userId,
                $action,
                $targetType,
                $targetId,
                !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                $ip,
            ]);
        } catch (\Throwable $e) {
            // Audit failures must not break application flow, but can be logged
            error_log('Audit logging failed: ' . $e->getMessage());
        }
    }
}
