<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasDriverProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->driverProfile ||  $request->user()->driverProfile->status_verification !== 'approved') {
            return response()->json(['message' => 'Vous devez avoir un profil conducteur pour effectuer cette action'], 403); 
        }
        return $next($request);
    }
}
