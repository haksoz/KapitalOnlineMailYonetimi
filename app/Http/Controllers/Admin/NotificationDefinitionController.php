<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Models\NotificationDefinition;
use App\Models\SalesInvoice;
use App\Services\InvoiceNotificationDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationDefinitionController extends Controller
{
    public function edit(InvoiceNotificationDispatcher $dispatcher): View
    {
        $definitions = NotificationDefinition::query()->orderBy('id')->get();
        $sampleInvoices = $this->sampleInvoicesForPreview($dispatcher);
        $mailFrom = $this->resolvedMailFrom();

        return view('admin.notifications.edit', compact('definitions', 'sampleInvoices', 'mailFrom'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'definitions' => ['required', 'array'],
            'definitions.*.id' => ['required', 'integer', 'exists:notification_definitions,id'],
            'definitions.*.is_enabled' => ['nullable', 'boolean'],
            'definitions.*.start_after_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'definitions.*.interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'definitions.*.send_at' => ['required', 'date_format:H:i'],
            'definitions.*.subject' => ['required', 'string', 'max:255'],
            'definitions.*.body' => ['required', 'string', 'max:20000'],
        ]);

        foreach ($validated['definitions'] as $row) {
            $definition = NotificationDefinition::query()->find($row['id']);
            if ($definition === null) {
                continue;
            }

            $definition->update([
                'is_enabled' => (int) ($row['is_enabled'] ?? 0) === 1,
                'start_after_days' => (int) $row['start_after_days'],
                'interval_days' => (int) $row['interval_days'],
                'send_at' => $row['send_at'].':00',
                'subject' => $row['subject'],
                'body' => $row['body'],
            ]);
        }

        return redirect()
            ->route('admin.notifications.edit')
            ->with('success', 'Bildiri ayarları kaydedildi.');
    }

    public function sendTest(Request $request, NotificationDefinition $notification_definition, InvoiceNotificationDispatcher $dispatcher): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
        ]);

        $invoice = $this->findSampleInvoice((int) $validated['sales_invoice_id']);

        if ($invoice === null) {
            return redirect()
                ->route('admin.notifications.edit')
                ->with('error', 'Test için vadesi kayıtlı, numarası olan bir fatura seçin.');
        }

        try {
            $dispatcher->sendTest($notification_definition, $invoice, $validated['test_email']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.notifications.edit')
                ->with('error', 'Test e-postası gönderilemedi: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.notifications.edit')
            ->with('success', $notification_definition->name . ' test maili gönderildi: ' . $validated['test_email']
                . ' (fatura ' . $invoice->our_invoice_number . ').');
    }

    public function preview(Request $request, NotificationDefinition $notification_definition, InvoiceNotificationDispatcher $dispatcher): View|JsonResponse|RedirectResponse
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
                ->route('admin.notifications.edit')
                ->with('error', 'Önizleme için vadesi kayıtlı, numarası olan bir fatura seçin.');
        }

        if (isset($validated['subject']) && $validated['subject'] !== '') {
            $notification_definition->subject = $validated['subject'];
        }
        if (isset($validated['body']) && $validated['body'] !== '') {
            $notification_definition->body = $validated['body'];
        }

        $payload = $this->previewPayload($notification_definition, $invoice, $dispatcher);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('admin.notifications.preview', $payload);
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
            ->with([
                'customerCari:id,name,short_name,email',
                'lines.pendingBilling.subscription:id,vat_rate',
            ])
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
            ->with(['customerCari', 'lines.pendingBilling.subscription'])
            ->whereKey($id)
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->first();
    }

    /**
     * @return array{definition_name: string, from_name: string, from_address: string, to: string, invoice_number: string, subject: string, body: string}
     */
    private function previewPayload(NotificationDefinition $definition, SalesInvoice $invoice, InvoiceNotificationDispatcher $dispatcher): array
    {
        $mailFrom = $this->resolvedMailFrom();
        $replacements = $dispatcher->replacements($invoice);

        return [
            'definition_name' => $definition->name,
            'from_name' => $mailFrom['name'],
            'from_address' => $mailFrom['address'],
            'to' => (string) ($invoice->customerCari?->email ?? ''),
            'invoice_number' => (string) ($invoice->our_invoice_number ?? ''),
            'subject' => $definition->renderSubject($replacements),
            'body' => $definition->renderBody($replacements),
        ];
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
