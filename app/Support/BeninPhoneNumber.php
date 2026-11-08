<?php

namespace App\Support;

class BeninPhoneNumber
{
    public const COUNTRY_CODE = '229';

    public const NATIONAL_PREFIX = '01';

    public static function forSms(string $phone, bool $assumeBenin = false): ?string
    {
        $national = self::nationalNumber($phone, $assumeBenin);

        return $national === null ? null : self::COUNTRY_CODE.self::NATIONAL_PREFIX.$national;
    }

    public static function forWhatsApp(string $phone, bool $assumeBenin = false): ?string
    {
        $national = self::nationalNumber($phone, $assumeBenin);

        return $national === null ? null : self::withoutNationalPrefix('+'.self::COUNTRY_CODE.$national);
    }

    public static function withoutNationalPrefix(string $number): string
    {
        return preg_replace('/^\+22901(\d{8})$/', '+229$1', $number) ?? $number;
    }

    private static function nationalNumber(string $phone, bool $assumeBenin): ?string
    {
        $number = preg_replace('/[^0-9+]/', '', $phone);

        if (! is_string($number) || $number === '') {
            return null;
        }

        if (str_starts_with($number, '+')) {
            $digits = substr($number, 1);
        } elseif (str_starts_with($number, '00')) {
            $digits = substr($number, 2);
        } else {
            $digits = $number;
        }

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $national = substr($digits, strlen(self::COUNTRY_CODE));
        } elseif (preg_match('/^'.self::NATIONAL_PREFIX.'\d{8}$/', $digits) === 1) {
            $national = $digits;
        } elseif ($assumeBenin && preg_match('/^\d{8}$/', $digits) === 1) {
            $national = $digits;
        } else {
            return null;
        }

        if (preg_match('/^'.self::NATIONAL_PREFIX.'(\d{8})$/', $national, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^\d{8}$/', $national) === 1) {
            return $national;
        }

        return null;
    }
}
