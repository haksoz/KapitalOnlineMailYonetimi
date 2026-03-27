<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PendingBillingAdminController extends Controller
{
    public function editSale(PendingBilling $pending_billing): View
    {
        $pending_billing->load([
            'subscription.customerCari',
            'subscription.product',
            'salesInvoiceLine.salesInvoice',
        ]);

        return view('admin.pending-billings.edit-sale', [
            'pendingBilling' => $pending_billing,
        ]);
    }

    public function updateSale(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'actual_satis_tl' => ['required', 'numeric'],
        ]);

        $newActual = (float) str_replace(',', '.', (string) $validated['actual_satis_tl']);

        DB::transaction(function () use ($pending_billing, $newActual): void {
            $pending_billing->actual_satis_tl = $newActual;

            if ($pending_billing->expected_satis_tl !== null && $pending_billing->expected_satis_tl !== '') {
                $pending_billing->fee_difference_tl = (float) $pending_billing->expected_satis_tl - $newActual;
            } else {
                $pending_billing->fee_difference_tl = null;
            }

            $pending_billing->save();

            $line = $pending_billing->salesInvoiceLine;
            if ($line) {
                $line->line_amount_tl = $newActual;
                $line->save();

                /** @var SalesInvoice $invoice */
                $invoice = $line->salesInvoice;
                if ($invoice) {
                    $invoice->total_amount_tl = $invoice->lines()->sum('line_amount_tl');
                    $invoice->save();
                }
            }
        });

        return redirect()
            ->route('pending-billings.index', array_merge(
                request()->only('status', 'customer_cari_id', 'period_year', 'period_month'),
                ['status' => request('status', 'invoiced')]
            ))
            ->with('success', 'Kesinleşen satış tutarı güncellendi.');
    }

    public function editExpectedPurchase(PendingBilling $pending_billing): View|RedirectResponse
    {
        if ($pending_billing->status !== PendingBilling::STATUS_INVOICED) {
            abort(404);
        }

        if ($pending_billing->actual_alis_tl !== null && $pending_billing->actual_alis_tl !== '') {
            return redirect()
                ->route('pending-billings.index', array_merge(
                    request()->only('status', 'customer_cari_id', 'period_year', 'period_month', 'has_supplier_invoice', 'per_page'),
                    ['status' => 'invoiced']
                ))
                ->with('error', 'Alış kesinleştiği için beklenen alış düzenlenemez.');
        }

        $pending_billing->load([
            'subscription.customerCari',
            'subscription.product',
            'salesInvoiceLine.salesInvoice',
        ]);

        return view('admin.pending-billings.edit-expected-purchase', [
            'pendingBilling' => $pending_billing,
        ]);
    }

    public function updateExpectedPurchase(Request $request, PendingBilling $pending_billing): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        if ($pending_billing->status !== PendingBilling::STATUS_INVOICED) {
            abort(404);
        }

        if ($pending_billing->actual_alis_tl !== null && $pending_billing->actual_alis_tl !== '') {
            return redirect()
                ->route('pending-billings.index', array_merge(
                    request()->only('status', 'customer_cari_id', 'period_year', 'period_month', 'has_supplier_invoice', 'per_page'),
                    ['status' => 'invoiced']
                ))
                ->with('error', 'Alış kesinleştiği için beklenen alış düzenlenemez.');
        }

        $validated = $request->validate([
            'expected_alis_tl' => ['required', 'numeric', 'min:0'],
        ]);

        $newExpectedAlis = (float) str_replace(',', '.', (string) $validated['expected_alis_tl']);

        DB::transaction(function () use ($pending_billing, $newExpectedAlis): void {
            $pending_billing->load('subscription');
            $subscription = $pending_billing->subscription;

            $pending_billing->expected_alis_tl = $newExpectedAlis;

            $satisFromAlis = null;
            if ($subscription && (float) $subscription->usd_birim_alis > 0 && $subscription->usd_birim_satis !== null) {
                $satisFromAlis = $newExpectedAlis * ((float) $subscription->usd_birim_satis / (float) $subscription->usd_birim_alis);
            }
            $pending_billing->expected_satis_tl = $satisFromAlis;

            $actualSatis = null;
            $line = $pending_billing->salesInvoiceLine;
            if ($line && $line->line_amount_tl !== null && $line->line_amount_tl !== '') {
                $actualSatis = (float) $line->line_amount_tl;
            } elseif ($pending_billing->actual_satis_tl !== null && $pending_billing->actual_satis_tl !== '') {
                $actualSatis = (float) $pending_billing->actual_satis_tl;
            }

            if ($satisFromAlis !== null && $actualSatis !== null) {
                $pending_billing->fee_difference_tl = $satisFromAlis - $actualSatis;
            } else {
                $pending_billing->fee_difference_tl = null;
            }

            $pending_billing->save();
        });

        return redirect()
            ->route('pending-billings.index', array_merge(
                request()->only('status', 'customer_cari_id', 'period_year', 'period_month', 'has_supplier_invoice', 'per_page'),
                ['status' => request('status', 'invoiced')]
            ))
            ->with('success', 'Beklenen alış ve buna bağlı beklenen satış güncellendi.');
    }
}

