<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Acesso não autorizado. Apenas administradores podem acessar esta área.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
