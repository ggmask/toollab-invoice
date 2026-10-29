<?php

namespace App\Middleware;

use App\Support\Csrf;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            $token = $request->post('_token') ?? $request->header('X-CSRF-TOKEN');
            if (!Csrf::validate($token)) {
                if ($request->isAjax()) {
                    return Response::json(['error' => 'CSRF token mismatch. Please reload the page.'], 419);
                }
                Session::flash('error', 'Your session expired or invalid security token. Please try again.');
                return Response::redirect($request->baseUrl() . $request->path());
            }
        }
        return null;
    }
}
