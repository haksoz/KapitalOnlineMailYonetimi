<?php

namespace Tests\Feature;

use App\Automation\QuotePlaceholders;
use App\Models\Cari;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\QuoteService;
use App\Services\RenewalWorkTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'Europe/Istanbul'));
        Http::fake(['http://gotenberg.test/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_detection_obeys_all_six_commitment_and_auto_renew_combinations(): void
    {
        foreach (Quote::COMMITMENTS as $commitment) {
            foreach ([true, false] as $autoRenew) {
                $this->subscription(['taahhut_tipi' => $commitment, 'auto_renew' => $autoRenew]);
            }
        }
        $service = app(RenewalWorkTaskService::class);
        $this->assertSame(5, $service->detect());
        $this->assertSame(0, $service->detect());
        $this->assertSame(5, WorkTask::count());
        $excluded = Subscription::where('taahhut_tipi', 'monthly_no_commitment')->where('auto_renew', true)->first();
        $this->assertDatabaseMissing('work_tasks', ['subject_id' => $excluded->id]);
        $this->assertSame(5, \App\Models\WorkTaskActivity::count());
    }

    public function test_detection_catches_missed_days_and_excludes_inactive_outside_window_and_past(): void
    {
        foreach (['2026-11-04', '2026-11-08', '2026-11-19', '2026-11-20', '2026-11-03'] as $end) {
            $this->subscription(['bitis_tarihi' => $end]);
        }
        $this->subscription(['durum' => 'cancelled']);
        $this->subscription(['durum' => 'pending']);
        $this->subscription(['bitis_tarihi' => null]);
        $this->artisan('work-tasks:detect-renewals')->expectsOutput('3 yenileme işi oluşturuldu.')->assertSuccessful();
        $this->assertSame(3, WorkTask::count());
    }

    public function test_snapshot_survives_renewal_and_new_period_gets_its_own_task(): void
    {
        $subscription = $this->subscription();
        app(RenewalWorkTaskService::class)->detect();
        $task = WorkTask::firstOrFail();
        $snapshot = $task->snapshot;
        $subscription->update(['bitis_tarihi' => '2027-11-19', 'quantity' => 40, 'usd_birim_satis' => 20]);
        $this->assertSame($snapshot, $task->fresh()->snapshot);
        $this->assertTrue($task->fresh()->isOpen());
        $this->assertSame(0, app(RenewalWorkTaskService::class)->detect());
        $this->assertSame(1, app(RenewalWorkTaskService::class)->detect(Carbon::parse('2027-11-04', 'Europe/Istanbul')));
        $this->assertSame(2, WorkTask::count());
    }

    public function test_guests_cannot_access_or_modify_tasks(): void
    {
        $task = $this->task();
        $this->get(route('work-tasks.index'))->assertRedirect(route('login'));
        $this->get(route('work-tasks.show', $task))->assertRedirect(route('login'));
        $this->patch(route('work-tasks.update', $task), ['action' => 'cancel', 'note' => 'x'])->assertRedirect(route('login'));
        $this->post(route('work-tasks.detect'))->assertRedirect(route('login'));
    }

    public function test_assignment_follow_up_filters_and_history_are_available(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $task = $this->task();
        $this->actingAs($user)->get(route('work-tasks.index'))->assertOk()->assertSee('İş Takip');
        $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('Yeni dönem fiyat kontrolü');
        $this->get(route('dashboard'))->assertOk()->assertSee('1 açık iş')->assertSee($task->title);
        $this->get(route('work-tasks.index', ['timing' => 'today']))->assertSee('İşi aç')->assertViewHas('counts', fn ($counts) => $counts['today'] === 1 && $counts['overdue'] === 0);
        $this->get(route('work-tasks.index', ['timing' => 'overdue']))->assertDontSee('İşi aç');
        $this->patch(route('work-tasks.update', $task), ['action' => 'assign', 'assigned_to' => $user->id])->assertSessionHasNoErrors();
        $this->get(route('work-tasks.index', ['owner' => 'mine']))->assertSee($task->title);
        $this->get(route('work-tasks.index', ['owner' => 'unassigned']))->assertDontSee('İşi aç');
        $this->patch(route('work-tasks.update', $task), ['action' => 'note', 'note' => 'Cuma günü aranacak', 'next_follow_up' => '2026-11-06'])->assertSessionHasNoErrors();
        $this->get(route('work-tasks.index', ['timing' => 'today']))->assertDontSee('İşi aç');
        $this->assertSame('2026-11-06', $task->fresh()->next_follow_up->toDateString());
        $this->get(route('work-tasks.show', $task))->assertSee('Cuma günü aranacak');
        $this->assertSame(3, $task->activities()->count());
    }

    public function test_cost_confirmation_is_required_and_quote_form_prefills_context(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $task = $this->task();
        $this->actingAs($user)->get(route('quotes.create', ['work_task_id' => $task->id]))->assertRedirect(route('work-tasks.show', $task));
        $this->patch(route('work-tasks.update', $task), ['action' => 'pricing', 'verified_cost' => 9, 'proposed_sale' => 12])->assertSessionHasErrors('cost_confirmed');
        $this->assertNull($task->fresh()->cost_checked_at);
        $this->review($task);
        $this->assertSame($user->id, $task->fresh()->cost_checked_by);
        $this->get(route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]))
            ->assertOk()->assertSee('Otomatik yenileme fiyat bildirimi')
            ->assertViewHas('formConfig', fn ($config) => $config['lines'][0]['quantity'] === 20 && $config['lines'][0]['birim_satis'] === '12.0000');
    }

    public function test_linked_quote_validates_customer_product_currency_and_keeps_verified_cost(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $task = $this->task();
        $this->actingAs($user);
        $payload = $this->payload($task);
        $this->post(route('quotes.store'), $payload)->assertSessionHasErrors('work_task_id');
        $this->review($task);
        $other = Cari::create(['name' => 'Başka müşteri', 'cari_type' => 'customer']);
        $this->post(route('quotes.store'), array_replace($payload, ['customer_cari_id' => $other->id]))->assertSessionHasErrors('work_task_id');
        $otherProduct = Product::create(['name' => 'Başka ürün', 'currency' => 'USD', 'satis_usd_monthly_commitment' => 12]);
        $bad = $payload;
        $bad['items'][0]['product_id'] = $otherProduct->id;
        $this->post(route('quotes.store'), $bad)->assertSessionHasErrors('items');
        Product::findOrFail($task->snapshot['product_id'])->update(['currency' => 'TRY']);
        $this->post(route('quotes.store'), $payload)->assertSessionHasErrors('items');
        Product::findOrFail($task->snapshot['product_id'])->update(['currency' => 'USD']);
        $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
        $quote = Quote::firstOrFail();
        $this->assertSame($task->id, $quote->work_task_id);
        $this->assertSame($task->snapshot, $quote->renewal_context);
        $this->assertSame('9.0000', $quote->items->first()->birim_alis);
        $this->assertSame('notification_pending', $task->fresh()->status);
        $this->get(route('quotes.show', $quote))->assertOk()->assertSee('İş #'.$task->id);
        $this->get(route('quotes.edit', $quote))->assertOk();
        $this->patch(route('quotes.update', $quote), array_replace($payload, ['customer_cari_id' => $other->id, 'work_task_id' => null]))->assertSessionHasErrors('work_task_id');
        $this->assertSame($task->id, $quote->fresh()->work_task_id);
        $this->get(route('quotes.customer', $quote))->assertOk()->assertDontSee('aboneliğinizin mevcut dönemi')->assertDontSee('2.880,00 USD');
        $pdf = view('quotes.pdf', ['quote' => $quote])->render();
        $this->assertStringNotContainsString('2.880,00 USD', $pdf);
        $this->assertStringNotContainsString('aboneliğinizin mevcut dönemi', $pdf);
        $mail = QuotePlaceholders::renderPreview('{yenileme_aciklamasi}\n{kalemler}', $quote);
        $this->assertStringContainsString('otomatik yenileme açıktır', $mail);
        $this->assertStringNotContainsString('2.880,00 USD', $mail);
        $this->assertStringNotContainsString('aboneliğinizin mevcut dönemi', view('quotes.mail', ['quote' => $quote])->render());
        $this->assertStringNotContainsString('9,00 USD', $mail);
        $this->assertStringNotContainsString('aboneliğinizin mevcut dönemi', QuotePlaceholders::forQuote($quote)['{kalemler}']);
    }

    public function test_optional_quote_conversion_preserves_work_and_period_context(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $task = $this->task(['auto_renew' => false]);
        $this->actingAs($user);
        $this->review($task);
        $data = $this->payload($task);
        $data['type'] = 'optional';
        $data['items'] = [['product_id' => $task->snapshot['product_id'], 'options' => [
            'monthly_commitment' => ['enabled' => '1', 'birim_satis' => 12],
            'monthly_no_commitment' => ['enabled' => '1', 'birim_satis' => 14],
        ]]];
        $this->post(route('quotes.store'), $data)->assertSessionHasNoErrors();
        $source = Quote::firstOrFail();
        $this->get(route('quotes.customer', $source))->assertDontSee('Mevcut lisans adedine göre')->assertDontSee('aboneliğinizin mevcut dönemi')->assertDontSee('12 aylık karşılık');
        $firm = app(QuoteService::class)->convert($source, ['vat_rate' => 20, 'lines' => [[
            'include' => true, 'quote_item_id' => $source->items->first()->id, 'taahhut_tipi' => 'monthly_commitment', 'quantity' => 20,
        ]]], $user->id);
        $this->assertSame($task->id, $firm->work_task_id);
        $this->assertSame($source->renewal_context, $firm->renewal_context);
        $this->assertSame('9.0000', $firm->items->first()->birim_alis);
        $this->assertSame(2, $task->quotes()->count());
    }

    public function test_closed_task_cannot_be_modified_or_used_for_new_quotes(): void
    {
        $task = $this->task();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->patch(route('work-tasks.update', $task), ['action' => 'cancel'])->assertSessionHasErrors('task');
        $this->patch(route('work-tasks.update', $task), ['action' => 'cancel', 'note' => 'Müşteri yenilemeyecek'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $task->fresh()->status);
        $this->patch(route('work-tasks.update', $task), ['action' => 'note', 'note' => 'değişiklik'])->assertSessionHasErrors('task');
        $this->post(route('quotes.store'), $this->payload($task))->assertSessionHasErrors('work_task_id');
        $this->assertSame(0, app(RenewalWorkTaskService::class)->detect());
    }

    public function test_renewal_cannot_be_completed_manually_without_subscription_record(): void
    {
        $task = $this->task();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $complete = ['action' => 'complete', 'note' => 'Yenileme ve fiyatlar doğrulandı'];
        $this->patch(route('work-tasks.update', $task), $complete)->assertSessionHasErrors('task');
        $this->review($task);
        $this->patch(route('work-tasks.update', $task), ['action' => 'stage', 'status' => 'renewal_pending'])->assertSessionHasErrors('task');
        $this->patch(route('work-tasks.update', $task), ['action' => 'notify', 'note' => '04.11.2026 telefon ile bildirildi'])->assertSessionHasNoErrors();
        $this->assertSame('renewal_pending', $task->fresh()->status);
        $this->patch(route('work-tasks.update', $task), $complete)->assertSessionHasErrors('task');
        $task->subject->update(['bitis_tarihi' => '2027-11-19']);
        $this->patch(route('work-tasks.update', $task), $complete)->assertSessionHasErrors('task');
        $task->subject->update(['usd_birim_alis' => 9, 'usd_birim_satis' => 12]);
        $this->patch(route('work-tasks.update', $task), $complete)->assertSessionHasErrors('task');
        $this->assertSame('renewal_pending', $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_unsuccessful_quote_email_does_not_mark_customer_as_notified(): void
    {
        $task = $this->task();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->review($task);
        $this->post(route('quotes.store'), $this->payload($task))->assertSessionHasNoErrors();
        $quote = Quote::firstOrFail();
        \App\Models\AutomationRule::where('legacy_key', 'quote_renewal_firm_sent')->update(['is_enabled' => false]);
        $this->post(route('quotes.send', $quote), ['email' => 'customer@example.test'])->assertSessionHasNoErrors();
        $this->assertNull($task->fresh()->notified_at);
        $this->assertSame('notification_pending', $task->fresh()->status);
    }

    public function test_successful_delivery_and_later_retry_update_the_work_task(): void
    {
        $task = $this->task(['auto_renew' => false]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->review($task);
        $this->post(route('quotes.store'), $this->payload($task))->assertSessionHasNoErrors();
        $quote = Quote::firstOrFail();
        $this->post(route('quotes.send', $quote), ['email' => 'customer@example.test'])->assertSessionHasNoErrors();
        $this->assertNotNull($task->fresh()->notified_at);
        $this->assertSame('response_pending', $task->fresh()->status);
        $this->assertSame('2026-11-07', $task->fresh()->next_follow_up->toDateString());
        $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('Müşteri yanıtını işle')->assertSee('Gönderim:');
        $this->get(route('quotes.show', $quote))->assertOk()->assertSee('Onaylandı olarak işaretle')->assertSee('Reddedildi olarak işaretle');
        $this->post(route('quotes.approve', $quote))->assertSessionHasNoErrors();
        $this->assertSame('renewal_pending', $task->fresh()->status);

        $second = $this->task();
        $this->review($second);
        $this->post(route('quotes.store'), $this->payload($second))->assertSessionHasNoErrors();
        $quote2 = Quote::latest('id')->firstOrFail();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['http://gotenberg.test/*' => Http::response('down', 500)]);
        $this->post(route('quotes.send', $quote2), ['email' => 'second@example.test'])->assertSessionHasNoErrors();
        $this->assertNull($second->fresh()->notified_at);
        $job = \App\Models\AutomationJob::where('subject_id', $quote2->id)->where('subject_type', $quote2->getMorphClass())->latest('id')->firstOrFail();
        $this->assertSame(\App\Automation\JobStatus::Failed, $job->status);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['http://gotenberg.test/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);
        app(\App\Automation\JobRunner::class)->retry($job);
        $this->assertSame(\App\Automation\JobStatus::Succeeded, $job->fresh()->status);
        $this->assertNotNull($second->fresh()->notified_at);
        $this->assertSame('renewal_pending', $second->fresh()->status);
        $this->post(route('quotes.reject', $quote2))->assertSessionHasNoErrors();
        $this->assertSame('rejected', $quote2->fresh()->status);
        $this->assertSame('pricing', $second->fresh()->status);
        $this->assertFalse($second->hasBlockingQuote());
        $this->assertTrue($second->activities()->where('description', 'like', '%Reddedildi%')->exists());
    }

    public function test_price_control_is_locked_until_the_offer_is_closed(): void
    {
        $task = $this->task();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->review($task);
        $this->post(route('quotes.store'), $this->payload($task))->assertSessionHasNoErrors();
        $quote = Quote::firstOrFail();
        $pricing = ['action' => 'pricing', 'verified_cost' => 10, 'proposed_sale' => 12, 'cost_confirmed' => '1'];
        foreach (['draft', 'sent', 'approved'] as $status) {
            $quote->update(['status' => $status]);
            $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('fiyat kontrolü kilitli')->assertDontSee('name="verified_cost"', false);
            $this->patch(route('work-tasks.update', $task), $pricing)->assertSessionHasErrors('task');
            $this->assertSame('9.0000', $task->fresh()->verified_cost);
            $this->assertSame('9.00', Product::findOrFail($task->snapshot['product_id'])->alis_usd_monthly_commitment);
        }
        foreach (['rejected', 'cancelled', 'expired'] as $status) {
            $quote->update(['status' => $status]);
            $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('name="verified_cost"', false)->assertDontSee('fiyat kontrolü kilitli');
            $this->patch(route('work-tasks.update', $task), $pricing)->assertSessionHasNoErrors();
            $this->assertSame('10.0000', $task->fresh()->verified_cost);
        }
    }

    public function test_pricing_suggests_sale_from_existing_profit_and_preserves_saved_values(): void
    {
        $task = $this->task(['usd_birim_alis' => 100, 'usd_birim_satis' => 125]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get(route('work-tasks.show', $task))->assertOk()
            ->assertSee('Kâr oranı')->assertSee('25,00%')
            ->assertViewHas('initialCost', '8.00')->assertViewHas('initialSale', '10.00')
            ->assertSee('Mevcut kâr oranını uygula');
        $this->review($task);
        $this->get(route('work-tasks.show', $task))
            ->assertViewHas('initialCost', '9.0000')->assertViewHas('initialSale', '12.00')
            ->assertSee('Yeni dönem')->assertSee('33,33%');
    }

    public function test_pricing_handles_zero_cost_and_uses_exact_original_ratio(): void
    {
        $this->assertSame('11.4286', \App\Services\QuoteMath::saleKeepingProfit('8', '7', '10'));
        $this->assertSame('42.86', \App\Services\QuoteMath::profitPercentage('7', '10'));
        $this->assertSame('-20.00', \App\Services\QuoteMath::profitPercentage('100', '80'));
        $task = $this->task(['usd_birim_alis' => 0, 'usd_birim_satis' => 10]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get(route('work-tasks.show', $task))->assertOk()
            ->assertViewHas('existingProfitRate', null)->assertViewHas('initialSale', '11.00')
            ->assertSee('Mevcut alış sıfır veya eksik');
    }

    public function test_verified_cost_updates_only_the_relevant_product_cost_and_records_history(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);
        foreach ([
            'monthly_commitment' => 'alis_usd_monthly_commitment',
            'monthly_no_commitment' => 'alis_usd_monthly_no_commitment',
            'annual_commitment' => 'alis_usd_yearly_commitment',
        ] as $commitment => $field) {
            $task = $this->task(['taahhut_tipi' => $commitment, 'auto_renew' => false]);
            $product = Product::findOrFail($task->snapshot['product_id']);
            $product->update(['alis_usd_monthly_commitment' => 8, 'alis_usd_monthly_no_commitment' => 10, 'alis_usd_yearly_commitment' => 90]);
            $before = $product->fresh()->getAttributes();
            $historyCount = $product->priceHistories()->count();
            $this->review($task);
            $product->refresh();
            $this->assertSame('9.00', $product->$field);
            foreach ($before as $key => $value) {
                if (str_starts_with($key, 'alis_usd_') && $key !== $field || str_starts_with($key, 'satis_usd_')) {
                    $this->assertEquals($value, $product->getRawOriginal($key));
                }
            }
            $this->assertSame($historyCount + 1, $product->priceHistories()->count());
            $history = $product->priceHistories()->latest('id')->firstOrFail();
            $this->assertSame($field, $history->field_name);
            $this->assertEquals($user->id, $history->changed_by);
            $this->assertSame('9.0000', $history->new_value);
            $this->assertSame('7.0000', $task->subject->usd_birim_alis);
            $this->assertSame('7.0000', $task->snapshot['cost']);
            $this->review($task);
            $this->assertSame($historyCount + 1, $product->priceHistories()->count());
        }
    }

    public function test_product_currency_mismatch_rolls_back_price_confirmation(): void
    {
        $task = $this->task();
        Product::findOrFail($task->snapshot['product_id'])->update(['currency' => 'TRY']);
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->patch(route('work-tasks.update', $task), ['action' => 'pricing', 'verified_cost' => 9, 'proposed_sale' => 12, 'cost_confirmed' => '1'])
            ->assertSessionHasErrors('task');
        $this->assertNull($task->fresh()->cost_checked_at);
        $this->assertSame('8.00', Product::findOrFail($task->snapshot['product_id'])->alis_usd_monthly_commitment);
    }

    public function test_normal_and_renewal_quotes_use_separate_templates_and_preview_samples(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN]));
        foreach (['firm', 'optional'] as $type) {
            $task = $this->task();
            $this->review($task);
            $payload = $this->payload($task);
            $payload['type'] = $type;
            if ($type === 'optional') {
                $payload['items'] = [['product_id' => $task->snapshot['product_id'], 'options' => ['monthly_commitment' => ['enabled' => '1', 'birim_satis' => 12]]]];
            }
            $normalTemplate = \App\Models\NotificationTemplate::where('legacy_key', 'quote_'.$type.'_sent')->firstOrFail();
            $renewalTemplate = \App\Models\NotificationTemplate::where('legacy_key', 'quote_renewal_'.$type.'_sent')->firstOrFail();
            $normalTemplate->update(['body' => 'NORMAL-ONLY {kalemler}']);
            $renewalTemplate->update(['body' => 'RENEWAL-ONLY {yenileme_aciklamasi} {kalemler}']);
            $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
            $renewal = Quote::latest('id')->firstOrFail();
            unset($payload['work_task_id']);
            $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
            $normal = Quote::latest('id')->firstOrFail();
            $this->assertSame('quote.renewal_'.$type.'_sent', QuotePlaceholders::eventFor($renewal)->value);
            $this->assertSame('quote.'.$type.'_sent', QuotePlaceholders::eventFor($normal)->value);
            foreach ([$normalTemplate->id => $normal, $renewalTemplate->id => $renewal] as $templateId => $sample) {
                $this->get(route('admin.notifications.templates.edit', $templateId))->assertOk()->assertViewHas('previewSamples', fn ($samples) => array_column($samples, 'id') === [$sample->id]);
            }
            $this->postJson(route('admin.notifications.templates.preview', $renewalTemplate), ['quote_id' => $normal->id])->assertStatus(422);
            $preview = $this->postJson(route('admin.notifications.templates.preview', $renewalTemplate), ['quote_id' => $renewal->id])->assertOk();
            $this->assertStringContainsString('aboneliğinizin mevcut dönemi', $preview->json('body'));
            $plain = clone $renewal;
            $plain->renewal_context = null;
            $this->assertSame(view('quotes.pdf', ['quote' => $plain])->render(), view('quotes.pdf', ['quote' => $renewal])->render());
            foreach ([$normal, $renewal] as $quote) {
                $this->post(route('quotes.send', $quote), ['email' => 'customer@example.test'])->assertSessionHasNoErrors();
                $jobs = \App\Models\AutomationJob::where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)->get();
                $this->assertCount(1, $jobs);
                $this->assertSame(QuotePlaceholders::eventFor($quote), $jobs->first()->event_type);
                $this->assertSame(\App\Automation\JobStatus::Succeeded, $jobs->first()->status);
                $html = \Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getHtmlBody();
                $this->assertStringContainsString($quote->renewal_context ? 'RENEWAL-ONLY' : 'NORMAL-ONLY', $html);
                $this->assertStringNotContainsString($quote->renewal_context ? 'NORMAL-ONLY' : 'RENEWAL-ONLY', $html);
            }
        }
    }

    public function test_active_quote_blocks_new_quotes_but_closed_offers_allow_another(): void
    {
        $task = $this->task();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->review($task);
        $payload = $this->payload($task);
        $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
        $quote = Quote::firstOrFail();
        foreach (['draft', 'sent', 'approved'] as $status) {
            $quote->update(['status' => $status]);
            $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('Devam eden teklif var.')->assertDontSee(route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]));
            $this->get(route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]))->assertRedirect(route('work-tasks.show', $task));
            $this->post(route('quotes.store'), $payload)->assertSessionHasErrors('work_task_id');
            $this->assertSame(1, $task->quotes()->count());
        }
        foreach (['rejected', 'cancelled', 'expired'] as $status) {
            $quote->update(['status' => $status]);
            $this->get(route('work-tasks.show', $task))->assertOk()->assertSee(route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]));
            $this->get(route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]))->assertOk();
        }
        $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $task->quotes()->count());
    }

    public function test_existing_subscription_records_renewal_without_advancing_end_date(): void
    {
        [$task, $quote] = $this->approvedSubscriptionQuote();
        $item = $quote->items->first();
        $source = $task->subject;
        $end = $source->bitis_tarihi->toDateString();
        $this->get(route('quotes.show', $quote))->assertOk()->assertSee('Abonelik işlemini kaydet');
        $this->get(route('work-tasks.show', $task))->assertOk()->assertSee('Mevcut abonelik yenilendi')->assertSee('Yeni abonelik açıldı');
        $this->get(route('subscriptions.edit', ['subscription' => $source, 'quote_item_id' => $item->id]))
            ->assertOk()->assertViewHas('subscription', fn ($subscription) => $subscription->auto_renew && $subscription->usd_birim_alis === '9.0000' && $subscription->bitis_tarihi->toDateString() === $end);
        $data = $this->subscriptionPayload($task, $item);
        $this->patch(route('subscriptions.update', $source), $data)->assertSessionHasNoErrors()->assertRedirect(route('subscriptions.show', $source));
        $this->assertSame($end, $source->fresh()->bitis_tarihi->toDateString());
        $this->assertTrue($source->fresh()->auto_renew);
        $this->assertSame('9.0000', $source->fresh()->usd_birim_alis);
        $this->assertSame('12.0000', $source->fresh()->usd_birim_satis);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame(1, Subscription::count());
        $this->assertDatabaseHas('quote_subscription_records', ['quote_item_id' => $item->id, 'subscription_id' => $source->id, 'mode' => 'existing']);
        $this->get(route('quotes.show', $quote))->assertOk()->assertSee('Aboneliği aç')->assertDontSee('Abonelik işlemini kaydet');
        $this->patch(route('subscriptions.update', $source), $data)->assertSessionHasErrors('quote_item_id');
        $this->assertSame(1, \App\Models\QuoteSubscriptionRecord::count());
    }

    public function test_new_subscription_is_independent_and_keeps_only_a_historical_link(): void
    {
        [$task, $quote] = $this->approvedSubscriptionQuote();
        $item = $quote->items->first();
        $source = $task->subject;
        $before = $source->getAttributes();
        $this->get(route('subscriptions.create', ['quote_item_id' => $item->id]))->assertOk()
            ->assertViewHas('quoteDefaults', fn ($defaults) => (int) $defaults['customer_cari_id'] === (int) $task->customer_cari_id && $defaults['usd_birim_satis'] === '12.0000')
            ->assertSee('Yeni sözleşme');
        $data = $this->subscriptionPayload($task, $item);
        $this->post(route('subscriptions.store'), $data)->assertSessionHasErrors('quote_item_id');
        $this->assertSame(1, Subscription::count());
        $data['sozlesme_no'] = 'YENI-SOZLESME-100';
        $data['baslangic_tarihi'] = '2026-11-19';
        $data['bitis_tarihi'] = '';
        $this->post(route('subscriptions.store'), $data)->assertSessionHasNoErrors();
        $new = Subscription::latest('id')->firstOrFail();
        $this->assertNotSame($source->id, $new->id);
        $this->assertSame($before, $source->fresh()->getAttributes());
        $this->assertSame('YENI-SOZLESME-100', $new->sozlesme_no);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertDatabaseHas('quote_subscription_records', ['subscription_id' => $new->id, 'previous_subscription_id' => $source->id, 'mode' => 'new']);
        $this->get(route('subscriptions.show', $new))->assertOk()->assertSee($quote->quote_number)->assertSee('Önceki abonelik:');
        $this->post(route('subscriptions.store'), $data)->assertSessionHasErrors('quote_item_id');
        $this->assertSame(2, Subscription::count());
    }

    public function test_subscription_completion_rejects_unapproved_quotes_wrong_prices_and_disabled_renewal(): void
    {
        [$task, $quote] = $this->approvedSubscriptionQuote();
        $item = $quote->items->first();
        $data = $this->subscriptionPayload($task, $item);
        $quote->update(['status' => 'sent']);
        $this->get(route('subscriptions.create', ['quote_item_id' => $item->id]))->assertSessionHasErrors('quote_item_id');
        $this->patch(route('subscriptions.update', $task->subject), $data)->assertSessionHasErrors('quote_item_id');
        $quote->update(['status' => 'approved']);
        $data['usd_birim_satis'] = 99;
        $this->patch(route('subscriptions.update', $task->subject), $data)->assertSessionHasErrors('quote_item_id');
        $data['usd_birim_satis'] = 12;
        $data['auto_renew'] = false;
        $this->patch(route('subscriptions.update', $task->subject), $data)->assertSessionHasErrors('quote_item_id');
        $this->assertSame('renewal_pending', $task->fresh()->status);
        $this->assertSame('7.0000', $task->subject->fresh()->usd_birim_alis);
        $this->assertSame(0, \App\Models\QuoteSubscriptionRecord::count());
    }

    public function test_multi_line_quote_completes_the_work_only_after_all_subscriptions_are_recorded(): void
    {
        [$task, $quote] = $this->approvedSubscriptionQuote(true);
        foreach ($quote->items as $index => $item) {
            $data = $this->subscriptionPayload($task, $item);
            $data['sozlesme_no'] = 'YENI-'.$item->id;
            $this->post(route('subscriptions.store'), $data)->assertSessionHasNoErrors();
            $this->assertSame($index === 0 ? 'renewal_pending' : 'completed', $task->fresh()->status);
        }
        $this->assertSame(2, $quote->subscriptionRecords()->count());
    }

    public function test_existing_renewal_on_its_end_day_keeps_date_for_scheduler_and_records_quantity(): void
    {
        [$task, $quote] = $this->approvedSubscriptionQuote();
        $source = $task->subject;
        $source->update(['quantity' => 5, 'bitis_tarihi' => '2026-11-04']);
        $item = $quote->items->first();
        $data = $this->subscriptionPayload($task, $item);
        $this->patch(route('subscriptions.update', $source), $data)->assertSessionHasNoErrors();
        $this->assertSame('2026-11-04', $source->fresh()->bitis_tarihi->toDateString());
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertDatabaseHas('subscription_quantity_history', ['subscription_id' => $source->id, 'previous_quantity' => 5, 'new_quantity' => 20]);
        app(\App\Services\SubscriptionRenewalService::class)->processRenewals(Carbon::parse('2026-11-04'));
        $this->assertSame('2027-11-04', $source->fresh()->bitis_tarihi->toDateString());
        $this->assertSame('completed', $task->fresh()->status);
    }

    private function approvedSubscriptionQuote(bool $multiple = false): array
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $task = $this->task(['auto_renew' => false]);
        $this->review($task);
        $payload = $this->payload($task);
        if ($multiple) {
            $payload['items'][] = $payload['items'][0];
        }
        $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();
        $quote = Quote::latest('id')->firstOrFail();
        $quote->update(['status' => 'approved']);
        $task->update(['status' => 'renewal_pending', 'notified_at' => now()]);

        return [$task, $quote->fresh('items')];
    }

    private function subscriptionPayload(WorkTask $task, \App\Models\QuoteItem $item): array
    {
        return array_merge($task->subject->only(['customer_cari_id', 'provider_cari_id', 'service_provider_id', 'product_id', 'sozlesme_no', 'taahhut_tipi', 'faturalama_periyodu', 'currency']), [
            'quote_item_id' => $item->id, 'quantity' => $item->quantity,
            'baslangic_tarihi' => $task->subject->baslangic_tarihi->toDateString(), 'bitis_tarihi' => $task->subject->bitis_tarihi->toDateString(),
            'usd_birim_alis' => $item->birim_alis, 'usd_birim_satis' => $item->birim_satis, 'auto_renew' => true, 'durum' => 'active', 'vat_rate' => 20,
        ]);
    }

    private function subscription(array $overrides = []): Subscription
    {
        $customer = Cari::create(['name' => 'Yenileme Müşterisi', 'cari_type' => 'customer', 'notifications_enabled' => false]);
        $product = Product::create(['name' => 'Microsoft 365 Business Standard', 'currency' => 'USD', 'alis_usd_monthly_commitment' => 8, 'satis_usd_monthly_commitment' => 11]);

        return Subscription::create(array_merge([
            'customer_cari_id' => $customer->id, 'provider_cari_id' => $customer->id, 'product_id' => $product->id,
            'sozlesme_no' => 'SOZ-'.$customer->id, 'baslangic_tarihi' => '2025-11-19', 'bitis_tarihi' => '2026-11-19',
            'taahhut_tipi' => 'monthly_commitment', 'faturalama_periyodu' => 'monthly', 'auto_renew' => true,
            'durum' => 'active', 'quantity' => 20, 'usd_birim_alis' => 7, 'usd_birim_satis' => 10, 'currency' => 'USD',
        ], $overrides));
    }

    private function task(array $overrides = []): WorkTask
    {
        $subscription = $this->subscription($overrides);
        app(RenewalWorkTaskService::class)->detect();

        return WorkTask::where('subject_id', $subscription->id)->firstOrFail();
    }

    private function review(WorkTask $task): void
    {
        $this->patch(route('work-tasks.update', $task), ['action' => 'pricing', 'verified_cost' => 9, 'proposed_sale' => 12, 'cost_confirmed' => '1'])->assertSessionHasNoErrors();
        $task->refresh();
    }

    private function payload(WorkTask $task): array
    {
        return ['type' => 'firm', 'work_task_id' => $task->id, 'customer_cari_id' => $task->customer_cari_id, 'vat_rate' => 20,
            'items' => [['product_id' => $task->snapshot['product_id'], 'quantity' => 20, 'taahhut_tipi' => 'monthly_commitment', 'birim_satis' => 12]]];
    }
}
