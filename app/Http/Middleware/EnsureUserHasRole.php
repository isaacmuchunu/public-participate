<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            throw new AccessDeniedHttpException;
        }

        $userRole = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        $resolvedRoles = collect($roles)
            ->flatMap(function (string $role) {
                return match ($role) {
                    'citizen' => [UserRole::Citizen],
                    'mp' => [UserRole::Mp],
                    'senator' => [UserRole::Senator],
                    'legislator' => [UserRole::Mp, UserRole::Senator],
                    'clerk' => [UserRole::Clerk],
                    'admin' => [UserRole::Admin],
                    'management' => [UserRole::Clerk, UserRole::Admin],
                    default => [UserRole::from($role)],
                };
            })
            ->values();

        if ($userRole === UserRole::Admin) {
            return $next($request);
        }

        if ($resolvedRoles->contains($userRole)) {
            return $next($request);
        }

        throw new AccessDeniedHttpException;
    }
}
