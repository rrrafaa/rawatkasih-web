<?php

namespace App\Http\Middleware;

use App\Models\SupabaseUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user instanceof SupabaseUser && $user->role()->value === $role, 403);

        return $next($request);
    }
}
