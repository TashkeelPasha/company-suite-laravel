<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureMobileRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $role = match (true) {
            $user instanceof \App\Models\SuperAdmin => 'superadmin',
            $user instanceof \App\Models\Company => 'company',
            $user instanceof \App\Models\TeamMember => match ($user->team_type) {
                \App\Models\TeamMember::TYPE_STATION, \App\Models\TeamMember::TYPE_SAT => 'station',
                \App\Models\TeamMember::TYPE_GO => 'go',
                \App\Models\TeamMember::TYPE_ERC => 'erc',
                default => null,
            },
            default => null,
        };

        if ($role && in_array($role, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
