<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionRenewalLog;
use Illuminate\View\View;

class SubscriptionRenewalLogController extends Controller
{
    public function index(): View
    {
        $logs = SubscriptionRenewalLog::query()
            ->orderByDesc('run_at')
            ->paginate(20)
            ->withQueryString();

        return view('subscription-renewal-logs.index', compact('logs'));
    }
}
