<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Rates, fees, stock, offices and staff accounts are for managers and admins only. */
class EnsureStaffCanManage
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user('staff')?->canManage(), 403, 'Only managers and admins can change settings.');

        return $next($request);
    }
}
