<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();
            // Update last_seen_at if it's null or more than 1 minute ago
            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) >= 1) {
                $user->last_seen_at = now();
                $user->save();
            }
        }
        
        return $next($request);
    }
}
