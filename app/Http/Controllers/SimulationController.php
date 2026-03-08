<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionRenewalLog;
use App\Services\PendingBillingService;
use App\Services\SubscriptionRenewalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulationController extends Controller
{
    public function index(Request $request): View
    {
        $simulationDate = $request->session()->get('simulation_date');

        return view('simulation.index', [
            'simulationDate' => $simulationDate,
            'effectiveToday' => now()->toDateString(),
            'isActive' => (bool) $simulationDate,
        ]);
    }

    public function setDate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $request->session()->put('simulation_date', $validated['date']);

        return redirect()
            ->route('simulation.index')
            ->with('success', 'Simülasyon tarihi ayarlandı: ' . Carbon::parse($validated['date'])->format('d.m.Y') . '. Uygulama bu tarihte çalışıyormuş gibi davranacak.');
    }

    public function clearDate(Request $request): RedirectResponse
    {
        $request->session()->forget('simulation_date');
        Carbon::setTestNow();

        return redirect()
            ->route('simulation.index')
            ->with('success', 'Simülasyon kapatıldı. Gerçek tarih kullanılıyor.');
    }

    public function runEnqueue(PendingBillingService $pendingBillingService): RedirectResponse
    {
        $onDate = Carbon::today();
        $added = $pendingBillingService->enqueueDuePeriods($onDate);

        return redirect()
            ->route('simulation.index')
            ->with('success', "Dönem başı siparişleri eklendi (işlem tarihi: {$onDate->format('d.m.Y')}). {$added} kayıt oluşturuldu.");
    }

    public function runRenewals(SubscriptionRenewalService $renewalService): RedirectResponse
    {
        $asOf = Carbon::today();
        $renewed = $renewalService->processRenewals($asOf);
        $count = count($renewed);

        SubscriptionRenewalLog::create([
            'run_at' => now(),
            'as_of_date' => $asOf->toDateString(),
            'renewed_count' => $count,
            'renewed_ids' => $renewed,
        ]);

        $message = $count > 0
            ? "Abonelik yenilemeleri işlendi (işlem tarihi: {$asOf->format('d.m.Y')}). {$count} abonelik yenilendi."
            : "Abonelik yenilemeleri işlendi (işlem tarihi: {$asOf->format('d.m.Y')}). Yenilenecek abonelik yoktu.";

        return redirect()
            ->route('simulation.index')
            ->with('success', $message);
    }
}
