<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class QuoteMath
{
    public static function unit(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) BigDecimal::of(trim((string) $value))->toScale(4, RoundingMode::HALF_UP);
    }

    public static function money(mixed $unit, int $quantity): string
    {
        $normalized = self::unit($unit) ?? '0.0000';

        return (string) BigDecimal::of($normalized)
            ->multipliedBy($quantity)
            ->toScale(2, RoundingMode::HALF_UP);
    }

    public static function vat(string $net, mixed $rate): string
    {
        $normalizedRate = ($rate === null || $rate === '') ? '0' : (string) $rate;

        return (string) BigDecimal::of($net)
            ->multipliedBy($normalizedRate)
            ->dividedBy('100', 2, RoundingMode::HALF_UP);
    }

    public static function add(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)->plus($right)->toScale(2, RoundingMode::HALF_UP);
    }

    public static function sub(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)->minus($right)->toScale(2, RoundingMode::HALF_UP);
    }

    public static function rate(string $profit, string $cost): ?string
    {
        $costDecimal = BigDecimal::of($cost);
        if ($costDecimal->isLessThanOrEqualTo('0')) {
            return null;
        }

        return (string) BigDecimal::of($profit)
            ->multipliedBy('100')
            ->dividedBy($costDecimal, 2, RoundingMode::HALF_UP);
    }

    public static function display(?string $amount, int $scale = 2): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $scaled = (string) BigDecimal::of($amount)->toScale($scale, RoundingMode::HALF_UP);
        $negative = str_starts_with($scaled, '-');
        $scaled = ltrim($scaled, '-');
        [$whole, $fraction] = array_pad(explode('.', $scaled, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');
        $wholeWithSeparators = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) ?? $whole;

        $formatted = $wholeWithSeparators;
        if ($scale > 0) {
            $formatted .= ','.$fraction;
        }

        return ($negative ? '-' : '').$formatted;
    }

    public static function displayRate(?string $rate): string
    {
        if ($rate === null || $rate === '') {
            return '—';
        }

        return self::display($rate).'%';
    }
}
