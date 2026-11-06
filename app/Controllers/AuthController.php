<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        return new Response(View::render('auth.login', ['title' => 'Sign In'], 'auth'));
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim($request->post('email', '')));
        $password = (string)$request->post('password', '');

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Please enter your email and password.');
            return Response::redirect(url('/login'));
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Invalid email or password.');
            return Response::redirect(url('/login'));
        }

        // Authentication success
        Session::regenerate(true);
        unset($user['password_hash']);
        Session::set('user', $user);

        // Load primary business
        $bStmt = $pdo->prepare("
            SELECT b.*, bm.role 
            FROM businesses b
            INNER JOIN business_members bm ON bm.business_id = b.id
            WHERE bm.user_id = ? AND bm.is_active = 1
            LIMIT 1
        ");
        $bStmt->execute([$user['id']]);
        $business = $bStmt->fetch(PDO::FETCH_ASSOC);

        if ($business) {
            Session::set('business', $business);
        }

        AuditService::log('user.login', 'user', (int)$user['id'], ['email' => $email]);
        Session::flash('success', 'Welcome back, ' . htmlspecialchars($user['name']));

        return Response::redirect(url('/dashboard'));
    }

    public function showRegister(Request $request): Response
    {
        return new Response(View::render('auth.register', ['title' => 'Create Account'], 'auth'));
    }

    public function register(Request $request): Response
    {
        $name = trim($request->post('name', ''));
        $email = strtolower(trim($request->post('email', '')));
        $businessName = trim($request->post('business_name', ''));
        $password = (string)$request->post('password', '');
        $passwordConfirm = (string)$request->post('password_confirmation', '');

        $errors = [];
        if (strlen($name) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($businessName)) {
            $errors[] = 'Business or firm name is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            return Response::redirect(url('/register'));
        }

        $pdo = Database::pdo();
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            Session::flash('error', 'An account with this email address already exists.');
            return Response::redirect(url('/register'));
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Transaction to create user + business workspace + default tax rates
        try {
            Database::transaction(function (PDO $pdo) use ($name, $email, $passwordHash, $businessName) {
                // 1. Create User
                $uStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, status, created_at, updated_at)
                    VALUES (?, ?, ?, 'active', NOW(), NOW())
                ");
                $uStmt->execute([$name, $email, $passwordHash]);
                $userId = (int)$pdo->lastInsertId();

                // 2. Create Business
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $bStmt = $pdo->prepare("
                    INSERT INTO businesses 
                    (uuid, legal_name, trade_name, business_type, gst_status, currency, currency_symbol, invoice_prefix, estimate_prefix, payment_prefix, number_padding, default_payment_terms, default_due_days, created_at, updated_at)
                    VALUES (?, ?, ?, 'Proprietorship', 'registered_regular', 'INR', '₹', 'INV', 'EST', 'PAY', 4, 'Net 15', 15, NOW(), NOW())
                ");
                $bStmt->execute([$uuid, $businessName, $businessName]);
                $businessId = (int)$pdo->lastInsertId();

                // 3. Link Membership
                $mStmt = $pdo->prepare("
                    INSERT INTO business_members (business_id, user_id, role, is_active, created_at)
                    VALUES (?, ?, 'owner', 1, NOW())
                ");
                $mStmt->execute([$businessId, $userId]);

                // 4. Seed Default GST Tax Rates for this business
                $defaultRates = [
                    ['name' => 'GST 0% (Nil / Exempt)', 'rate' => 0.00, 'category' => 'exempt'],
                    ['name' => 'GST 5%', 'rate' => 5.00, 'category' => 'taxable'],
                    ['name' => 'GST 12%', 'rate' => 12.00, 'category' => 'taxable'],
                    ['name' => 'GST 18%', 'rate' => 18.00, 'category' => 'taxable'],
                    ['name' => 'GST 28%', 'rate' => 28.00, 'category' => 'taxable'],
                ];
                $trStmt = $pdo->prepare("
                    INSERT INTO tax_rates (business_id, name, rate, tax_category, is_active, created_at)
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                foreach ($defaultRates as $r) {
                    $trStmt->execute([$businessId, $r['name'], $r['rate'], $r['category']]);
                }

                // 5. Set session
                Session::regenerate(true);
                Session::set('user', [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'status' => 'active',
                ]);

                $bFetchStmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
                $bFetchStmt->execute([$businessId]);
                $bFetch = $bFetchStmt->fetch(PDO::FETCH_ASSOC);
                $bFetch['role'] = 'owner';
                Session::set('business', $bFetch);

                AuditService::log('user.register', 'user', $userId, ['email' => $email, 'business' => $businessName]);
            });

            Session::flash('success', 'Account and business workspace created successfully!');
            return Response::redirect(url('/dashboard'));
        } catch (\Throwable $e) {
            Session::flash('error', 'Registration failed: ' . $e->getMessage());
            return Response::redirect(url('/register'));
        }
    }

    public function logout(Request $request): Response
    {
        $user = Session::get('user');
        if ($user) {
            AuditService::log('user.logout', 'user', (int)$user['id']);
        }
        Session::destroy();
        return Response::redirect(url('/login'));
    }

    public function showForgotPassword(Request $request): Response
    {
        return new Response(View::render('auth.forgot-password', ['title' => 'Reset Password'], 'auth'));
    }

    public function sendResetLink(Request $request): Response
    {
        $email = strtolower(trim($request->post('email', '')));
        // Always show generic message to avoid email enumeration
        Session::flash('success', 'If an account exists for ' . htmlspecialchars($email) . ', password reset instructions have been sent.');
        return Response::redirect(url('/login'));
    }
}
