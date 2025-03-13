<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Get the authenticated user
        // $user = Auth::user();
        // dd($role);
        // // Check if the user has the specified role
        // if (!$user || !$user->hasRole($role)) {
        //     // Return an unauthorized response if the user doesn't have the required role
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        // Assuming the user has a 'role' attribute
        // dd($request->user()->role, $role);
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
