<?php

namespace App\Http\Controllers\Admin;

use App\Automation\EventType;
use App\Automation\JobRunner;
use App\Automation\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\AutomationJob;
use App\Models\Cari;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationJobController extends Controller
{
    public function index(Request $request): View
    {
        $query = AutomationJob::query()
            ->with(['rule', 'action', 'cari', 'subject'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type')->toString());
        }
        if ($request->filled('cari_id')) {
            $query->where('cari_id', (int) $request->input('cari_id'));
        }

        $jobs = $query->paginate(30)->withQueryString();
        $caris = Cari::query()
            ->whereIn('id', AutomationJob::query()->whereNotNull('cari_id')->distinct()->pluck('cari_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        return view('admin.notifications.jobs', [
            'jobs' => $jobs,
            'caris' => $caris,
            'statuses' => JobStatus::cases(),
            'eventTypes' => EventType::cases(),
            'filters' => [
                'status' => $request->input('status'),
                'event_type' => $request->input('event_type'),
                'cari_id' => $request->input('cari_id'),
            ],
        ]);
    }

    public function show(AutomationJob $job): View
    {
        $job->load(['rule', 'action.template', 'cari', 'subject', 'runs' => fn ($q) => $q->orderByDesc('id')]);

        return view('admin.notifications.job-show', compact('job'));
    }

    public function retry(AutomationJob $job, JobRunner $runner): RedirectResponse
    {
        if (! $runner->retry($job)) {
            return back()->with('error', 'Bu iş tekrar denenemiyor.');
        }

        return redirect()
            ->route('admin.notifications.jobs.show', $job)
            ->with('success', 'İş yeniden denendi.');
    }

    public function cancel(AutomationJob $job, JobRunner $runner): RedirectResponse
    {
        if (! $runner->cancel($job)) {
            return back()->with('error', 'Bu iş iptal edilemiyor.');
        }

        return redirect()
            ->route('admin.notifications.jobs.index')
            ->with('success', 'İş iptal edildi.');
    }

    public function reschedule(Request $request, AutomationJob $job, JobRunner $runner): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        if (! $runner->reschedule($job, Carbon::parse($validated['scheduled_at']))) {
            return back()->with('error', 'Yalnızca bekleyen işlerin tarihi değiştirilebilir.');
        }

        return redirect()
            ->route('admin.notifications.jobs.show', $job)
            ->with('success', 'Gönderim tarihi güncellendi.');
    }

    public function retrigger(AutomationJob $job, JobRunner $runner): RedirectResponse
    {
        $clone = $runner->retrigger($job);

        return redirect()
            ->route('admin.notifications.jobs.show', $clone)
            ->with('success', 'İş manuel olarak yeniden tetiklendi.');
    }
}
