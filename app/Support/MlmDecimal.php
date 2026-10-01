<?php

namespace App\Support;

use InvalidArgumentException;

final class MlmDecimal
{
    public const SCALE = 18;

    public static function normalize(string $value, int $scale = self::SCALE): string
    {
        $value = trim($value);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Invalid non-negative decimal value.');
        }

        return bcadd($value, '0', $scale);
    }

    public static function normalizeSigned(string $value, int $scale = self::SCALE): string
    {
        $value = trim($value);
        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Invalid signed decimal value.');
        }

        return bcadd($value, '0', $scale);
    }

    public static function add(string $left, string $right, int $scale = self::SCALE): string
    {
        return bcadd($left, $right, $scale);
    }

    public static function subtract(string $left, string $right, int $scale = self::SCALE): string
    {
        return bcsub($left, $right, $scale);
    }

    public static function multiply(string $left, string $right, int $scale = self::SCALE): string
    {
        return bcmul($left, $right, $scale);
    }

    public static function divide(string $left, string $right, int $scale = self::SCALE): string
    {
        if (bccomp($right, '0', $scale) === 0) {
            throw new InvalidArgumentException('Cannot divide by zero.');
        }

        return bcdiv($left, $right, $scale);
    }

    public static function isPositive(string $value, int $scale = self::SCALE): bool
    {
        return bccomp($value, '0', $scale) === 1;
    }

    public static function negate(string $value, int $scale = self::SCALE): string
    {
        return bcsub('0', $value, $scale);
    }

    public static function minimum(string $left, string $right, int $scale = self::SCALE): string
    {
        return bccomp($left, $right, $scale) <= 0 ? $left : $right;
    }

    public static function absolute(string $value, int $scale = self::SCALE): string
    {
        return bccomp($value, '0', $scale) < 0 ? self::negate($value, $scale) : $value;
    }
}
