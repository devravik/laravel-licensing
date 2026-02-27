<?php

namespace DevRavik\LaravelLicensing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckValidLicense
{
    public function handle(Request $request, Closure $next): mixed
    {
        // TODO: Implemented in Plan 09
        return $next($request);
    }
}
