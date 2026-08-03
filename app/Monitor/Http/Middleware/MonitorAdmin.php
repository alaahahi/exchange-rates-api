<?php

namespace App\Monitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $allowed = config('monitor.admin_type_ids', [1]);
        if (!in_array((int) $user->type_id, $allowed, true)) {
            abort(403, 'Monitor access denied');
        }

        return $next($request);
    }
}
