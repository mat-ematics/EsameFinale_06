<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;

class ItalianTaxCodeHelper
{
    private const MONTHS = 'ABCDEHLMPRST';
    private const OMOCODIA = 'LMNPQRSTUV';
    private const ODD = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
    private const STRUCTURE = '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/';
    private const MIN_AGE = 18;

    private static function letters(string $text): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($text)));
    }

    public static function surnameCode(string $surname): ?string
    {
        $text = self::letters($surname);
        if ($text === '') return null;

        $consonants = preg_replace('/[AEIOU]/', '', $text);
        $vowels     = preg_replace('/[^AEIOU]/', '', $text);

        return substr($consonants . $vowels . 'XXX', 0, 3);
    }

    public static function nameCode(string $name): ?string
    {
        $text = self::letters($name);
        if ($text === '') return null;

        $consonants = preg_replace('/[AEIOU]/', '', $text);
        $vowels     = preg_replace('/[^AEIOU]/', '', $text);

        if (strlen($consonants) >= 4) return $consonants[0] . $consonants[2] . $consonants[3];
        return substr($consonants . $vowels . 'XXX', 0, 3);
    }

    public static function checkChar(string $first15): string
    {
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $ch    = $first15[$i];
            $index = ctype_digit($ch) ? (int) $ch : ord($ch) - 65;
            $sum  += $i % 2 === 0 ? self::ODD[$index] : $index;
        }
        return chr(65 + $sum % 26);
    }

    /** Decoded data, or null if structure, date or check character are wrong */
    public static function decode(string $raw): ?array
    {
        $code = strtoupper(trim($raw));
        if (!preg_match(self::STRUCTURE, $code)) return null;
        if (self::checkChar(substr($code, 0, 15)) !== $code[15]) return null;

        // Omocodia: letters standing in for digits go back to digits
        $digits = $code;
        foreach ([6, 7, 9, 10, 12, 13, 14] as $position) {
            $value = strpos(self::OMOCODIA, $digits[$position]);
            if ($value !== false) $digits[$position] = (string) $value;
        }

        $yy     = (int) substr($digits, 6, 2);
        $month  = strpos(self::MONTHS, $code[8]) + 1;
        $rawDay = (int) substr($digits, 9, 2);
        $female = $rawDay > 40;
        $day    = $female ? $rawDay - 40 : $rawDay;

        $latest = (int) date('Y') - self::MIN_AGE;
        $year   = 2000 + $yy <= $latest ? 2000 + $yy : 1900 + $yy;

        if (!checkdate($month, $day, $year)) return null;

        return [
            'surname' => substr($code, 0, 3),
            'name'    => substr($code, 3, 3),
            'yy'      => $yy,
            'month'   => $month,
            'day'     => $day,
            'gender'  => $female ? 'female' : 'male',
            'place'   => $code[11] . substr($digits, 12, 3),
        ];
    }

    /** Which of the provided fields contradict the code */
    public static function mismatches(array $decoded, array $input): array
    {
        $out = [];

        $name = self::nameCode((string) ($input['name'] ?? ''));
        if ($name !== null && $name !== $decoded['name']) $out[] = 'first name';

        $surname = self::surnameCode((string) ($input['surname'] ?? ''));
        if ($surname !== null && $surname !== $decoded['surname']) $out[] = 'surname';

        if (!empty($input['birthdate'])) {
            try {
                $date = Carbon::parse($input['birthdate']);
                if ($date->year % 100 !== $decoded['yy'] || $date->month !== $decoded['month'] || $date->day !== $decoded['day']) {
                    $out[] = 'date of birth';
                }
            } catch (\Throwable) {
                // an invalid date is reported by the "date" rule
            }
        }

        $gender = $input['gender'] ?? null;
        if (in_array($gender, ['male', 'female'], true) && $gender !== $decoded['gender']) $out[] = 'gender';

        return $out;
    }
}