<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the CRUK session header is present, set the form hydration schema
 * model and version at app level so form hydration endpoints use the CRUK schema.
 */
class CrukSessionHeader
{
    public const CRUK_SESSION_HEADER = 'X-CRUK-Session';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->header(self::CRUK_SESSION_HEADER);
        if ($value !== null && $value !== '') {
            $model = Config::get('form_hydration.schema.model', 'HDRUK');
            $version = Config::get('form_hydration.schema.latest_version', '4.1.0');
            Config::set('form_hydration.schema.model', $model);
            Config::set('form_hydration.schema.latest_version', $version);
        }

        return $next($request);
    }
}
