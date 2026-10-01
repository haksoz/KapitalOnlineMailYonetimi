<?php

namespace App\Http\Controllers;

use App\Automation\DomainEvents;
use App\Models\Cari;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Subscription;
use App\Services\QuoteMath;
use App\Services\QuoteService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quotes, private DomainEvents $events) {}

    public function index(Request $request): View
    {
        $quotes = Quote::query()
            ->with(['customerCari:id,name,short_name'])
            ->withCount('items')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('quote_number', 'like', "%{$search}%")
                        ->orWhereHas('customerCari', function ($cari) use ($search): void {
                            $cari->where('name', 'like', "%{$search}%")
                                ->orWhere('short_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('quotes.index', compact('quotes'));
    }

    public function create(Request $request): View
    {
        $type = old('type', $request->query('type', Quote::TYPE_OPTIONAL));
        if (! in_array($type, [Quote::TYPE_OPTIONAL, Quote::TYPE_FIRM], true)) {
            $type = Quote::TYPE_OPTIONAL;
        }

        return view('quotes.create', $this->formViewData($type, $this->oldItems($request), null));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => ['required', 'in:'.Quote::TYPE_OPTIONAL.','.Quote::TYPE_FIRM],
        ], $this->messages())['type'];

        $data = $this->validated($request, $type);
        $quote = $this->quotes->create($data, (int) $request->user()->id);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote): View
    {
        $quote->load([
            'customerCari',
            'items.options',
            'items.product:id,name',
            'sourceQuote:id,quote_number,type',
            'derivedQuote:id,quote_number,type,status',
            'creator:id,name',
            'approver:id,name',
        ]);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote): View|RedirectResponse
    {
        if (! $quote->canRevise()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Yalnızca taslak veya reddedilen teklif düzenlenebilir.');
        }

        $quote->load('items.options');

        return view('quotes.edit', $this->formViewData($quote->type, $this->oldItems(request()), $quote));
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        if (! $quote->canRevise()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Yalnızca taslak veya reddedilen teklif düzenlenebilir.');
        }

        $data = $this->validated($request, $quote->type);
        $this->quotes->update($quote, $data);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif güncellendi.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if (! $quote->isDraft()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Yalnızca taslak teklif silinebilir.');
        }

        if ($quote->derivedQuote()->exists()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Kesin teklife dönüştürülmüş kayıt silinemez.');
        }

        DB::transaction(function () use ($quote): void {
            if ($quote->source_quote_id) {
                $source = $quote->sourceQuote;
                if ($source && $source->status === Quote::STATUS_CONVERTED) {
                    $source->status = $source->sent_at ? Quote::STATUS_SENT : Quote::STATUS_DRAFT;
                    $source->save();
                }
            }

            $quote->delete();
        });

        return redirect()->route('quotes.index')->with('success', 'Teklif silindi.');
    }

    public function customer(Quote $quote): View
    {
        $quote->load(['customerCari', 'items.options']);

        return view('quotes.customer', compact('quote'));
    }

    public function convertForm(Quote $quote): View|RedirectResponse
    {
        if (! $quote->canConvert()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Bu teklif kesin teklife dönüştürülemez.');
        }

        $quote->load(['customerCari', 'items.options']);

        return view('quotes.convert', compact('quote'));
    }

    public function convertStore(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.include' => ['nullable'],
            'lines.*.quote_item_id' => ['required', 'integer'],
            'lines.*.taahhut_tipi' => ['nullable', 'string', 'in:'.implode(',', Quote::COMMITMENTS)],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'lines.*.birim_satis' => ['nullable', 'numeric', 'min:0'],
        ], $this->messages());

        $firm = $this->quotes->convert($quote, $data, (int) $request->user()->id);

        return redirect()->route('quotes.show', $firm)->with('success', 'Kesin teklif oluşturuldu.');
    }

    public function send(Request $request, Quote $quote): RedirectResponse
    {
        $quote->load('customerCari');
        $cari = $quote->customerCari;
        $registered = $cari?->notificationEmails() ?? [];
        $email = null;

        if ($registered === []) {
            $email = $request->validate([
                'email' => ['required', 'email', 'max:255'],
            ], [
                'email.required' => 'E-posta adresi gerekli.',
                'email.email' => 'Geçerli bir e-posta adresi girin.',
            ])['email'];
        }

        $resend = $quote->status === Quote::STATUS_REJECTED;
        $this->quotes->transition($quote, Quote::STATUS_SENT);

        if ($email !== null && $cari !== null) {
            $cari->email = Cari::normalizeEmailList($email);
            $cari->save();
        }

        $recipients = $cari?->fresh()->notificationEmails() ?? [];
        $mail = $this->events->quoteSent($quote->fresh(['customerCari', 'items.options']), $recipients);
        $message = $resend ? 'Teklif tekrar gönderildi.' : 'Teklif gönderildi.';
        $message .= match ($mail) {
            'sent' => ' Fiyatlar kilitlendi ve e-posta iletildi.',
            'disabled' => ' Fiyatlar kilitlendi. E-posta kuralı kapalı olduğu için mail gitmedi.',
            default => ' Fiyatlar kilitlendi. E-posta iletilemedi.',
        };

        return redirect()->route('quotes.show', $quote)->with('success', $message);
    }

    public function approve(Request $request, Quote $quote): RedirectResponse
    {
        $this->quotes->transition($quote, Quote::STATUS_APPROVED, (int) $request->user()->id);

        return redirect()->route('quotes.show', $quote)->with('success', 'Kesin teklif onaylandı.');
    }

    public function reject(Quote $quote): RedirectResponse
    {
        $this->quotes->transition($quote, Quote::STATUS_REJECTED);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif reddedildi.');
    }

    public function expire(Quote $quote): RedirectResponse
    {
        $this->quotes->transition($quote, Quote::STATUS_EXPIRED);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklifin süresi doldu olarak işaretlendi.');
    }

    public function cancel(Quote $quote): RedirectResponse
    {
        $this->quotes->transition($quote, Quote::STATUS_CANCELLED);

        return redirect()->route('quotes.show', $quote)->with('success', 'Teklif iptal edildi.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function oldItems(Request $request): ?array
    {
        $items = $request->old('items');

        return is_array($items) ? $items : null;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $oldItems
     * @return array<string, mixed>
     */
    private function formViewData(string $type, ?array $oldItems, ?Quote $quote): array
    {
        $products = Product::query()->orderBy('name')->get([
            'id',
            'name',
            'stock_code',
            'currency',
            'alis_usd_monthly_commitment',
            'satis_usd_monthly_commitment',
            'alis_usd_monthly_no_commitment',
            'satis_usd_monthly_no_commitment',
            'alis_usd_yearly_commitment',
            'satis_usd_yearly_commitment',
        ]);

        $lines = $oldItems !== null
            ? $this->linesFromOld($oldItems)
            : ($quote ? $this->linesFromQuote($quote) : [$this->blankLine()]);

        $vatRate = old('vat_rate', $quote?->vat_rate ?? ($type === Quote::TYPE_FIRM ? '20' : ''));

        $formConfig = [
            'type' => $type,
            'lockedType' => $quote !== null,
            'vatRate' => $vatRate === null ? '' : (string) $vatRate,
            'products' => $this->productOptions($products),
            'lines' => $lines,
            'commitmentTypes' => Quote::COMMITMENTS,
            'commitmentLabels' => Quote::commitmentLabels(),
        ];

        return [
            'type' => $type,
            'isEdit' => $quote !== null,
            'quote' => $quote,
            'formConfig' => $formConfig,
            'customerCaris' => Cari::query()
                ->whereIn('cari_type', ['customer', 'both'])
                ->orderBy('name')
                ->get(['id', 'name', 'short_name', 'tax_number', 'email']),
            'productOptions' => $this->productOptions($products),
            'lines' => $lines,
            'vatRate' => $formConfig['vatRate'],
            'commitmentTypes' => Quote::COMMITMENTS,
            'commitmentLabels' => Quote::commitmentLabels(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function productOptions($products): array
    {
        return $products->map(function (Product $product): array {
            $prices = [];
            foreach (Quote::COMMITMENTS as $tip) {
                $pair = $product->pricesForCommitment($tip);
                $prices[$tip] = [
                    'alis' => $pair['alis'],
                    'satis' => $pair['satis'],
                ];
            }

            $currency = $product->currency ?? Product::CURRENCY_USD;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'stock_code' => $product->stock_code,
                'currency' => $currency,
                'label' => $product->name
                    .($product->stock_code ? ' ('.$product->stock_code.')' : '')
                    .' — '.($currency === Product::CURRENCY_TRY ? 'TL' : 'USD'),
                'prices' => $prices,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function linesFromQuote(Quote $quote): array
    {
        return $quote->items->map(function (QuoteItem $item): array {
            $options = [];
            foreach (Quote::COMMITMENTS as $tip) {
                $existing = $item->options->firstWhere('taahhut_tipi', $tip);
                $options[$tip] = [
                    'enabled' => $existing !== null,
                    'birim_satis' => $existing?->birim_satis !== null ? $this->inputPrice($existing->birim_satis) : '',
                ];
            }

            return [
                'key' => 'item-'.$item->id,
                'id' => $item->id,
                'product_id' => (string) $item->product_id,
                'quantity' => $item->quantity,
                'taahhut_tipi' => $item->taahhut_tipi ?? Subscription::TAAHHUT_MONTHLY_COMMITMENT,
                'birim_satis' => $item->birim_satis !== null ? $this->inputPrice($item->birim_satis) : '',
                'options' => $options,
            ];
        })->all();
    }

    /**
     * @param  array<int, mixed>  $oldItems
     * @return array<int, array<string, mixed>>
     */
    private function linesFromOld(array $oldItems): array
    {
        $lines = [];
        foreach (array_values($oldItems) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $options = [];
            foreach (Quote::COMMITMENTS as $tip) {
                $option = is_array($row['options'][$tip] ?? null) ? $row['options'][$tip] : [];
                $options[$tip] = [
                    'enabled' => $this->isEnabled($option['enabled'] ?? false),
                    'birim_satis' => (string) ($option['birim_satis'] ?? ''),
                ];
            }

            $lines[] = [
                'key' => 'old-'.$index,
                'id' => $row['id'] ?? '',
                'product_id' => (string) ($row['product_id'] ?? ''),
                'quantity' => $row['quantity'] ?? 1,
                'taahhut_tipi' => $row['taahhut_tipi'] ?? Subscription::TAAHHUT_MONTHLY_COMMITMENT,
                'birim_satis' => (string) ($row['birim_satis'] ?? ''),
                'options' => $options,
            ];
        }

        return $lines === [] ? [$this->blankLine()] : $lines;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankLine(): array
    {
        $options = [];
        foreach (Quote::COMMITMENTS as $tip) {
            $options[$tip] = [
                'enabled' => false,
                'birim_satis' => '',
            ];
        }

        return [
            'key' => 'line-new',
            'id' => '',
            'product_id' => '',
            'quantity' => 1,
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'birim_satis' => '',
            'options' => $options,
        ];
    }

    private function inputPrice(mixed $value): string
    {
        $unit = QuoteMath::unit($value);
        if ($unit === null) {
            return '';
        }

        return (string) BigDecimal::of($unit)->toScale(2, RoundingMode::HALF_UP);
    }

    private function isEnabled(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true';
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $type): array
    {
        $data = $request->validate($this->rules($type), $this->messages());
        $data['type'] = $type;
        $data['refresh_catalog'] = $request->boolean('refresh_catalog');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $type): array
    {
        $rules = [
            'customer_cari_id' => [
                'required',
                Rule::exists('caris', 'id')->where(fn ($query) => $query->whereIn('cari_type', ['customer', 'both'])),
            ],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'refresh_catalog' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];

        if ($type === Quote::TYPE_FIRM) {
            $rules['vat_rate'] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules['items.*.taahhut_tipi'] = ['required', 'string', 'in:'.implode(',', Quote::COMMITMENTS)];
            $rules['items.*.birim_satis'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $rules['items.*.options'] = ['nullable', 'array'];
            foreach (Quote::COMMITMENTS as $tip) {
                $rules["items.*.options.$tip.enabled"] = ['nullable'];
                $rules["items.*.options.$tip.birim_satis"] = ['nullable', 'numeric', 'min:0'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'type.required' => 'Teklif türü seçilmelidir.',
            'customer_cari_id.required' => 'Müşteri seçilmelidir.',
            'customer_cari_id.exists' => 'Müşteri bulunamadı.',
            'items.required' => 'En az bir teklif kalemi girilmelidir.',
            'items.min' => 'En az bir teklif kalemi girilmelidir.',
            'items.*.product_id.required' => 'Her kalemde ürün seçilmelidir.',
            'items.*.quantity.required' => 'Adet girilmelidir.',
            'items.*.quantity.min' => 'Adet en az 1 olmalıdır.',
            'vat_rate.required' => 'Kesin teklifte KDV oranı girilmelidir.',
            'items.*.taahhut_tipi.required' => 'Kesin teklif kaleminde taahhüt tipi seçilmelidir.',
            'lines.required' => 'Kesin teklife en az bir kalem alınmalıdır.',
            'lines.min' => 'Kesin teklife en az bir kalem alınmalıdır.',
        ];
    }
}
