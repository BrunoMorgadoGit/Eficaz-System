<?php

namespace App\Services;

use App\Models\Reseller;
use InvalidArgumentException;

class PricingService
{
    /**
     * The commercial rule intentionally stays small for the MVP: the reseller
     * profile determines one discount for the entire quotation.
     */
    public function discountRateFor(Reseller $reseller): int
    {
        return match (strtoupper(trim((string) $reseller->commercial_profile))) {
            'STANDARD' => 0,
            'GOLD' => 5,
            'PREMIUM' => 10,
            default => throw new InvalidArgumentException('Perfil comercial inválido para cálculo de preço.'),
        };
    }

    /**
     * @param  iterable<array<string, mixed>|object>  $items
     * @return array{subtotal: string, discount: string, total: string, discount_rate: int}
     */
    public function calculate(iterable $items, Reseller $reseller): array
    {
        $subtotalCents = 0;

        foreach ($items as $item) {
            $quantity = (int) data_get($item, 'quantity', 0);
            $subtotalCents += $this->toCents(data_get($item, 'unit_price')) * $quantity;

            if ($quantity < 1) {
                throw new InvalidArgumentException('A quantidade de um item deve ser maior que zero.');
            }
        }

        $discountRate = $this->discountRateFor($reseller);
        $discountCents = intdiv(($subtotalCents * $discountRate) + 50, 100);

        return [
            'subtotal' => $this->fromCents($subtotalCents),
            'discount' => $this->fromCents($discountCents),
            'total' => $this->fromCents($subtotalCents - $discountCents),
            'discount_rate' => $discountRate,
        ];
    }

    public function lineTotal(mixed $unitPrice, int $quantity): string
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('A quantidade de um item deve ser maior que zero.');
        }

        return $this->fromCents($this->toCents($unitPrice) * $quantity);
    }

    /** @param iterable<array<string, mixed>|object> $items */
    public function sumLineTotals(iterable $items): string
    {
        $totalCents = 0;

        foreach ($items as $item) {
            $totalCents += $this->toCents(data_get($item, 'line_total'));
        }

        return $this->fromCents($totalCents);
    }

    public function amountsMatch(mixed $left, mixed $right): bool
    {
        return $this->toCents($left) === $this->toCents($right);
    }

    public function toCents(mixed $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        if (is_float($amount)) {
            return (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
        }

        $value = str_replace(',', '.', trim((string) $amount));

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$cents : $cents;
    }

    public function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
