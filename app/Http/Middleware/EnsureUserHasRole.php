<?php
// app/Http/Middleware/EnsureUserHasRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->roles->count() === 0) {
            abort(403, 'You do not have any role assigned.');
        }

        return $next($request);
    }
}