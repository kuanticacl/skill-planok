<?php

namespace App\Services\Proposals;

/**
 * Totales de una propuesta (CLP, enteros, valores netos).
 * El descuento se aplica sobre el total contratado (pago único + mensual × meses) y se reparte
 * proporcionalmente entre ambos, de modo que «mensual» y «único» siempre cuadran con el total.
 */
class ProposalCalculator
{
    /**
     * @param  array<int, array{billing: string, quantity: float|int|string, unit_price: int|string, discount_pct?: int|string}>  $items
     * @return array{subtotal_one_time: float, subtotal_monthly: float, months: int, discount: float, total_one_time: float, total_monthly: float, total_net: float, total_tax: float, total_gross: float}
     */
    public static function totals(array $items, string $discountType, float $discountValue, ?int $months, int $taxRate, int $decimals = 2): array
    {
        $r = fn (float $n): float => round($n, $decimals);
        $oneTime = 0;
        $monthly = 0;

        foreach ($items as $i) {
            $line = $r((float) $i['quantity'] * (float) $i['unit_price'] * (1 - ((int) ($i['discount_pct'] ?? 0)) / 100));
            if (($i['billing'] ?? 'one_time') === 'monthly') {
                $monthly += $line;
            } else {
                $oneTime += $line;
            }
        }

        $m = max(1, (int) $months);
        $base = $oneTime + $monthly * $m;
        $discount = $discountType === 'amount' ? min($discountValue, $base) : $r($base * min(100, $discountValue) / 100);
        $factor = $base > 0 ? ($base - $discount) / $base : 1;

        $totalOne = $r($oneTime * $factor);
        $totalMonthly = $r($monthly * $factor);
        $net = $r($totalOne + $totalMonthly * $m);
        $tax = $r($net * $taxRate / 100);

        return [
            'subtotal_one_time' => $r($oneTime),
            'subtotal_monthly' => $r($monthly),
            'months' => $m,
            'discount' => $r($discount),
            'total_one_time' => $totalOne,
            'total_monthly' => $totalMonthly,
            'total_net' => $net,
            'total_tax' => $tax,
            'total_gross' => $r($net + $tax),
        ];
    }
}
