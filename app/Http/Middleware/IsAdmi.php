<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Assurez-vous que l'utilisateur est authentifié via le guard 'api'
        if (!Auth::guard('api')->check()) {
             return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::guard('api')->user();

        // Vérifiez si l'utilisateur a le rôle admin (ajoutez la colonne 'is_admin' à la table users)
        if ($user && $user->is_admin) { // Assurez-vous que la propriété/colonne existe
            return $next($request);
        }

        // Si non admin, retourner une erreur 403 Forbidden
        return response()->json(['message' => 'Forbidden. Administrator access required.'], 403);
    }
}