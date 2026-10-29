<?php

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

class GuestMiddleware
{
    public function handle(Request $request): ?Response
    {
        Session::start();
        if (Session::get('user')) {
            return Response::redirect(url('/dashboard'));
        }
        return null;
    }
}
