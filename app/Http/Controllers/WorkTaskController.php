<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\QuoteMath;
use App\Services\RenewalWorkTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkTaskController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Europe/Istanbul')->toDateString();
        $query = WorkTask::query()->with(['customerCari', 'assignee', 'subject']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } elseif (! $request->boolean('closed')) {
            $query->open();
        }
        $query->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')->toString()))
            ->when($request->filled('customer'), fn ($q) => $q->where('customer_cari_id', $request->input('customer')))
            ->when($request->input('owner') === 'mine', fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when($request->input('owner') === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
            ->when(is_numeric($request->input('owner')), fn ($q) => $q->where('assigned_to', $request->input('owner')))
            ->when($request->input('timing') === 'today', fn ($q) => $q->whereDate(DB::raw('COALESCE(next_follow_up, due_date)'), '<=', $today))
            ->when($request->input('timing') === 'overdue', fn ($q) => $q->whereDate(DB::raw('COALESCE(next_follow_up, due_date)'), '<', $today))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($q) => $q->where('title', 'like', $search)->orWhereHas('customerCari', fn ($q) => $q->where('name', 'like', $search)->orWhere('short_name', 'like', $search)));
            });

        return view('work-tasks.index', [
            'tasks' => $query->orderByRaw('COALESCE(next_follow_up, due_date)')->orderBy('due_date')->paginate(25)->withQueryString(),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'customers' => Cari::whereHas('workTasks')->orderBy('name')->get(),
            'counts' => [
                'open' => WorkTask::open()->count(),
                'mine' => WorkTask::open()->where('assigned_to', $request->user()->id)->count(),
                'today' => WorkTask::open()->whereDate(DB::raw('COALESCE(next_follow_up, due_date)'), '<=', $today)->count(),
                'overdue' => WorkTask::open()->whereDate(DB::raw('COALESCE(next_follow_up, due_date)'), '<', $today)->count(),
            ],
        ]);
    }

    public function detect(RenewalWorkTaskService $service)
    {
        return back()->with('success', $service->detect().' yeni yenileme işi oluşturuldu.');
    }

    public function show(WorkTask $workTask)
    {
        $workTask->load(['customerCari', 'assignee', 'costChecker', 'activities.user', 'quotes.items.subscriptionRecord.subscription', 'subject']);
        if ($workTask->type === WorkTask::TYPE_QUOTE) {
            $workTask->subject?->load('items.subscriptionRecord');
            return view('work-tasks.quote', ['task' => $workTask, 'quote' => $workTask->subject, 'users' => User::where('is_active', true)->orderBy('name')->get()]);
        }
        $catalog = $workTask->subject instanceof Subscription
            ? $workTask->subject->product?->pricesForCommitment($workTask->snapshot['commitment']) : null;

        $initialCost = old('verified_cost', $workTask->verified_cost ?? $catalog['alis'] ?? '');
        $existingProfitRate = QuoteMath::profitPercentage($workTask->snapshot['cost'], $workTask->snapshot['sale']);
        $suggestedSale = QuoteMath::saleKeepingProfit($initialCost, $workTask->snapshot['cost'], $workTask->snapshot['sale']);
        $initialSale = old('proposed_sale', $workTask->proposed_sale ?? $suggestedSale ?? $catalog['satis'] ?? '');
        if ($initialSale !== '' && $initialSale !== null && is_numeric($initialSale)) {
            $initialSale = (string) \Brick\Math\BigDecimal::of((string) $initialSale)->toScale(2, \Brick\Math\RoundingMode::HALF_UP);
        }

        return view('work-tasks.show', [
            'task' => $workTask, 'catalog' => $catalog, 'initialCost' => $initialCost,
            'initialSale' => $initialSale, 'existingProfitRate' => $existingProfitRate,
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkTask $workTask)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['assign', 'note', 'pricing', 'notify', 'complete', 'cancel'])],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'next_follow_up' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:10000'],
            'verified_cost' => ['required_if:action,pricing', 'nullable', 'numeric', 'min:0', 'max:99999999999999', 'decimal:0,4'],
            'proposed_sale' => ['required_if:action,pricing', 'nullable', 'numeric', 'min:0', 'max:99999999999999', 'decimal:0,4'],
            'cost_confirmed' => ['accepted_if:action,pricing'],
        ]);
        DB::transaction(function () use ($workTask, $data, $request) {
            $task = WorkTask::whereKey($workTask->id)->lockForUpdate()->firstOrFail();
            $this->require($task->isOpen(), 'Tamamlanan veya iptal edilen iş değiştirilemez.');
            $this->require($task->type !== WorkTask::TYPE_QUOTE || in_array($data['action'], ['assign', 'note'], true), 'Teklifin durumunu ilgili teklif üzerinden güncelleyin.');
            $before = $task->only(['assigned_to', 'status', 'next_follow_up', 'verified_cost', 'proposed_sale']);
            $note = trim($data['note'] ?? '');
            $message = '';
            switch ($data['action']) {
                case 'assign':
                    $task->assigned_to = $data['assigned_to'] ?? null;
                    $message = 'Sorumlu güncellendi.';
                    break;
                case 'note':
                    $this->require($note !== '' || ! empty($data['next_follow_up']), 'Not veya sonraki takip tarihi girin.');
                    $message = 'Takip bilgisi kaydedildi.';
                    break;
                case 'pricing':
                    $this->require(! $task->hasBlockingQuote(), 'Devam eden teklif varken fiyat kontrolü değiştirilemez. Önce ilgili teklifi sonuçlandırın.');
                    if ($task->type === WorkTask::TYPE_RENEWAL) {
                        $product = Product::whereKey($task->snapshot['product_id'])->lockForUpdate()->first();
                        $this->require($product !== null, 'İlgili ürün bulunamadı.');
                        $this->require(($product->currency ?? 'USD') === $task->snapshot['currency'], 'Ürünün para birimi abonelikle aynı olmalıdır.');
                        $field = match ($task->snapshot['commitment']) {
                            Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'alis_usd_monthly_commitment',
                            Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'alis_usd_monthly_no_commitment',
                            Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'alis_usd_yearly_commitment',
                        };
                        $productCost = (string) \Brick\Math\BigDecimal::of((string) $data['verified_cost'])->toScale(2, \Brick\Math\RoundingMode::HALF_UP);
                        $this->require(\Brick\Math\BigDecimal::of($productCost)->isLessThanOrEqualTo('99999999.99'), 'Ürün alış fiyatı en fazla 99.999.999,99 olabilir.');
                        $product->update([$field => $productCost]);
                    }
                    $task->verified_cost = $data['verified_cost'];
                    $task->proposed_sale = (string) \Brick\Math\BigDecimal::of((string) $data['proposed_sale'])->toScale(2, \Brick\Math\RoundingMode::HALF_UP);
                    $task->cost_checked_by = $request->user()->id;
                    $task->cost_checked_at = now();
                    $task->status = 'pricing';
                    $message = 'Yeni dönem alış fiyatı doğrulandı ve satış fiyatı kaydedildi.';
                    if ($task->type === WorkTask::TYPE_RENEWAL) {
                        $message .= ' Ürünün '.Quote::commitmentLabel($task->snapshot['commitment']).' alış fiyatı güncellendi.';
                    }
                    break;
                case 'notify':
                    $this->require($task->cost_checked_at !== null && $note !== '', 'Alış fiyatını doğrulayın ve bildirimin tarihini / kanalını açıklayın.');
                    $task->notified_at = now();
                    $task->status = ($task->snapshot['auto_renew'] ?? false) ? 'renewal_pending' : 'response_pending';
                    $message = 'Harici müşteri bildirimi kaydedildi.';
                    break;
                case 'complete':
                    $this->require($task->type !== WorkTask::TYPE_RENEWAL, 'Onaylı teklifin Abonelik işlemini kaydet düğmesini kullanın.');
                    $this->require($note !== '' && $task->notified_at !== null && $task->cost_checked_at !== null, 'Tamamlamak için alış kontrolü, müşteri bildirimi ve sonuç açıklaması gereklidir.');
                    $task->status = 'completed';
                    $task->completed_at = now();
                    $task->result = $note;
                    $message = 'İş tamamlandı.';
                    break;
                case 'cancel':
                    $this->require($note !== '', 'Yenilememe / iptal gerekçesi girin.');
                    $task->status = 'cancelled';
                    $task->completed_at = now();
                    $task->result = $note;
                    $message = 'İş yenilenmeyecek / iptal olarak sonuçlandırıldı. Abonelik iptal talimatını ayrıca kontrol edin.';
                    break;
            }
            if (array_key_exists('next_follow_up', $data)) {
                $task->next_follow_up = $data['next_follow_up'];
            }
            $task->save();
            $task->record($message.($note !== '' ? ' '.$note : ''), $request->user()->id, ['before' => $before, 'after' => $task->only(array_keys($before))]);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fiyat kontrolü kaydedildi.']);
        }

        return back()->with('success', 'İş güncellendi.');
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['task' => $message]);
        }
    }
}
