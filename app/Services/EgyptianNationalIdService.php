<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Egyptian National ID validator and data extractor.
 *
 * Format (14 digits): C YY MM DD GG S GGG C
 * - C (digit 1): Century — 2 = 1900–1999, 3 = 2000–2099
 * - YY MM DD (digits 2–7): Birth date
 * - GG (digits 8–9): Governorate code
 * - S (digit 10): Gender — odd = male, even = female (first digit of serial)
 * - GGG (digits 11–13): Serial number continuation
 * - C (digit 14): Check digit (Luhn)
 */
class EgyptianNationalIdService
{
    private const LENGTH = 14;

    /**
     * Official Egyptian governorate codes (Civil Registry).
     */
    private const GOVERNORATES = [
        '01' => 'Cairo',
        '02' => 'Alexandria',
        '03' => 'Port Said',
        '04' => 'Suez',
        '11' => 'Damietta',
        '12' => 'Dakahlia',
        '13' => 'Sharqia',
        '14' => 'Qalyubia',
        '15' => 'Kafr El Sheikh',
        '16' => 'Gharbia',
        '17' => 'Monufia',
        '18' => 'Beheira',
        '19' => 'Ismailia',
        '21' => 'Giza',
        '22' => 'Beni Suef',
        '23' => 'Fayoum',
        '24' => 'Minya',
        '25' => 'Asyut',
        '26' => 'Sohag',
        '27' => 'Qena',
        '28' => 'Aswan',
        '29' => 'Luxor',
        '31' => 'Red Sea',
        '32' => 'New Valley',
        '33' => 'Matruh',
        '34' => 'North Sinai',
        '35' => 'South Sinai',
        '88' => 'Outside the republic',
    ];

    /**
     * Validate the national ID format and check digit.
     */
    public function validate(string $nationalId): bool
    {
        $nationalId = $this->normalize($nationalId);

        if (strlen($nationalId) !== self::LENGTH || !ctype_digit($nationalId)) {
            return false;
        }

        if (!$this->validateCheckDigit($nationalId)) {
            return false;
        }

        return $this->validateStructure($nationalId);
    }

    /**
     * Extract structured data from a valid national ID.
     *
     * @return array{valid: bool, birth_date: string|null, birth_year: int|null, birth_month: int|null, birth_day: int|null, gender: string|null, governorate_code: string|null, governorate_name: string|null}
     */
    public function extract(string $nationalId): array
    {
        $nationalId = $this->normalize($nationalId);

        $result = [
            'valid' => false,
            'birth_date' => null,
            'birth_year' => null,
            'birth_month' => null,
            'birth_day' => null,
            'gender' => null,
            'governorate_code' => null,
            'governorate_name' => null,
        ];

        if (!$this->validate($nationalId)) {
            return $result;
        }

        $result['valid'] = true;
        $result['birth_year'] = (int) $this->getBirthYear($nationalId);
        $result['birth_month'] = (int) substr($nationalId, 3, 2);
        $result['birth_day'] = (int) substr($nationalId, 5, 2);
        $result['birth_date'] = sprintf(
            '%04d-%02d-%02d',
            $result['birth_year'],
            $result['birth_month'],
            $result['birth_day']
        );
        $result['gender'] = $this->getGender($nationalId);
        $result['governorate_code'] = substr($nationalId, 7, 2);
        $result['governorate_name'] = self::GOVERNORATES[$result['governorate_code']] ?? null;

        return $result;
    }

    /**
     * Validate and extract in one call. Throws on invalid ID if strict.
     *
     * @throws InvalidArgumentException when ID is invalid and $throwOnInvalid is true
     */
    public function validateAndExtract(string $nationalId, bool $throwOnInvalid = false): array
    {
        $data = $this->extract($nationalId);

        if ($throwOnInvalid && !$data['valid']) {
            throw new InvalidArgumentException('Invalid Egyptian national ID.');
        }

        return $data;
    }

    private function normalize(string $nationalId): string
    {
        return preg_replace('/\s+/', '', $nationalId);
    }

    /**
     * Luhn check digit validation (last digit).
     */
    private function validateCheckDigit(string $nationalId): bool
    {
        $digits = array_map('intval', str_split($nationalId));
        $checkDigit = array_pop($digits);

        $sum = 0;
        $length = count($digits);

        for ($i = 0; $i < $length; $i++) {
            $digit = $digits[$i];
            if (($length - 1 - $i) % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        $expectedCheck = (10 - ($sum % 10)) % 10;

        return $checkDigit === $expectedCheck;
    }

    private function validateStructure(string $nationalId): bool
    {
        $century = (int) $nationalId[0];
        if (!in_array($century, [2, 3], true)) {
            return false;
        }

        $month = (int) substr($nationalId, 3, 2);
        if ($month < 1 || $month > 12) {
            return false;
        }

        $day = (int) substr($nationalId, 5, 2);
        if ($day < 1 || $day > 31) {
            return false;
        }

        if (!checkdate($month, $day, $this->getBirthYear($nationalId))) {
            return false;
        }

        $governorateCode = substr($nationalId, 7, 2);
        if (!array_key_exists($governorateCode, self::GOVERNORATES)) {
            return false;
        }

        return true;
    }

    private function getBirthYear(string $nationalId): string
    {
        $century = $nationalId[0];
        $yy = substr($nationalId, 1, 2);
        $baseYear = $century === '2' ? 1900 : 2000;

        return (string) ($baseYear + (int) $yy);
    }

    private function getGender(string $nationalId): string
    {
        $genderDigit = (int) $nationalId[9]; // 10th digit (0-indexed: 9)

        return $genderDigit % 2 === 1 ? 'male' : 'female';
    }
}
