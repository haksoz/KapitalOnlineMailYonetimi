<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionRenewalLog;
use App\Services\PendingBillingService;
use App\Services\SubscriptionRenewalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TriggersController extends Controller
{
    public function index(): View
    {
        return view('triggers.index', [
            'effectiveToday' => now()->toDateString(),
        ]);
    }

    public function runRenewalsUpTo(SubscriptionRenewalService $renewalService): RedirectResponse
    {
        $upTo = Carbon::today();
        $result = $renewalService->processRenewalsUpTo($upTo);

        SubscriptionRenewalLog::create([
            'run_at' => now(),
            'as_of_date' => $upTo->toDateString(),
            'renewed_count' => count($result['renewed_ids']),
            'renewed_ids' => $result['renewed_ids'],
        ]);

        $count = count($result['renewed_ids']);
        $message = $result['total_extensions'] > 0
            ? "Abonelik bitiş tarihleri bugüne kadar güncellendi (son tarih: {$upTo->format('d.m.Y')}). {$result['total_extensions']} yenileme uygulandı, {$count} abonelik etkilendi."
            : "Abonelik bitiş tarihleri güncellendi. Güncellenecek abonelik yoktu.";

        return redirect()
            ->route('triggers.index')
            ->with('success', $message);
    }

    public function runEnqueueMissingPeriods(PendingBillingService $pendingBillingService): RedirectResponse
    {
        $upTo = Carbon::today();
        $added = $pendingBillingService->enqueueMissingPeriodsUpTo($upTo);

        return redirect()
            ->route('triggers.index')
            ->with('success', "Bugüne kadar eksik dönemler eklendi (son tarih: {$upTo->format('d.m.Y')}). {$added} kayıt oluşturuldu.");
    }
}
