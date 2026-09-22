<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Models\NotificationTemplate;
use App\Models\SalesInvoice;
use App\Services\InvoiceNotificationDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        $templates = NotificationTemplate::query()->orderBy('name')->get();

        return view('admin.notifications.templates', compact('templates'));
    }

    public function edit(NotificationTemplate $template, InvoiceNotificationDispatcher $dispatcher): View
    {
        return view('admin.notifications.template-edit', [
            'template' => $template,
            'sampleInvoices' => $this->sampleInvoicesForPreview($dispatcher),
            'mailFrom' => $this->resolvedMailFrom(),
        ]);
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $template->update($validated);

        return redirect()
            ->route('admin.notifications.templates.index')
            ->with('success', 'Şablon kaydedildi.');
    }

    public function preview(Request $request, NotificationTemplate $template, InvoiceNotificationDispatcher $dispatcher): View|JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
        ]);

        $invoice = $this->findSampleInvoice((int) $validated['sales_invoice_id']);
        if ($invoice === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Önizleme için vadesi kayıtlı, numarası olan bir fatura seçin.',
                ], 422);
            }

            return redirect()
                ->route('admin.notifications.templates.edit', $template)
                ->with('error', 'Önizleme için vadesi kayıtlı, numarası olan bir fatura seçin.');
        }

        if (isset($validated['subject']) && $validated['subject'] !== '') {
            $template->subject = $validated['subject'];
        }
        if (isset($validated['body']) && $validated['body'] !== '') {
            $template->body = $validated['body'];
        }

        $replacements = $dispatcher->replacements($invoice);
        $mailFrom = $this->resolvedMailFrom();
        $payload = [
            'definition_name' => $template->name,
            'from_name' => $mailFrom['name'],
            'from_address' => $mailFrom['address'],
            'to' => (string) ($invoice->customerCari?->email ?? ''),
            'invoice_number' => (string) ($invoice->our_invoice_number ?? ''),
            'subject' => $template->renderSubject($replacements),
            'body' => $template->renderBody($replacements),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('admin.notifications.preview', $payload);
    }

    public function sendTest(Request $request, NotificationTemplate $template, InvoiceNotificationDispatcher $dispatcher): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
        ]);

        $invoice = $this->findSampleInvoice((int) $validated['sales_invoice_id']);
        if ($invoice === null) {
            return redirect()
                ->route('admin.notifications.templates.edit', $template)
                ->with('error', 'Test için vadesi kayıtlı, numarası olan bir fatura seçin.');
        }

        try {
            $dispatcher->sendTest($template, $invoice, $validated['test_email']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.notifications.templates.edit', $template)
                ->with('error', 'Test e-postası gönderilemedi: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.notifications.templates.edit', $template)
            ->with('success', $template->name.' test maili gönderildi: '.$validated['test_email']);
    }

    /**
     * @return list<array{id: int, label: string, to: string, replacements: array<string, string>}>
     */
    private function sampleInvoicesForPreview(InvoiceNotificationDispatcher $dispatcher): array
    {
        return $this->sampleInvoiceQuery()
            ->get()
            ->map(function (SalesInvoice $invoice) use ($dispatcher): array {
                $payable = $invoice->payableAmountTl();
                $label = ($invoice->customerCari?->short_name ?: $invoice->customerCari?->name ?? 'Müşteri')
                    .' · '.$invoice->our_invoice_number
                    .' · vade '.($invoice->due_date?->format('d.m.Y') ?? '');
                if ($payable !== null) {
                    $label .= ' · '.number_format($payable, 2, ',', '.').' ₺ KDV dahil';
                }

                return [
                    'id' => (int) $invoice->id,
                    'label' => $label,
                    'to' => (string) ($invoice->customerCari?->email ?? ''),
                    'replacements' => $dispatcher->replacements($invoice),
                ];
            })
            ->values()
            ->all();
    }

    private function sampleInvoiceQuery(): Builder
    {
        return SalesInvoice::query()
            ->with(['customerCari:id,name,short_name,email', 'lines.pendingBilling.subscription.product'])
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->limit(100);
    }

    private function findSampleInvoice(int $id): ?SalesInvoice
    {
        return SalesInvoice::query()
            ->with(['customerCari', 'lines.pendingBilling.subscription.product'])
            ->whereKey($id)
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->first();
    }

    /**
     * @return array{name: string, address: string}
     */
    private function resolvedMailFrom(): array
    {
        MailSetting::applyToRuntime();

        return [
            'name' => (string) (config('mail.from.name') ?: ''),
            'address' => (string) (config('mail.from.address') ?: ''),
        ];
    }
}
