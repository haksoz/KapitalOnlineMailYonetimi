<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationDefinition;
use App\Models\SalesInvoice;
use App\Services\InvoiceNotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationDefinitionController extends Controller
{
    public function edit(): View
    {
        $definitions = NotificationDefinition::query()->orderBy('id')->get();
        $sampleInvoices = SalesInvoice::query()
            ->with('customerCari:id,name,short_name')
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('admin.notifications.edit', compact('definitions', 'sampleInvoices'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'definitions' => ['required', 'array'],
            'definitions.*.id' => ['required', 'integer', 'exists:notification_definitions,id'],
            'definitions.*.is_enabled' => ['nullable', 'boolean'],
            'definitions.*.start_after_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'definitions.*.interval_days' => ['required', 'integer', 'min:1', 'max:365'],
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

        $invoice = SalesInvoice::query()
            ->with('customerCari')
            ->whereKey($validated['sales_invoice_id'])
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->first();

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
}
