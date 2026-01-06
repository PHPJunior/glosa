<?php

namespace PhpJunior\Glosa\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed|void
     */
    public function handle($request, Closure $next)
    {
        if (app()->environment('local') || app()->environment('testing')) {
            return $next($request);
        }

        if (Gate::allows('viewGlosa')) {
            return $next($request);
        }

        abort(403);
    }
}
