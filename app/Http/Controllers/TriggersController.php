<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionRenewalLog;
use App\Services\PendingBillingService;
use App\Services\SubscriptionRenewalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * "Bu ay sonuna kadar güncelle" önizleme: etkilenecek carileri ve abonelik bitiş tarihlerini listeler.
     */
    public function showRenewalsUpToEndOfMonth(): View
    {
        $upTo = Carbon::today()->endOfMonth();

        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->where('auto_renew', true)
            ->whereNotNull('bitis_tarihi')
            ->whereDate('bitis_tarihi', '<=', $upTo)
            ->with('customerCari')
            ->orderBy('bitis_tarihi')
            ->get();

        return view('triggers.renewals-up-to-end-of-month', [
            'upTo' => $upTo,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Seçilen aboneliklerin bitiş tarihini bu ay sonuna kadar uzatır.
     */
    public function runRenewalsUpToEndOfMonth(Request $request, SubscriptionRenewalService $renewalService): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_ids' => ['nullable', 'array'],
            'subscription_ids.*' => ['integer', 'exists:subscriptions,id'],
        ]);

        $subscriptionIds = array_map('intval', $validated['subscription_ids'] ?? []);
        $upTo = Carbon::today()->endOfMonth();

        $result = $renewalService->processRenewalsUpToForSubscriptionIds($upTo, $subscriptionIds);

        SubscriptionRenewalLog::create([
            'run_at' => now(),
            'as_of_date' => $upTo->toDateString(),
            'renewed_count' => count($result['renewed_ids']),
            'renewed_ids' => $result['renewed_ids'],
        ]);

        $count = count($result['renewed_ids']);
        $message = $result['total_extensions'] > 0
            ? "Seçilen aboneliklerin bitiş tarihleri bu ay sonuna kadar güncellendi (son tarih: {$upTo->format('d.m.Y')}). {$result['total_extensions']} yenileme uygulandı, {$count} abonelik etkilendi."
            : "Seçilen abonelik yoktu veya güncellenecek kayıt kalmadı.";

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
