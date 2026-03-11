<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class SalesInvoiceXmlParser
{
    private const UBL_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const UBL_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    /**
     * Satış e-fatura (UBL) XML'ini parse eder.
     * Eşleştirme için: dönem (IssueDate), alıcı VKN, açıklamadaki *xxx* sözleşme no'ları, KDV hariç toplam.
     *
     * @return array{
     *   invoice_id: string|null,
     *   uuid: string|null,
     *   issue_date: string|null,
     *   customer_vkn: string|null,
     *   sozlesme_nos: list<string>,
     *   tax_exclusive_amount: float,
     *   payable_amount: float
     * }
     */
    public function parse(string $xmlContent): array
    {
        $xmlContent = $this->normalizeXmlString($xmlContent);

        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', self::UBL_CBC);
        $xpath->registerNamespace('cac', self::UBL_CAC);

        $invoiceId = $this->firstNodeValue($xpath->query('//cbc:ID'), 0);
        $uuid = $this->firstNodeValue($xpath->query('//cbc:UUID'), 0);
        $issueDate = $this->firstNodeValue($xpath->query('//cbc:IssueDate'), 0);
        $customerVkn = $this->firstNodeValue($xpath->query("//cac:AccountingCustomerParty//cbc:ID[@schemeID='VKN']"), 0);
        if ($customerVkn === '') {
            $customerVkn = $this->firstNodeValue($xpath->query("//cac:AccountingCustomerParty//cbc:ID[@schemeID='TCKN']"), 0);
        }

        $taxExclusive = (float) $this->firstNodeValue($xpath->query('//cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount[@currencyID="TRY"]'), 0);
        if ($taxExclusive === 0.0) {
            $taxExclusive = (float) $this->firstNodeValue($xpath->query('//cac:LegalMonetaryTotal/cbc:LineExtensionAmount[@currencyID="TRY"]'), 0);
        }
        $payableAmount = (float) $this->firstNodeValue($xpath->query('//cac:LegalMonetaryTotal/cbc:PayableAmount[@currencyID="TRY"]'), 0);

        $sozlesmeNos = [];
        $invoiceLines = $xpath->query('//cac:InvoiceLine');
        foreach ($invoiceLines as $lineNode) {
            foreach ($this->extractAsteriskCodesFromLine($xpath, $lineNode) as $code) {
                if ($code !== '' && ! in_array($code, $sozlesmeNos, true)) {
                    $sozlesmeNos[] = $code;
                }
            }
        }

        return [
            'invoice_id' => $invoiceId !== '' ? $invoiceId : null,
            'uuid' => $uuid !== '' ? $uuid : null,
            'issue_date' => $issueDate !== '' ? $issueDate : null,
            'customer_vkn' => $customerVkn !== '' ? $customerVkn : null,
            'sozlesme_nos' => $sozlesmeNos,
            'tax_exclusive_amount' => $taxExclusive,
            'payable_amount' => $payableAmount,
        ];
    }

    /**
     * BOM ve baştaki/sondaki boşlukları kaldırır; loadXML için geçerli XML başlangıcı sağlar.
     */
    private function normalizeXmlString(string $xml): string
    {
        $xml = trim($xml);
        // UTF-8 BOM
        if (str_starts_with($xml, "\xEF\xBB\xBF")) {
            $xml = substr($xml, 3);
        }
        // UTF-16 LE BOM
        if (str_starts_with($xml, "\xFF\xFE")) {
            $xml = substr($xml, 2);
            $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-16LE');
        }
        // UTF-16 BE BOM
        if (str_starts_with($xml, "\xFE\xFF")) {
            $xml = substr($xml, 2);
            $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-16BE');
        }

        $xml = trim($xml);
        // İlk geçerli XML başlangıcını bul (bazı dosyalarda BOM dışı görünmez karakter olabiliyor)
        $first = strpos($xml, '<');
        if ($first > 0) {
            $xml = substr($xml, $first);
        }

        return $xml;
    }

    private function firstNodeValue(\DOMNodeList $list, int $index = 0): string
    {
        $node = $list->item($index);

        return $node ? trim((string) $node->textContent) : '';
    }

    /**
     * Satır içinde *xxx* formatındaki kodları döndürür (cac:Item/cbc:Name ve cbc:Note).
     *
     * @return list<string>
     */
    private function extractAsteriskCodesFromLine(DOMXPath $xpath, \DOMNode $lineNode): array
    {
        $codes = [];
        $texts = [];
        $nameNodes = $xpath->query('.//cac:Item/cbc:Name', $lineNode);
        foreach ($nameNodes as $n) {
            $texts[] = trim((string) $n->textContent);
        }
        $noteNodes = $xpath->query('.//cbc:Note', $lineNode);
        foreach ($noteNodes as $n) {
            $texts[] = trim((string) $n->textContent);
        }
        foreach ($texts as $text) {
            if (preg_match_all('/\*([^*]+)\*/', $text, $m)) {
                foreach ($m[1] as $code) {
                    $code = trim($code);
                    if ($code !== '' && ! in_array($code, $codes, true)) {
                        $codes[] = $code;
                    }
                }
            }
        }

        return $codes;
    }
}
