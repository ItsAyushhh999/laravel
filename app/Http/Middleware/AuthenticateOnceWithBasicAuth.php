<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOnceWithBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Attempts HTTP Basic Auth once (no session)
        return Auth::onceBasic() ?: $next($request);
    }
}
