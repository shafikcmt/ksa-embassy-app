<?php

namespace Tests\Unit;

use App\Services\InvoiceService;
use App\Support\NumberToWords;
use PHPUnit\Framework\TestCase;

/** Pure integer-cent math for invoices — no DB, runs on the SQLite default. */
class InvoiceCalculationTest extends TestCase
{
    public function test_to_cents_parses_strings_without_float_drift(): void
    {
        $this->assertSame(0, InvoiceService::toCents(''));
        $this->assertSame(10, InvoiceService::toCents('0.1'));
        $this->assertSame(125050, InvoiceService::toCents('1,250.50'));
        $this->assertSame(1999, InvoiceService::toCents('19.99'));
        $this->assertSame('0.30', InvoiceService::fromCents(InvoiceService::toCents('0.1') + InvoiceService::toCents('0.2')));
        $this->assertSame('1250.05', InvoiceService::fromCents(125005));
    }

    public function test_rejects_more_than_two_decimals_and_negatives(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InvoiceService::toCents('1.005');
    }

    public function test_lines_subtotal_tax_discount_total(): void
    {
        $r = InvoiceService::calculate([
            ['processing_fee' => '0.30', 'mofa_fee' => '0'],      // 30 cents
            ['processing_fee' => '1500.00', 'mofa_fee' => '1500.00'], // 300000 cents
        ], 'percent', '15', 'amount', '100.30');

        $this->assertSame([30, 300000], $r['lines']);
        $this->assertSame(300030, $r['subtotal']);   // 3000.30
        $this->assertSame(45005, $r['tax']);         // 450.045 → 450.05 (half-up)
        $this->assertSame(10030, $r['discount']);
        $this->assertSame(335005, $r['total']);      // 3000.30 + 450.05 − 100.30
    }

    public function test_percent_discount_and_none_tax(): void
    {
        $r = InvoiceService::calculate([['processing_fee' => '999.99', 'mofa_fee' => '0']], 'none', '99', 'percent', '12.5');

        $this->assertSame(0, $r['tax']);
        $this->assertSame(12500, $r['discount']);    // 124.99875 → 125.00
        $this->assertSame(87499, $r['total']);
    }

    public function test_discount_larger_than_total_goes_negative_for_service_to_reject(): void
    {
        $r = InvoiceService::calculate([['processing_fee' => '10', 'mofa_fee' => '0']], 'none', 0, 'amount', '10.01');
        $this->assertSame(-1, $r['total']);
    }

    public function test_invoice_number_format(): void
    {
        $this->assertSame('INV-7-2026-0042', InvoiceService::formatNumber(7, 2026, 42));
    }

    public function test_amount_in_words_per_currency(): void
    {
        $this->assertSame('Taka Twelve Lakh Fifty Thousand and Paisa Five Only', NumberToWords::currency('1250000.05', 'BDT'));
        $this->assertSame('Saudi Riyal One Million Two Hundred Fifty Thousand and Halala Fifty Only', NumberToWords::currency('1250000.50', 'SAR'));
        $this->assertSame('US Dollar Zero Only', NumberToWords::currency('0.00', 'USD'));
    }
}
