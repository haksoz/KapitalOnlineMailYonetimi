<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class ArenaXmlParser
{
    private const UBL_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    private const UBL_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const UBL_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    /**
     * UBL-TR (Arena) alış faturası XML'ini parse eder.
     *
     * @return array{invoice_no: string|null, issue_date: string|null, seller_vkn: string|null, lines: list<array{sozlesme_no: string, quantity: float, line_extension_amount_try: float, item_name: string|null, stock_code: string|null}>}
     */
    public function parse(string $xmlContent): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', self::UBL_CBC);
        $xpath->registerNamespace('cac', self::UBL_CAC);

        $invoiceNo = $this->firstNodeValue($xpath->query('//cbc:ID'), 0);
        $issueDate = $this->firstNodeValue($xpath->query('//cbc:IssueDate'), 0);
        $sellerVkn = $this->firstNodeValue($xpath->query("//cac:AccountingSupplierParty//cbc:ID[@schemeID='VKN']"), 0);

        $lines = [];
        $invoiceLines = $xpath->query('//cac:InvoiceLine');
        foreach ($invoiceLines as $lineNode) {
            $sozlesmeNo = $this->extractSozlesmeNoFromLine($xpath, $lineNode);
            $quantity = (float) $this->firstNodeValue($xpath->query('.//cbc:InvoicedQuantity', $lineNode), 0);
            $amount = (float) $this->firstNodeValue($xpath->query('.//cbc:LineExtensionAmount[@currencyID="TRY"]', $lineNode), 0);
            $itemName = $this->firstNodeValue($xpath->query('.//cac:Item/cbc:Name', $lineNode), 0);
            $stockCode = $this->firstNodeValue($xpath->query('.//cac:SellersItemIdentification/cbc:ID', $lineNode), 0);

            $lines[] = [
                'sozlesme_no' => $sozlesmeNo !== null ? $sozlesmeNo : '',
                'quantity' => $quantity,
                'line_extension_amount_try' => $amount,
                'item_name' => $itemName !== '' ? $itemName : null,
                'stock_code' => $stockCode !== '' ? $stockCode : null,
            ];
        }

        return [
            'invoice_no' => $invoiceNo ?: null,
            'issue_date' => $issueDate ?: null,
            'seller_vkn' => $sellerVkn ?: null,
            'lines' => $lines,
        ];
    }

    private function firstNodeValue(\DOMNodeList $list, int $index = 0): string
    {
        $node = $list->item($index);

        return $node ? trim((string) $node->textContent) : '';
    }

    private function extractSozlesmeNoFromLine(DOMXPath $xpath, \DOMNode $lineNode): ?string
    {
        $notes = $xpath->query('.//cbc:Note', $lineNode);
        foreach ($notes as $note) {
            $text = trim((string) $note->textContent);
            if (preg_match('#SozlesmeNo([^|]+)\|#', $text, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }
}
