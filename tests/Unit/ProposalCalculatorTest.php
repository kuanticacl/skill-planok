<?php

namespace Tests\Unit;

use App\Services\Proposals\ProposalCalculator;
use App\Support\Rut;
use PHPUnit\Framework\TestCase;

class ProposalCalculatorTest extends TestCase
{
    public function test_totals_with_percent_discount_and_monthly_services(): void
    {
        $t = ProposalCalculator::totals([
            ['billing' => 'monthly', 'quantity' => 1, 'unit_price' => 650000],
            ['billing' => 'one_time', 'quantity' => 1, 'unit_price' => 890000],
        ], 'percent', 10, 6, 19);

        $this->assertSame(801000, $t['total_one_time']);
        $this->assertSame(585000, $t['total_monthly']);
        $this->assertSame(801000 + 585000 * 6, $t['total_net']);
        $this->assertSame((int) round($t['total_net'] * 0.19), $t['total_tax']);
        $this->assertSame($t['total_net'] + $t['total_tax'], $t['total_gross']);
    }

    public function test_amount_discount_never_exceeds_the_total_and_line_discounts_apply(): void
    {
        $t = ProposalCalculator::totals([['billing' => 'one_time', 'quantity' => 2, 'unit_price' => 100000, 'discount_pct' => 50]], 'amount', 999999999, null, 19);

        $this->assertSame(0, $t['total_net']);

        $t = ProposalCalculator::totals([['billing' => 'one_time', 'quantity' => 2, 'unit_price' => 100000, 'discount_pct' => 50]], 'percent', 0, null, 19);
        $this->assertSame(100000, $t['total_net']);
    }

    public function test_chilean_rut_validation_and_format(): void
    {
        $this->assertTrue(Rut::isValid('76.123.456-0'));
        $this->assertTrue(Rut::isValid('12345678-5'));
        $this->assertFalse(Rut::isValid('12345678-9'));
        $this->assertSame('76.123.456-0', Rut::format('761234560'));
    }
}
