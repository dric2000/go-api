<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanCreateTrip
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->driverProfile || $request->user()->driverProfile->vehicles->isEmpty()) {
            return response()->json([
                'message' => "Vous devez avoir un profil conducteur et au moins un véhicule pour créer un trajet."
            ], 403);
        }
        return $next($request);
    }
}
