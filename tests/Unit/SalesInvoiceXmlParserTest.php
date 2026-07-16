<?php

namespace Tests\Unit;

use App\Services\SalesInvoiceXmlParser;
use PHPUnit\Framework\TestCase;

class SalesInvoiceXmlParserTest extends TestCase
{
    public function test_it_extracts_invoice_totals(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">
    <cbc:ID>FAT-2026-1</cbc:ID>
    <cbc:IssueDate>2026-07-16</cbc:IssueDate>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification><cbc:ID schemeID="VKN">1234567890</cbc:ID></cac:PartyIdentification>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:TaxTotal><cbc:TaxAmount currencyID="TRY">400.00</cbc:TaxAmount></cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:TaxExclusiveAmount currencyID="TRY">2000.00</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="TRY">2400.00</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="TRY">2400.00</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
</Invoice>
XML;

        $parsed = (new SalesInvoiceXmlParser())->parse($xml);

        $this->assertSame(2000.0, $parsed['tax_exclusive_amount']);
        $this->assertSame(400.0, $parsed['tax_amount']);
        $this->assertSame(2400.0, $parsed['tax_inclusive_amount']);
        $this->assertSame(2400.0, $parsed['payable_amount']);
    }
}
