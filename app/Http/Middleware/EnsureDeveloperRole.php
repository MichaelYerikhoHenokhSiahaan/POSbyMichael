<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloperRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isDeveloper()) {
            return redirect()
                ->route('sales.index')
                ->withErrors(['authorization' => 'Only developers can access.']);
        }

        return $next($request);
    }
}
