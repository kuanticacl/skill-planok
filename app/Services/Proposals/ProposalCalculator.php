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
     * @return array{subtotal_one_time: int, subtotal_monthly: int, months: int, discount: int, total_one_time: int, total_monthly: int, total_net: int, total_tax: int, total_gross: int}
     */
    public static function totals(array $items, string $discountType, int $discountValue, ?int $months, int $taxRate): array
    {
        $oneTime = 0;
        $monthly = 0;

        foreach ($items as $i) {
            $line = (int) round((float) $i['quantity'] * (int) $i['unit_price'] * (1 - ((int) ($i['discount_pct'] ?? 0)) / 100));
            if (($i['billing'] ?? 'one_time') === 'monthly') {
                $monthly += $line;
            } else {
                $oneTime += $line;
            }
        }

        $m = max(1, (int) $months);
        $base = $oneTime + $monthly * $m;
        $discount = $discountType === 'amount' ? min($discountValue, $base) : (int) round($base * min(100, $discountValue) / 100);
        $factor = $base > 0 ? ($base - $discount) / $base : 1;

        $totalOne = (int) round($oneTime * $factor);
        $totalMonthly = (int) round($monthly * $factor);
        $net = $totalOne + $totalMonthly * $m;
        $tax = (int) round($net * $taxRate / 100);

        return [
            'subtotal_one_time' => $oneTime,
            'subtotal_monthly' => $monthly,
            'months' => $m,
            'discount' => $discount,
            'total_one_time' => $totalOne,
            'total_monthly' => $totalMonthly,
            'total_net' => $net,
            'total_tax' => $tax,
            'total_gross' => $net + $tax,
        ];
    }
}
