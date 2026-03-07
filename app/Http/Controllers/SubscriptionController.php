<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\Product;
use App\Models\ServiceProvider;
use App\Models\Subscription;
use App\Models\SubscriptionQuantityChange;
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
            'monthlyProjections' => fn ($q) => $q->orderByDesc('year')->orderByDesc('month'),
            'quantityChanges',
        ]);

        return view('subscriptions.show', compact('subscription'));
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

        if (empty($validated['bitis_tarihi'])) {
            $validated['bitis_tarihi'] = $this->renewalService->computeInitialEndDate(
                Carbon::parse($validated['baslangic_tarihi']),
                $validated['taahhut_tipi']
            )->format('Y-m-d');
        }

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Abonelik güncellendi.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Abonelik silindi.');
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
