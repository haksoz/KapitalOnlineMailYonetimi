<?php

namespace Tests\Feature;

use App\Automation\EventType;
use App\Automation\JobStatus;
use App\Automation\QuotePlaceholders;
use App\Models\AutomationJob;
use App\Models\Cari;
use App\Models\NotificationTemplate;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class QuoteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'http://gotenberg.test/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf']),
        ]);
    }

    public function test_guest_is_redirected_from_quotes(): void
    {
        $this->get(route('quotes.index'))->assertRedirect(route('login'));
    }

    public function test_optional_quote_snapshots_unit_prices_without_quantity_or_grand_total(): void
    {
        [$user, $cari, $product] = $this->fixtures();

        $response = $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $quote = Quote::query()->first();

        $response->assertRedirect(route('quotes.show', $quote));
        $this->assertSame('TKL000001', $quote->quote_number);
        $this->assertNotEmpty($quote->uuid);
        $this->assertSame(Quote::TYPE_OPTIONAL, $quote->type);
        $this->assertNull($quote->vat_rate);

        $item = $quote->items()->with('options')->first();
        $this->assertNull($item->quantity);
        $annual = $item->options->firstWhere('taahhut_tipi', 'annual_commitment');
        $this->assertSame('4.2000', QuoteMath::unit($annual->birim_alis));
        $this->assertSame('5.2000', QuoteMath::unit($annual->birim_satis));
        $this->assertSame('23.81', $annual->profitRate());

        $product->update([
            'alis_usd_yearly_commitment' => 1,
            'satis_usd_yearly_commitment' => 9.99,
        ]);
        $annual->refresh();
        $this->assertSame('5.2000', QuoteMath::unit($annual->birim_satis));
        $this->assertSame('4.2000', QuoteMath::unit($annual->birim_alis));

        $show = $this->actingAs($user)->get(route('quotes.show', $quote));
        $show->assertOk();
        $show->assertSee('4,20');
        $show->assertSee('23,81%');
        $show->assertSee('GIZLI-NOT-XYZ');
        $show->assertDontSee('Genel toplam');
        $show->assertDontSee('Satış tutarı');
        $show->assertDontSee('104,00');

        $cari->update(['email' => 'karar@ornek.test', 'tax_number' => '1111111111']);
        $customer = $this->actingAs($user)->get(route('quotes.customer', $quote));
        $customer->assertOk();
        $customer->assertDontSee('Fiyatlara KDV dahil değildir.');
        $customer->assertDontSee('104,00');
        $customer->assertDontSee('20 adet');
        $customer->assertDontSee('quote-qty', false);
        $customer->assertDontSee('>Tutar<', false);
        $customer->assertSee('Microsoft 365 Business Basic');
        $customer->assertSee('5,50 USD');
        $customer->assertSee('6,20 USD');
        $customer->assertSee('5,20 USD');
        $customer->assertDontSee('M365-BB');
        $customer->assertSee('Birim Fiyat Teklifi');
        $customer->assertSee('birim fiyat bilgilendirmesidir');
        $customer->assertSee('kesin teklifimizi hazırlayarak');
        $customer->assertDontSee('adedini yazın');
        $customer->assertSee('quote-compare', false);
        $customer->assertSee('Yıllık taahhüt, aylık ödeme');
        $customer->assertSee('Aylık ödeme');
        $customer->assertSee('Yıllık ödeme');
        $customer->assertDontSee('Teklif bilgileri');
        $customer->assertDontSee('Tarih bilgileri');
        $customer->assertSee('Satıcı');
        $customer->assertSee('Alıcı');
        $customer->assertSee('Örnek Müşteri');
        $customer->assertDontSee('karar@ornek.test');
        $customer->assertDontSee('1111111111');
        $customer->assertSee('KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.');
        $customer->assertDontSee('KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZMETLERİ TİCARET LİMİTED ŞİRKETİ');
        $customer->assertDontSee('4980863169');
        $customer->assertDontSee('muhasebe@ko.com.tr');
        $customer->assertDontSee('Aylık taahhütlü seçeneğinde yıllık taahhüt verilir, aylık ödenir.');
        $customer->assertSee('Müşteriye görünen not');
        $customer->assertSee('Birim fiyatlara KDV dahil değildir.');
        $customer->assertSee('Sipariş geçildikten sonra iade veya iptal hakkı yoktur.');
        $content = $customer->getContent();
        $introAt = strpos($content, 'kesin teklifimizi hazırlayarak');
        $tableAt = strpos($content, 'class="quote-compare"');
        $vatAt = strpos($content, 'Birim fiyatlara KDV dahil değildir.');
        $noteAt = strpos($content, 'Müşteriye görünen not');
        $termsAt = strpos($content, 'Sipariş geçildikten sonra iade veya iptal hakkı yoktur.');
        $this->assertNotFalse($introAt);
        $this->assertNotFalse($tableAt);
        $this->assertNotFalse($vatAt);
        $this->assertNotFalse($noteAt);
        $this->assertNotFalse($termsAt);
        $this->assertLessThan($tableAt, $introAt);
        $this->assertLessThan($noteAt, $vatAt);
        $this->assertLessThan($termsAt, $noteAt);
        $customer->assertDontSee('4,20');
        $customer->assertDontSee('GIZLI-NOT-XYZ');
        $customer->assertDontSee('Ara toplam');
        $customer->assertSee('PDF indir');
    }

    public function test_draft_keeps_cost_snapshot_until_catalog_refresh_is_requested(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 20,
                'options' => [
                    'annual_commitment' => ['enabled' => '1', 'birim_satis' => '5.20'],
                ],
            ]],
        ]));

        $quote = Quote::query()->first();
        $item = $quote->items()->first();
        $product->update(['alis_usd_yearly_commitment' => 3, 'name' => 'Yeni ad']);

        $this->actingAs($user)->patch(route('quotes.update', $quote), [
            'customer_cari_id' => $cari->id,
            'items' => [[
                'id' => $item->id,
                'product_id' => $product->id,
                'quantity' => 20,
                'options' => [
                    'annual_commitment' => ['enabled' => '1', 'birim_satis' => '5.70'],
                ],
            ]],
        ])->assertRedirect(route('quotes.show', $quote));

        $kept = $quote->fresh()->items()->with('options')->first();
        $this->assertSame('Microsoft 365 Business Basic', $kept->product_name);
        $this->assertSame('4.2000', QuoteMath::unit($kept->options->first()->birim_alis));
        $this->assertSame('5.7000', QuoteMath::unit($kept->options->first()->birim_satis));

        $this->actingAs($user)->patch(route('quotes.update', $quote), [
            'customer_cari_id' => $cari->id,
            'refresh_catalog' => '1',
            'items' => [[
                'id' => $kept->id,
                'product_id' => $product->id,
                'quantity' => 20,
                'options' => [
                    'annual_commitment' => ['enabled' => '1', 'birim_satis' => '5.70'],
                ],
            ]],
        ])->assertRedirect();

        $refreshed = $quote->fresh()->items()->with('options')->first();
        $this->assertSame('Yeni ad', $refreshed->product_name);
        $this->assertSame('3.0000', QuoteMath::unit($refreshed->options->first()->birim_alis));

        $this->actingAs($user)->delete(route('quotes.destroy', $quote))->assertRedirect(route('quotes.index'));
        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    }

    public function test_firm_quote_calculates_vat_and_hides_cost_on_customer_document(): void
    {
        [$user, $cari, $product] = $this->fixtures();

        $this->actingAs($user)->post(route('quotes.store'), [
            'type' => 'firm',
            'customer_cari_id' => $cari->id,
            'vat_rate' => '20',
            'internal_notes' => 'GIZLI-NOT-XYZ',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 20,
                'taahhut_tipi' => 'annual_commitment',
                'birim_satis' => '5.20',
            ]],
        ])->assertRedirect();

        $quote = Quote::query()->first();
        $this->assertSame('TKL000001', $quote->quote_number);
        $summary = $quote->fresh('items')->firmSummary();
        $this->assertSame('104.00', $summary['net']);
        $this->assertSame('84.00', $summary['cost']);
        $this->assertSame('20.80', $summary['vat']);
        $this->assertSame('124.80', $summary['gross']);
        $this->assertSame('20.00', $summary['profit']);
        $this->assertSame('23.81', $summary['profit_rate']);

        $cari->update(['email' => 'karar@ornek.test', 'tax_number' => '1111111111']);
        $customer = $this->actingAs($user)->get(route('quotes.customer', $quote));
        $customer->assertOk();
        $customer->assertSee('quote-summary', false);
        $customer->assertSee('text-center', false);
        $customer->assertSee('Genel toplam');
        $customer->assertDontSee('M365-BB');
        $customer->assertSee('124,80');
        $customer->assertSee('KDV');
        $customer->assertDontSee('4,20');
        $customer->assertDontSee('GIZLI-NOT-XYZ');
        $customer->assertDontSee('23,81%');
        $customer->assertSee('Birim fiyatlara KDV dahil değildir.');
        $customer->assertSee('quote-notices', false);
        $customer->assertDontSee('quote-summary-notes', false);
        $customer->assertSee('Yıllık taahhütlü seçeneğinde, yıllık ödenir.');
        $firmContent = $customer->getContent();
        $totalsAt = strpos($firmContent, 'Genel toplam:');
        $noticeAt = strpos($firmContent, 'class="quote-notices ');
        $termsAt = strpos($firmContent, 'Sipariş geçildikten sonra iade veya iptal hakkı yoktur.');
        $this->assertNotFalse($totalsAt);
        $this->assertNotFalse($noticeAt);
        $this->assertNotFalse($termsAt);
        $this->assertLessThan($noticeAt, $totalsAt);
        $this->assertLessThan($termsAt, $noticeAt);
        $customer->assertDontSee('Aylık taahhütsüz seçeneğinde aylık ödenir.');
        $customer->assertDontSee('Yıllık taahhüt, aylık ödeme');
        $customer->assertSee('otomatik yenileme yapılmayacak');
        $customer->assertSee('karar@ornek.test');
        $customer->assertSee('1111111111');
        $customer->assertSee('KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.');
        $customer->assertSee('VD YAKACIK - Vergi No: 4980863169');
        $customer->assertSee('E-posta: muhasebe@ko.com.tr');
        $customer->assertSee('Vergi No: 1111111111');
        $customer->assertSee('E-posta: karar@ornek.test');
        $customer->assertDontSee('+90 216 377 4000');
        $customer->assertSee('PDF indir');
        $mail = QuotePlaceholders::forQuote($quote->fresh(['customerCari', 'items']));
        $this->assertStringContainsString('Birim fiyat: 5,20 USD', $mail['{kalemler}']);
        $this->assertStringContainsString('Tutar: 104,00 USD', $mail['{kalemler}']);
        $this->assertStringContainsString('Ara toplam: 104,00 USD', $mail['{kalemler}']);
        $this->assertStringContainsString('Genel toplam: 124,80 USD', $mail['{kalemler}']);
        $this->assertStringNotContainsString('Birim fiyatlara KDV dahil değildir.', $mail['{kalemler}']);
        $this->assertStringNotContainsString('Yıllık taahhütlü seçeneğinde, yıllık ödenir.', $mail['{kalemler}']);
        $this->assertStringContainsString('- Birim fiyatlara KDV dahil değildir.', $mail['{dinamik_kosullar}']);
        $this->assertStringContainsString('- Yıllık taahhütlü seçeneğinde, yıllık ödenir.', $mail['{dinamik_kosullar}']);
        $this->assertStringNotContainsString('4,20', $mail['{kalemler}']);
        $this->assertStringContainsString('VD YAKACIK - Vergi No: 4980863169', $mail['{satici}']);
        $this->assertSame('Örnek Müşteri', $mail['{cari_unvani}']);
        $this->assertStringNotContainsString('1111111111', $mail['{cari_unvani}']);
        $this->assertStringContainsString('Vergi No: 1111111111', $mail['{alici}']);
        $kalemler = QuotePlaceholders::forQuote($quote->fresh(['customerCari', 'items.options']), htmlLines: true)['{kalemler}'];
        $this->assertMatchesRegularExpression('/align="center"[^>]*>Taahhüt</', $kalemler);
        $this->assertMatchesRegularExpression('/align="center"[^>]*>Adet</', $kalemler);
        $this->assertMatchesRegularExpression('/align="center"[^>]*>Yıllık Taahhütlü</', $kalemler);
        $this->assertMatchesRegularExpression('/align="center"[^>]*>20</', $kalemler);
        $this->assertMatchesRegularExpression('/align="right"[^>]*>Birim fiyat</', $kalemler);
        $this->assertMatchesRegularExpression('/align="right"[^>]*>Tutar</', $kalemler);
        $this->assertMatchesRegularExpression('/align="right"[^>]*>5,20 USD</', $kalemler);
        $html = view('quotes.mail', ['quote' => $quote->fresh(['customerCari', 'items'])])->render();
        $this->assertStringContainsString('Kesin Teklif', $html);
        $this->assertStringContainsString('Genel toplam:', $html);

        NotificationTemplate::query()->where('legacy_key', 'quote_firm_sent')->update([
            'body' => "Merhaba,\n\n{cari_unvani} için hazırladığımız {teklif_no} numaralı teklifimizi aşağıda ve ekte bilgilerinize sunarız.\n\n{kalemler}\n{dinamik_kosullar}\n\nİyi çalışmalar dileriz.",
        ]);
        $this->actingAs($user)->post(route('quotes.send', $quote))->assertRedirect(route('quotes.show', $quote));
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $sent = $transport->messages()->last()?->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $sent);
        $sentHtml = (string) $sent->getHtmlBody();
        $this->assertStringContainsString('Örnek Müşteri için hazırladığımız '.$quote->quote_number.' numaralı teklifimizi', $sentHtml);
        $this->assertStringNotContainsString('Vergi No: 1111111111', $sentHtml);
        $this->assertStringContainsString('<table', $sentHtml);
        $this->assertStringContainsString('Microsoft 365 Business Basic', $sentHtml);
        $this->assertStringContainsString('Genel toplam:', $sentHtml);
        $this->assertStringContainsString('Birim fiyatlara KDV dahil değildir.', $sentHtml);
        $this->assertStringContainsString('İyi çalışmalar dileriz.', $sentHtml);
        $this->assertStringNotContainsString('background:#f3f4f6', $sentHtml);
        $this->assertStringNotContainsString('Satıcı', $sentHtml);
        $this->assertStringNotContainsString('VD YAKACIK', $sentHtml);
        $this->assertStringNotContainsString('Orta Mah.', $sentHtml);
        $this->assertStringContainsString('için hazırladığımız', (string) $sent->getTextBody());
        $this->assertCount(1, $sent->getAttachments());
    }

    public function test_optional_quote_converts_from_its_snapshot_and_cannot_convert_twice(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $source = Quote::query()->first();
        $item = $source->items()->first();

        $edit = $this->actingAs($user)->get(route('quotes.edit', $source));
        $edit->assertOk();
        $edit->assertSee('Microsoft 365 Business Basic', false);
        $edit->assertSee('"birim_satis":"5.50"', false);
        $edit->assertSee('syncSelect($el, line.product_id)', false);
        $this->actingAs($user)->get(route('quotes.convert', $source))->assertOk()->assertSee('Kesin teklife dönüştür');

        $product->update([
            'alis_usd_yearly_commitment' => 1,
            'satis_usd_yearly_commitment' => 9.99,
        ]);

        $this->actingAs($user)->post(route('quotes.convert.store', $source), [
            'vat_rate' => '20',
            'lines' => [[
                'include' => '1',
                'quote_item_id' => $item->id,
                'taahhut_tipi' => 'annual_commitment',
                'quantity' => 10,
                'birim_satis' => '6.00',
            ]],
        ])->assertRedirect();

        $source->refresh();
        $firm = Quote::query()->where('type', Quote::TYPE_FIRM)->first();
        $firmItem = $firm->items()->first();

        $this->assertSame(Quote::STATUS_CONVERTED, $source->status);
        $this->assertEquals($source->id, $firm->source_quote_id);
        $this->assertSame(10, $firmItem->quantity);
        $this->assertSame('annual_commitment', $firmItem->taahhut_tipi);
        $this->assertSame('6.0000', QuoteMath::unit($firmItem->birim_satis));
        $this->assertSame('4.2000', QuoteMath::unit($firmItem->birim_alis));
        $this->assertEquals($item->id, $firmItem->source_quote_item_id);
        $this->assertSame('TKL000002', $firm->quote_number);

        $this->actingAs($user)
            ->from(route('quotes.show', $source))
            ->post(route('quotes.convert.store', $source), [
                'vat_rate' => '20',
                'lines' => [[
                    'include' => '1',
                    'quote_item_id' => $item->id,
                    'taahhut_tipi' => 'annual_commitment',
                    'quantity' => 10,
                    'birim_satis' => '6.00',
                ]],
            ])
            ->assertSessionHasErrors('quote');
        $this->assertSame(1, Quote::query()->where('type', Quote::TYPE_FIRM)->count());
    }

    public function test_sent_quote_cannot_be_edited_or_deleted(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $quote = Quote::query()->first();
        $cari->update(['email' => 'musteri@example.com']);

        $show = $this->actingAs($user)->get(route('quotes.show', $quote));
        $show->assertOk();
        $show->assertSee('Kayıtlı e-posta');
        $show->assertSee('musteri@example.com');
        $show->assertSee('Birim fiyat teklifi gönderildi');

        $this->actingAs($user)->post(route('quotes.send', $quote))
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('success');
        $quote->refresh();
        $this->assertSame(Quote::STATUS_SENT, $quote->status);
        $sentLabel = $quote->sent_at->timezone('Europe/Istanbul')->format('d.m.Y H:i');

        $this->actingAs($user)->get(route('quotes.show', $quote))->assertSee('Gönderim')->assertSee($sentLabel);
        $this->actingAs($user)->get(route('quotes.index'))->assertSee('Gönderim '.$sentLabel);
        $this->actingAs($user)->get(route('quotes.customer', $quote))->assertDontSee('Gönderim')->assertDontSee($sentLabel);

        $quote->load('items.options', 'customerCari');
        $mail = QuotePlaceholders::forQuote($quote);
        $this->assertStringContainsString('kesin teklifimizi hazırlayarak', $mail['{kalemler}']);
        $this->assertStringContainsString('Yıllık Taahhütlü — Yıllık ödeme: 5,20 USD', $mail['{kalemler}']);
        $this->assertStringContainsString('Microsoft 365 Business Basic', $mail['{kalemler}']);
        $this->assertStringNotContainsString('Tutar:', $mail['{kalemler}']);
        $this->assertStringNotContainsString('20 adet', $mail['{kalemler}']);
        $this->assertStringNotContainsString('Ara toplam:', $mail['{kalemler}']);
        $this->assertStringNotContainsString('4,20', $mail['{kalemler}']);
        $this->assertStringContainsString('KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.', $mail['{satici}']);
        $this->assertStringContainsString('Sipariş geçildikten sonra iade veya iptal hakkı yoktur.', $mail['{kosullar}']);
        $html = view('quotes.mail', ['quote' => $quote])->render();
        $this->assertStringContainsString('Birim Fiyat Teklifi', $html);
        $this->assertStringContainsString('Aylık Taahhütlü', $html);
        $this->assertStringContainsString('5,20 USD', $html);
        $this->assertStringNotContainsString('20 adet', $html);
        $this->assertStringNotContainsString('>Tutar<', $html);

        $job = AutomationJob::query()->where('subject_id', $quote->id)->first();
        $this->assertNotNull($job);
        $this->assertSame(EventType::QuoteOptionalSent, $job->event_type);
        $this->assertSame(JobStatus::Succeeded, $job->status);
        $this->assertSame('musteri@example.com', $job->to_email);

        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $sent = $transport->messages()->last()?->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $sent);
        $sentHtml = (string) $sent->getHtmlBody();
        $this->assertStringContainsString('Birim Fiyat Teklifi', $sentHtml);
        $this->assertStringContainsString('5,20 USD', $sentHtml);
        $this->assertStringContainsString('<table', $sentHtml);
        $this->assertStringNotContainsString('20 adet', $sentHtml);
        $attachments = $sent->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('application/pdf', $attachments[0]->getContentType());
        $this->assertStringStartsWith($quote->quote_number.'-', (string) $attachments[0]->getFilename());
        $this->assertStringEndsWith('.pdf', (string) $attachments[0]->getFilename());

        $this->actingAs($user)
            ->patch(route('quotes.update', $quote), [])
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->delete(route('quotes.destroy', $quote))
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('quotes', ['id' => $quote->id]);
    }

    public function test_rejected_quote_can_be_updated_and_sent_again(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $quote = Quote::query()->first();
        $cari->update(['email' => 'musteri@example.com']);

        $this->actingAs($user)->post(route('quotes.send', $quote));
        $this->actingAs($user)->post(route('quotes.reject', $quote));
        $quote->refresh();
        $this->assertSame(Quote::STATUS_REJECTED, $quote->status);

        $this->actingAs($user)->get(route('quotes.edit', $quote))->assertOk();
        $this->actingAs($user)->get(route('quotes.show', $quote))->assertSee('Tekrar gönder');

        $this->actingAs($user)
            ->patch(route('quotes.update', $quote), $this->optionalPayload($cari, $product, [
                'notes' => 'Revize müşteri notu',
            ]))
            ->assertRedirect(route('quotes.show', $quote));

        $quote->refresh();
        $this->assertSame(Quote::STATUS_REJECTED, $quote->status);
        $this->assertSame('Revize müşteri notu', $quote->notes);

        $this->actingAs($user)->post(route('quotes.send', $quote))->assertRedirect(route('quotes.show', $quote));
        $this->assertSame(Quote::STATUS_SENT, $quote->fresh()->status);
    }

    public function test_send_asks_for_email_when_customer_has_none_and_stores_it(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $quote = Quote::query()->first();

        $show = $this->actingAs($user)->get(route('quotes.show', $quote));
        $show->assertOk();
        $show->assertSee('quote-send-email', false);
        $show->assertDontSee('Kayıtlı e-posta');

        $this->actingAs($user)
            ->from(route('quotes.show', $quote))
            ->post(route('quotes.send', $quote))
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHasErrors('email');
        $this->assertSame(Quote::STATUS_DRAFT, $quote->fresh()->status);

        $this->actingAs($user)
            ->post(route('quotes.send', $quote), ['email' => 'yeni@example.com'])
            ->assertRedirect(route('quotes.show', $quote));

        $cari->refresh();
        $this->assertSame('yeni@example.com', $cari->email);
        $this->assertFalse($cari->notifications_enabled);
        $this->assertSame(Quote::STATUS_SENT, $quote->fresh()->status);
    }

    public function test_quote_mail_template_preview_uses_quotes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        [$user, $cari, $product] = $this->fixtures();
        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $quote = Quote::query()->first();
        $template = NotificationTemplate::query()->where('legacy_key', 'quote_optional_sent')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.notifications.templates.edit', $template))
            ->assertOk()
            ->assertSee('Örnek teklif', false)
            ->assertSee($quote->quote_number, false)
            ->assertDontSee('Örnek fatura', false);

        $this->actingAs($admin)->post(route('admin.notifications.templates.preview', $template), [
            'quote_id' => $quote->id,
            'subject' => 'Taslak {teklif_no}',
            'body' => "Merhaba {musteri}\n{kalemler}",
        ])->assertOk()
            ->assertSee('Taslak '.$quote->quote_number, false)
            ->assertSee('Teklif '.$quote->quote_number, false)
            ->assertSee('5,20 USD', false)
            ->assertSee('Aylık Taahhütlü', false)
            ->assertSee('<table', false)
            ->assertDontSee('Tutar:', false)
            ->assertDontSee('20 adet', false)
            ->assertDontSee('4,20', false)
            ->assertDontSee('Ara toplam:', false);

        $firmTemplate = NotificationTemplate::query()->where('legacy_key', 'quote_firm_sent')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.notifications.templates.edit', $firmTemplate))
            ->assertOk()
            ->assertDontSee($quote->quote_number, false);

        $this->actingAs($user)->post(route('quotes.store'), [
            'type' => 'firm',
            'customer_cari_id' => $cari->id,
            'vat_rate' => '20',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 20,
                'taahhut_tipi' => 'annual_commitment',
                'birim_satis' => '5.20',
            ]],
        ])->assertRedirect();
        $firm = Quote::query()->where('type', Quote::TYPE_FIRM)->first();

        $this->actingAs($admin)
            ->get(route('admin.notifications.templates.edit', $firmTemplate))
            ->assertOk()
            ->assertSee($firm->quote_number, false)
            ->assertSee('{kalemler}', false);

        $this->actingAs($admin)->post(route('admin.notifications.templates.preview', $firmTemplate), [
            'quote_id' => $firm->id,
            'subject' => 'Kesin {teklif_no}',
            'body' => '{kalemler}',
        ])->assertOk()
            ->assertSee('<table', false)
            ->assertSee('Ürün', false)
            ->assertSee('Taahhüt', false)
            ->assertSee('Microsoft 365 Business Basic', false)
            ->assertSee('5,20 USD', false)
            ->assertSee('104,00 USD', false)
            ->assertSee('Genel toplam:', false)
            ->assertSee('124,80 USD', false)
            ->assertDontSee('Birim fiyatlara KDV dahil değildir.', false)
            ->assertDontSee('Yıllık taahhütlü seçeneğinde, yıllık ödenir.', false);

        $this->actingAs($admin)->post(route('admin.notifications.templates.test', $firmTemplate), [
            'test_email' => 'deneme@example.com',
            'quote_id' => $firm->id,
            'subject' => 'Sayfadaki konu {teklif_no}',
            'body' => "Sayfadaki metin {teklif_no}\n\n{kalemler}",
        ])->assertRedirect(route('admin.notifications.templates.edit', $firmTemplate));

        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $sent = $transport->messages()->last()?->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $sent);
        $this->assertSame('[TEST] Sayfadaki konu '.$firm->quote_number, $sent->getSubject());
        $sentHtml = (string) $sent->getHtmlBody();
        $this->assertStringContainsString('Sayfadaki metin '.$firm->quote_number, $sentHtml);
        $this->assertStringContainsString('<table', $sentHtml);
        $this->assertStringNotContainsString('Satıcı', $sentHtml);
        $this->assertCount(1, $sent->getAttachments());
    }

    public function test_mixed_currencies_are_rejected(): void
    {
        [$user, $cari, $product] = $this->fixtures();
        $tryProduct = Product::create([
            'name' => 'TL ürün',
            'stock_code' => 'TRY-1',
            'currency' => Product::CURRENCY_TRY,
            'satis_usd_monthly_commitment' => 100,
            'alis_usd_monthly_commitment' => 80,
        ]);

        $this->actingAs($user)
            ->from(route('quotes.create'))
            ->post(route('quotes.store'), [
                'type' => 'firm',
                'customer_cari_id' => $cari->id,
                'vat_rate' => 20,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'taahhut_tipi' => 'annual_commitment',
                        'birim_satis' => '5.20',
                    ],
                    [
                        'product_id' => $tryProduct->id,
                        'quantity' => 1,
                        'taahhut_tipi' => 'monthly_commitment',
                        'birim_satis' => '100',
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.1.product_id');

        $this->assertSame(0, Quote::query()->count());
    }

    public function test_quote_pages_render(): void
    {
        [$user] = $this->fixtures();

        $this->actingAs($user)->get(route('quotes.index'))->assertOk()->assertSee('Teklifler');
        $this->actingAs($user)->get(route('quotes.create', ['type' => 'optional']))->assertOk()->assertSee('Birim Fiyat Teklifi')->assertSee('Kar marjı (%)')->assertSee('Kâr:');
        $this->actingAs($user)->get(route('quotes.create', ['type' => 'firm']))
            ->assertOk()
            ->assertSee('Kesin Teklif')
            ->assertSee('Teklif bilgileri')
            ->assertSee('Tarih bilgileri')
            ->assertSee('Satıcı bilgileri')
            ->assertSee('Alıcı bilgileri')
            ->assertSee('KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.')
            ->assertSee('VD YAKACIK - Vergi No: 4980863169');
    }

    public function test_customer_document_pdf_is_built_by_gotenberg_on_download(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 08:04:00'));
        [$user, $cari, $product] = $this->fixtures();
        $this->get(route('quotes.customer.pdf', 1))->assertRedirect(route('login'));

        Http::fake([
            'http://gotenberg.test/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->actingAs($user)->post(route('quotes.store'), $this->optionalPayload($cari, $product));
        $optional = Quote::query()->first();
        $optionalResponse = $this->actingAs($user)->get(route('quotes.customer.pdf', $optional));
        $optionalResponse->assertOk();
        $optionalResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment; filename="'.$optional->quote_number.'-20261002-0804.pdf"',
            (string) $optionalResponse->headers->get('content-disposition')
        );

        Http::assertSent(function ($request) use ($optional) {
            $body = $request->body();

            return str_contains($request->url(), '/forms/chromium/convert/html')
                && str_contains($body, 'data:image/png;base64,')
                && str_contains($body, 'Birim Fiyat Teklifi')
                && str_contains($body, $optional->quote_number)
                && str_contains($body, 'Microsoft 365 Business Basic')
                && str_contains($body, '5,20 USD')
                && str_contains($body, 'Aylık Taahhütlü')
                && str_contains($body, 'işaretleyin')
                && str_contains($body, '>Seç<')
                && str_contains($body, 'class="tick"')
                && str_contains($body, 'class="blank"')
                && ! str_contains($body, '20 adet')
                && ! str_contains($body, '104,00')
                && ! str_contains($body, 'name="landscape"')
                && str_contains($body, 'marginTop')
                && str_contains($body, '10mm')
                && ! str_contains($body, 'M365-BB')
                && ! str_contains($body, '4,20')
                && ! str_contains($body, 'GIZLI-NOT-XYZ')
                && ! str_contains($body, '4980863169')
                && ! str_contains($body, 'Gönderim')
                && ! $request->hasHeader('Authorization');
        });

        $cari->update(['email' => 'karar@ornek.test', 'tax_number' => '1111111111']);
        $this->actingAs($user)->post(route('quotes.store'), [
            'type' => 'firm',
            'customer_cari_id' => $cari->id,
            'vat_rate' => '20',
            'internal_notes' => 'GIZLI-NOT-XYZ',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 20,
                'taahhut_tipi' => 'annual_commitment',
                'birim_satis' => '5.20',
            ]],
        ]);
        $firm = Quote::query()->where('type', Quote::TYPE_FIRM)->first();
        $firmResponse = $this->actingAs($user)->get(route('quotes.customer.pdf', $firm));
        $firmResponse->assertOk();
        $firmResponse->assertHeader('content-type', 'application/pdf');

        Http::assertSent(function ($request) use ($firm) {
            $body = $request->body();

            return str_contains($body, $firm->quote_number)
                && str_contains($body, 'Kesin Teklif')
                && str_contains($body, 'Genel toplam')
                && str_contains($body, 'align="center"')
                && str_contains($body, 'align="right"')
                && str_contains($body, '124,80')
                && str_contains($body, 'VD YAKACIK - Vergi No: 4980863169')
                && str_contains($body, 'Vergi No: 1111111111')
                && str_contains($body, 'E-posta: karar@ornek.test')
                && ! str_contains($body, 'name="landscape"')
                && ! str_contains($body, 'M365-BB')
                && ! str_contains($body, '4,20')
                && ! str_contains($body, 'GIZLI-NOT-XYZ');
        });

        Http::swap(new Factory);
        Http::fake([
            'http://gotenberg.test/*' => Http::response('down', 500),
        ]);
        $this->actingAs($user)->get(route('quotes.customer.pdf', $firm))
            ->assertRedirect(route('quotes.customer', $firm))
            ->assertSessionHas('error');
    }

    /**
     * @return array{0: User, 1: Cari, 2: Product}
     */
    private function fixtures(): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
        ]);

        $cari = Cari::create([
            'name' => 'Örnek Müşteri',
            'short_name' => 'Örnek',
            'cari_type' => 'customer',
        ]);

        $product = Product::create([
            'name' => 'Microsoft 365 Business Basic',
            'stock_code' => 'M365-BB',
            'currency' => Product::CURRENCY_USD,
            'alis_usd_monthly_commitment' => 4.80,
            'satis_usd_monthly_commitment' => 5.50,
            'alis_usd_monthly_no_commitment' => 5.40,
            'satis_usd_monthly_no_commitment' => 6.20,
            'alis_usd_yearly_commitment' => 4.20,
            'satis_usd_yearly_commitment' => 5.20,
        ]);

        return [$user, $cari, $product];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function optionalPayload(Cari $cari, Product $product, array $overrides = []): array
    {
        $payload = [
            'type' => 'optional',
            'customer_cari_id' => $cari->id,
            'notes' => 'Müşteriye görünen not',
            'internal_notes' => 'GIZLI-NOT-XYZ',
            'items' => [[
                'product_id' => $product->id,
                'options' => [
                    'monthly_commitment' => ['enabled' => '1', 'birim_satis' => '5.50'],
                    'monthly_no_commitment' => ['enabled' => '1', 'birim_satis' => '6.20'],
                    'annual_commitment' => ['enabled' => '1', 'birim_satis' => '5.20'],
                ],
            ]],
        ];

        return array_replace($payload, $overrides);
    }
}
