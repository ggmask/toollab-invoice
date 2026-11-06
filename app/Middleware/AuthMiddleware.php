<?php

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\Database;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            Session::flash('error', 'Please sign in to access your business workspace.');
            return Response::redirect(url('/login'));
        }

        // Verify active business is loaded
        $business = Session::get('business');
        if (!$business) {
            // Attempt to load default business for this user
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT b.*, bm.role 
                FROM businesses b
                INNER JOIN business_members bm ON bm.business_id = b.id
                WHERE bm.user_id = ? AND bm.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $business = $stmt->fetch();

            if ($business) {
                Session::set('business', $business);
            } else {
                Session::remove('user');
                Session::remove('business');
                Session::flash('error', 'No active business workspace found for your account. Please log in again or contact your administrator.');
                return Response::redirect(url('/login'));
            }
        }

        return null;
    }
}
