<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roleSegments  Параметры из `role:designer|moderator` или несколько сегментов
     */
    public function handle(Request $request, Closure $next, string ...$roleSegments): Response
    {
        $wantsJson = $request->expectsJson() || $request->is('api/*');

        if (! $request->user()) {
            if ($wantsJson) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->route('login');
        }

        $allowed = $this->normalizeAllowedRoles($roleSegments);
        $userRole = (string) ($request->user()->role ?? 'designer');
        // Legacy 'moderator' accounts were migrated to system_admin, so admin
        // roles must also satisfy moderator-only routes.
        $aliases = array_values(array_unique(array_filter([
            $userRole,
            $userRole === 'system_admin' ? 'admin' : null,
            $userRole === 'system_admin' ? 'moderator' : null,
            $userRole === 'admin' ? 'system_admin' : null,
            $userRole === 'admin' ? 'moderator' : null,
        ])));

        if (array_intersect($aliases, $allowed) !== []) {
            return $next($request);
        }

        if ($wantsJson) {
            abort(403, 'Forbidden.');
        }

        if (in_array($userRole, ['moderator', 'admin', 'system_admin'], true)) {
            return redirect()->route('moderator.index');
        }

        if ($userRole === 'designer') {
            return redirect()->route('dashboard');
        }

        if ($userRole === 'supplier') {
            return redirect()->route(\App\Support\SupplierDeposit::redirectRoute($request->user()));
        }

        abort(403, 'У вас нет доступа');
    }

    /**
     * @param  array<int, string>  $roleSegments
     * @return list<string>
     */
    private function normalizeAllowedRoles(array $roleSegments): array
    {
        $allowed = [];

        foreach ($roleSegments as $segment) {
            foreach (preg_split('/[|]/', $segment) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $allowed[] = $part;
                }
            }
        }

        return array_values(array_unique($allowed));
    }
}
