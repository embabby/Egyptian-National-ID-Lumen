<?php

namespace Tests\Unit;

use App\Services\EgyptianNationalIdService;
use PHPUnit\Framework\TestCase;

class EgyptianNationalIdServiceTest extends TestCase
{
    private EgyptianNationalIdService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EgyptianNationalIdService();
    }

    public function test_rejects_non_14_digits(): void
    {
        $this->assertFalse($this->service->validate('123'));
        $this->assertFalse($this->service->validate('123456789012345'));
        $this->assertFalse($this->service->validate('abcdefghijklmn'));
        $this->assertFalse($this->service->validate(''));
    }

    public function test_rejects_invalid_check_digit(): void
    {
        $this->assertFalse($this->service->validate('29001011234560'));
    }

    public function test_rejects_invalid_date(): void
    {
        $invalidDate = '29991301234567'; // invalid month 13
        $this->assertFalse($this->service->validate($invalidDate));
    }

    public function test_rejects_invalid_century(): void
    {
        $invalidCentury = '19001011234567'; // century 1 not valid (only 2 or 3)
        $this->assertFalse($this->service->validate($invalidCentury));
    }

    public function test_valid_id_passes_validation(): void
    {
        $validId = $this->computeValidId('2', '90', '01', '15', '01', '1', '234');
        $this->assertTrue($this->service->validate($validId));
    }

    public function test_extract_returns_valid_false_for_invalid_id(): void
    {
        $result = $this->service->extract('00000000000000');
        $this->assertFalse($result['valid']);
        $this->assertNull($result['birth_date']);
        $this->assertNull($result['gender']);
    }

    public function test_extract_returns_birth_data_for_valid_id(): void
    {
        $validId = $this->computeValidId('2', '95', '06', '20', '21', '1', '001');
        $result = $this->service->extract($validId);

        $this->assertTrue($result['valid']);
        $this->assertSame('1995-06-20', $result['birth_date']);
        $this->assertSame(1995, $result['birth_year']);
        $this->assertSame(6, $result['birth_month']);
        $this->assertSame(20, $result['birth_day']);
        $this->assertSame('male', $result['gender']);
        $this->assertSame('21', $result['governorate_code']);
        $this->assertSame('Giza', $result['governorate_name']);
    }

    public function test_extract_identifies_female(): void
    {
        $validId = $this->computeValidId('3', '00', '01', '01', '01', '2', '000');
        $result = $this->service->extract($validId);
        $this->assertTrue($result['valid']);
        $this->assertSame('female', $result['gender']);
        $this->assertSame(2000, $result['birth_year']);
    }

    public function test_normalizes_whitespace(): void
    {
        $validId = $this->computeValidId('2', '90', '01', '01', '01', '1', '000');
        $withSpaces = substr($validId, 0, 7) . ' ' . substr($validId, 7, 7);
        $this->assertTrue($this->service->validate($withSpaces));
        $result = $this->service->extract($withSpaces);
        $this->assertTrue($result['valid']);
    }

    /**
     * Compute a valid 14-digit Egyptian ID with correct Luhn check digit.
     */
    private function computeValidId(string $century, string $yy, string $mm, string $dd, string $gov, string $genderDigit, string $serial3): string
    {
        $withoutCheck = $century . $yy . $mm . $dd . $gov . $genderDigit . $serial3;
        $digits = array_map('intval', str_split($withoutCheck));
        $sum = 0;
        $len = count($digits);
        for ($i = 0; $i < $len; $i++) {
            $d = $digits[$i];
            if (($len - 1 - $i) % 2 === 0) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }
        $check = (10 - ($sum % 10)) % 10;

        return $withoutCheck . $check;
    }
}
