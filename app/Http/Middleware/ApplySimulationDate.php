<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySimulationDate
{
    /**
     * Session'da simülasyon tarihi varsa Carbon'ı o tarihe kilitle;
     * tüm uygulama "bugün" olarak bu tarihi görür.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dateStr = $request->session()->get('simulation_date');
        if (is_string($dateStr) && $dateStr !== '') {
            $parsed = Carbon::parse($dateStr)->startOfDay();
            Carbon::setTestNow($parsed);
        }

        return $next($request);
    }
}
