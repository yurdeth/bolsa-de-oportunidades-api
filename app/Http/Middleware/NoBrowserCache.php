<?php

namespace App\Http\Middleware;

use Closure;

/*
 * Middleware para evitar el cache en el navegador
 *
 * @param $request
 * @param Closure $next
 * @return mixed
 */

class NoBrowserCache {
    public function handle($request, Closure $next) {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
