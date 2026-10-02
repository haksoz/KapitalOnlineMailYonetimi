<?php

namespace App\Services;

use App\Models\Quote;
use Spatie\LaravelPdf\Facades\Pdf;

final class QuotePdf
{
    /**
     * @return array{path: string, filename: string}
     */
    public function write(Quote $quote): array
    {
        $quote->loadMissing(['customerCari', 'items.options']);
        $path = sys_get_temp_dir().'/'.uniqid('pdf_', true).'.pdf';
        $filename = $quote->quote_number.'-'.now()->format('Ymd-Hi').'.pdf';

        Pdf::view('quotes.pdf', ['quote' => $quote, 'pdfMode' => true])
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->save($path);

        return ['path' => $path, 'filename' => $filename];
    }
}
