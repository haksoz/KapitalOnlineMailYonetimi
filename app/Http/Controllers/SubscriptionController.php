<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\Product;
use App\Models\ServiceProvider;
use App\Models\Subscription;
use App\Models\SubscriptionQuantityChange;
use App\Models\PendingBilling;
use App\Services\PendingBillingService;
use App\Services\SubscriptionProjectionService;
use App\Services\SubscriptionRenewalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionProjectionService $projectionService,
        protected SubscriptionRenewalService $renewalService,
        protected PendingBillingService $pendingBillingService
    ) {}

    public function index(Request $request): View
    {
        $query = Subscription::query()
            ->with([
                'customerCari:id,name,short_name,uuid',
                'providerCari:id,name,short_name,uuid',
                'product:id,name,stock_code',
                'serviceProvider:id,name,code',
            ])
            ->latest('baslangic_tarihi');

        if ($request->filled('customer_cari_id')) {
            $query->where('customer_cari_id', $request->customer_cari_id);
        }
        if ($request->filled('durum')) {
            $query->where('durum', $request->durum);
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $caris = Cari::orderBy('name')->get(['id', 'name', 'short_name']);

        return view('subscriptions.index', compact('subscriptions', 'caris'));
    }

    public function create(): View
    {
        $customerCaris = Cari::whereIn('cari_type', ['customer', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
        $providerCaris = Cari::whereIn('cari_type', ['supplier', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
        $serviceProviders = ServiceProvider::orderBy('name')->get(['id', 'name', 'code']);
        $products = Product::orderBy('name')->get(['id', 'name', 'stock_code']);

        return view('subscriptions.create', compact('customerCaris', 'providerCaris', 'serviceProviders', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_cari_id' => ['required', 'exists:caris,id'],
            'provider_cari_id' => ['nullable', 'exists:caris,id'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'sozlesme_no' => ['required', 'string', 'max:64'],
            'baslangic_tarihi' => ['required', 'date'],
            'bitis_tarihi' => ['nullable', 'date'],
            'taahhut_tipi' => ['required', 'string', 'in:monthly_commitment,monthly_no_commitment,annual_commitment'],
            'faturalama_periyodu' => ['required', 'string', 'in:monthly,yearly'],
            'durum' => ['required', 'string', 'in:active,cancelled,pending'],
            'auto_renew' => ['nullable', 'boolean'],
            'usd_birim_alis' => ['nullable', 'numeric', 'min:0'],
            'usd_birim_satis' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $validated['auto_renew'] = $request->boolean('auto_renew');
        if (! isset($validated['vat_rate']) || $validated['vat_rate'] === '') {
            $validated['vat_rate'] = 20;
        }

        // Başlangıç > bitiş ise izin verme
        if (! empty($validated['bitis_tarihi'])) {
            $baslangic = Carbon::parse($validated['baslangic_tarihi']);
            $bitis = Carbon::parse($validated['bitis_tarihi']);
            if ($bitis->lt($baslangic)) {
                return back()
                    ->withInput()
                    ->withErrors(['bitis_tarihi' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.']);
            }
        }

        if (empty($validated['bitis_tarihi'])) {
            $validated['bitis_tarihi'] = $this->renewalService->computeInitialEndDate(
                Carbon::parse($validated['baslangic_tarihi']),
                $validated['taahhut_tipi']
            )->format('Y-m-d');
        }

        $subscription = Subscription::create($validated);
        $this->pendingBillingService->addFirstPeriodForSubscription($subscription);

        return redirect()->route('subscriptions.index')->with('success', 'Abonelik eklendi.');
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load([
            'customerCari', 'providerCari', 'product', 'serviceProvider',
            'quantityChanges',
        ]);

        $orderSummaries = PendingBilling::where('subscription_id', $subscription->id)
            ->orderByDesc('period_start')
            ->paginate(15)
            ->withQueryString();

        return view('subscriptions.show', compact('subscription', 'orderSummaries'));
    }

    public function orderSummaryTotals(Subscription $subscription): \Illuminate\Http\JsonResponse
    {
        $rows = PendingBilling::where('subscription_id', $subscription->id)->get();

        $expectedSatis = 0.0;
        $actualAlis = 0.0;
        $actualSatis = 0.0;
        $fark = 0.0;

        foreach ($rows as $pb) {
            if ($pb->expected_satis_tl !== null && $pb->expected_satis_tl !== '') {
                $expectedSatis += (float) $pb->expected_satis_tl;
            }
            if ($pb->actual_alis_tl !== null && $pb->actual_alis_tl !== '') {
                $actualAlis += (float) $pb->actual_alis_tl;
            }
            if ($pb->actual_satis_tl !== null && $pb->actual_satis_tl !== '') {
                $actualSatis += (float) $pb->actual_satis_tl;
            }
            $farkVal = $pb->fee_difference_tl;
            if ($farkVal === null && $pb->expected_satis_tl !== null && $pb->expected_satis_tl !== '' && $pb->actual_satis_tl !== null && $pb->actual_satis_tl !== '') {
                $farkVal = (float) $pb->expected_satis_tl - (float) $pb->actual_satis_tl;
            }
            if ($farkVal !== null) {
                $fark += (float) $farkVal;
            }
        }

        return response()->json([
            'expected_satis_tl' => round($expectedSatis, 2),
            'actual_alis_tl' => round($actualAlis, 2),
            'actual_satis_tl' => round($actualSatis, 2),
            'fark_tl' => round($fark, 2),
        ]);
    }

    public function showUpdateQuantity(Subscription $subscription): View
    {
        return view('subscriptions.update-quantity', compact('subscription'));
    }

    public function updateQuantity(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'new_quantity' => ['required', 'integer', 'min:1'],
            'effective_date' => ['required', 'date'],
        ]);

        $previousQuantity = (int) $subscription->quantity;
        $newQuantity = (int) $validated['new_quantity'];

        if ($newQuantity === $previousQuantity) {
            return redirect()
                ->route('subscriptions.show', $subscription)
                ->with('info', 'Adet değişmedi.');
        }

        SubscriptionQuantityChange::create([
            'subscription_id' => $subscription->id,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'effective_date' => $validated['effective_date'],
        ]);

        $subscription->update(['quantity' => $newQuantity]);

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'Ürün adeti güncellendi.');
    }

    public function edit(Subscription $subscription): View
    {
        $customerCaris = Cari::whereIn('cari_type', ['customer', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
        $providerCaris = Cari::whereIn('cari_type', ['supplier', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
        $serviceProviders = ServiceProvider::orderBy('name')->get(['id', 'name', 'code']);
        $products = Product::orderBy('name')->get(['id', 'name', 'stock_code']);

        return view('subscriptions.edit', compact('subscription', 'customerCaris', 'providerCaris', 'serviceProviders', 'products'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'customer_cari_id' => ['required', 'exists:caris,id'],
            'provider_cari_id' => ['nullable', 'exists:caris,id'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'sozlesme_no' => ['required', 'string', 'max:64'],
            'baslangic_tarihi' => ['required', 'date'],
            'bitis_tarihi' => ['nullable', 'date'],
            'taahhut_tipi' => ['required', 'string', 'in:monthly_commitment,monthly_no_commitment,annual_commitment'],
            'faturalama_periyodu' => ['required', 'string', 'in:monthly,yearly'],
            'durum' => ['required', 'string', 'in:active,cancelled,pending'],
            'auto_renew' => ['nullable', 'boolean'],
            'usd_birim_alis' => ['nullable', 'numeric', 'min:0'],
            'usd_birim_satis' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $validated['auto_renew'] = $request->boolean('auto_renew');
        if (! isset($validated['vat_rate']) || $validated['vat_rate'] === '') {
            $validated['vat_rate'] = 20;
        }

        // Başlangıç > bitiş ise izin verme
        if (! empty($validated['bitis_tarihi'])) {
            $baslangic = Carbon::parse($validated['baslangic_tarihi']);
            $bitis = Carbon::parse($validated['bitis_tarihi']);
            if ($bitis->lt($baslangic)) {
                return back()
                    ->withInput()
                    ->withErrors(['bitis_tarihi' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.']);
            }
        }

        if (empty($validated['bitis_tarihi'])) {
            $validated['bitis_tarihi'] = $this->renewalService->computeInitialEndDate(
                Carbon::parse($validated['baslangic_tarihi']),
                $validated['taahhut_tipi']
            )->format('Y-m-d');
        }

        if ($validated['auto_renew'] === true) {
            $end = Carbon::parse($validated['bitis_tarihi'])->startOfDay();
            if ($end->lte(now()->startOfDay())) {
                return back()
                    ->withInput()
                    ->withErrors(['auto_renew' => 'Bitiş tarihi geçmiş/bugün olan abonelikte otomatik yenileme açılamaz. Otomatik yenileme için bitiş tarihini bugünden ileri bir güne güncelleyin.']);
            }
        }

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Abonelik güncellendi.');
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        if ($subscription->durum === Subscription::DURUM_CANCELLED) {
            return redirect()
                ->route('subscriptions.show', $subscription)
                ->with('info', 'Bu abonelik zaten iptal edilmiş.');
        }

        if ($subscription->durum === Subscription::DURUM_PENDING) {
            return redirect()
                ->route('subscriptions.show', $subscription)
                ->with('info', 'Bu abonelik için zaten bir iptal talimatı var.');
        }

        $bitis = $subscription->bitis_tarihi;

        $plannedCancelDate = $bitis?->copy() ?? now()->toDateString();

        $subscription->update([
            'durum' => Subscription::DURUM_PENDING,
            'auto_renew' => false,
            'planned_cancel_date' => $plannedCancelDate,
        ]);

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'Abonelik, bitiş tarihinde iptal edilmek üzere işaretlendi.');
    }

    public function toggleAutoRenew(Request $request, Subscription $subscription): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $next = ! (bool) $subscription->auto_renew;

        // Kural: Bitişi gelmiş ve durum iptal olmuş aboneliklerde otomatik yenileme tekrar açılamaz.
        if (
            $next === true
            && $subscription->durum === Subscription::DURUM_CANCELLED
            && $subscription->bitis_tarihi !== null
            && $subscription->bitis_tarihi->lte(now()->startOfDay())
        ) {
            $message = 'Bu abonelik bitişi geldiği için iptal olmuş. Otomatik yenileme tekrar açılamaz.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'auto_renew' => (bool) $subscription->auto_renew,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('subscriptions.index', $request->query())
                ->with('error', $message);
        }

        $subscription->update(['auto_renew' => $next]);

        $fresh = $subscription->fresh();
        $label = $fresh->auto_renew ? 'açıldı' : 'kapatıldı';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'auto_renew' => (bool) $fresh->auto_renew,
                'message' => 'Otomatik yenileme ' . $label . '.',
            ]);
        }

        return redirect()
            ->route('subscriptions.index', $request->query())
            ->with('success', 'Otomatik yenileme ' . $label . '.');
    }

    public function createProjection(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $this->projectionService->generateForSubscriptionAndMonth(
            $subscription,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', $validated['year'] . '-' . str_pad($validated['month'], 2, '0', STR_PAD_LEFT) . ' için beklenen maliyet kaydı oluşturuldu.');
    }
}
