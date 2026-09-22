<?php

namespace App\Http\Controllers\Admin;

use App\Automation\ActionType;
use App\Automation\DedupePolicy;
use App\Automation\EventType;
use App\Automation\TimingMode;
use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\AutomationRuleAction;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AutomationRuleController extends Controller
{
    public function index(): View
    {
        $rules = AutomationRule::query()
            ->with(['actions.template'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $interestStartDays = (int) ($rules
            ->first(fn (AutomationRule $rule): bool => $rule->event_type === EventType::InvoiceInterestClosure)
            ?->offsetDays() ?? 30);

        return view('admin.notifications.edit', compact('rules', 'interestStartDays'));
    }

    public function create(): View
    {
        return $this->formView(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $rule = AutomationRule::query()->create([
            'name' => $validated['name'],
            'event_type' => $validated['event_type'],
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'conditions' => $this->conditionsFrom($validated),
            'timing' => $this->timingFrom($validated),
            'dedupe_policy' => $validated['dedupe_policy'],
            'sort_order' => $this->nextSortOrder(),
        ]);

        AutomationRuleAction::query()->create([
            'automation_rule_id' => $rule->id,
            'action_type' => ActionType::Email,
            'notification_template_id' => $validated['notification_template_id'],
            'config' => ['template_id' => (int) $validated['notification_template_id']],
            'sort_order' => 0,
            'is_enabled' => true,
        ]);

        return redirect()
            ->route('admin.notifications.edit')
            ->with('success', 'Kural eklendi.');
    }

    public function edit(AutomationRule $rule): View
    {
        $rule->load(['actions.template']);

        return $this->formView($rule);
    }

    public function update(Request $request, AutomationRule $rule): RedirectResponse
    {
        $validated = $this->validated($request);
        $rule->update([
            'name' => $validated['name'],
            'event_type' => $validated['event_type'],
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'conditions' => $this->conditionsFrom($validated),
            'timing' => $this->timingFrom($validated, $rule),
            'dedupe_policy' => $validated['dedupe_policy'],
        ]);

        $action = $rule->actions()->orderBy('sort_order')->first();
        if ($action === null) {
            AutomationRuleAction::query()->create([
                'automation_rule_id' => $rule->id,
                'action_type' => ActionType::Email,
                'notification_template_id' => $validated['notification_template_id'],
                'config' => ['template_id' => (int) $validated['notification_template_id']],
                'sort_order' => 0,
                'is_enabled' => true,
            ]);
        } else {
            $action->update([
                'notification_template_id' => $validated['notification_template_id'],
                'config' => array_merge((array) $action->config, [
                    'template_id' => (int) $validated['notification_template_id'],
                ]),
            ]);
        }

        return redirect()
            ->route('admin.notifications.edit')
            ->with('success', 'Kural kaydedildi.');
    }

    public function move(Request $request, AutomationRule $rule): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $ids = AutomationRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $index = array_search((int) $rule->id, $ids, true);
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index === false || ! isset($ids[$swapWith])) {
            return redirect()->route('admin.notifications.edit');
        }

        [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];
        $this->persistOrder($ids);

        return redirect()->route('admin.notifications.edit');
    }

    public function reorderByProcess(): RedirectResponse
    {
        $rules = AutomationRule::query()->orderBy('id')->get();
        $ordered = $rules->sortBy(function (AutomationRule $rule): int {
            return $rule->event_type instanceof EventType
                ? $rule->event_type->processSort()
                : $rule->id * 10;
        })->values();

        $this->persistOrder($ordered->pluck('id')->all());

        return redirect()
            ->route('admin.notifications.edit')
            ->with('success', 'Kurallar süreç sırasına alındı.');
    }

    private function formView(?AutomationRule $rule): View
    {
        return view('admin.notifications.rules-form', [
            'rule' => $rule,
            'templates' => NotificationTemplate::query()->orderBy('name')->get(),
            'eventTypes' => EventType::selectableCases(),
            'eventMeta' => EventType::formMeta(),
            'timingModes' => TimingMode::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $event = EventType::tryFrom((string) $request->input('event_type'));
        $allowedModes = $event?->allowedTimingModes() ?? [TimingMode::Immediate];
        $mode = TimingMode::tryFrom((string) $request->input('timing_mode'));

        $allowedPolicies = $event?->allowedDedupePolicies() ?? [DedupePolicy::Once];
        $sendAtRequired = $mode === TimingMode::AtSendAt;
        $offsetRequired = $event !== null && ! $event->isInstantaneous();
        $intervalRequired = $event !== null
            && ! $event->isInstantaneous()
            && DedupePolicy::tryFrom((string) $request->input('dedupe_policy')) === DedupePolicy::PerOccurrenceKey;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(array_column(EventType::selectableCases(), 'value'))],
            'is_enabled' => ['nullable', 'boolean'],
            'timing_mode' => ['required', Rule::in(array_map(fn (TimingMode $item): string => $item->value, $allowedModes))],
            'offset_days' => [$offsetRequired ? 'required' : 'nullable', 'integer', 'min:0', 'max:3650'],
            'interval_days' => [$intervalRequired ? 'required' : 'nullable', 'integer', 'min:1', 'max:365'],
            'send_at' => [$sendAtRequired ? 'required' : 'nullable', 'date_format:H:i'],
            'dedupe_policy' => ['required', Rule::in(array_map(fn (DedupePolicy $item): string => $item->value, $allowedPolicies))],
            'notification_template_id' => ['required', 'integer', 'exists:notification_templates,id'],
            'auto_renew' => ['nullable', 'in:,0,1'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function timingFrom(array $validated, ?AutomationRule $existing = null): array
    {
        $mode = TimingMode::from($validated['timing_mode']);
        $policy = DedupePolicy::tryFrom((string) $validated['dedupe_policy']);
        $interval = $policy === DedupePolicy::Once
            ? ($existing?->intervalDays() ?? 1)
            : max(1, (int) ($validated['interval_days'] ?? 1));

        return [
            'mode' => $mode->value,
            'offset_days' => (int) ($validated['offset_days'] ?? 0),
            'interval_days' => $interval,
            'send_at' => $validated['send_at'] ?? '10:00',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|null
     */
    private function conditionsFrom(array $validated): ?array
    {
        $event = EventType::tryFrom((string) $validated['event_type']);
        if ($event === null || ! $event->showsAutoRenewCondition()) {
            return null;
        }
        if (! array_key_exists('auto_renew', $validated) || $validated['auto_renew'] === null || $validated['auto_renew'] === '') {
            return null;
        }

        return ['auto_renew' => (int) $validated['auto_renew'] === 1];
    }

    private function nextSortOrder(): int
    {
        return (int) AutomationRule::query()->max('sort_order') + 10;
    }

    /**
     * @param  list<int>  $ids
     */
    private function persistOrder(array $ids): void
    {
        foreach (array_values($ids) as $index => $id) {
            AutomationRule::query()->whereKey($id)->update([
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }
}
