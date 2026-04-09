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
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $allowed = $this->normalizeAllowedRoles($roleSegments);
        $userRole = $request->user()->role ?? 'designer';

        if (in_array($userRole, $allowed, true)) {
            return $next($request);
        }

        if ($userRole === 'moderator') {
            return redirect()->route('moderator.index');
        }

        if ($userRole === 'designer') {
            return redirect()->route('dashboard');
        }

        if ($userRole === 'supplier') {
            return redirect()->route('supplier.index');
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
