<?php

namespace PhpJunior\Glosa\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, Closure $next)
    {
        if (app()->environment('local') || app()->environment('testing')) {
            return $next($request);
        }

        if (Gate::allows('viewGlosa', [$request->user()])) {
            return $next($request);
        }

        abort(403);
    }
}
