<?php

namespace App\Http\Middleware;

use App\Support\PortalSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*') || $request->is('login') || $request->is('up')) {
            return $next($request);
        }

        $settings = PortalSettings::get('system.maintenance');

        if (! ($settings['enabled'] ?? false)) {
            return $next($request);
        }

        return response()->view('maintenance', ['settings' => $settings], 503, ['Retry-After' => '3600']);
    }
}
