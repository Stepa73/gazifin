<?php

namespace Tests\Unit;

use App\Support\CzechIco;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CzechIcoTest extends TestCase
{
    #[DataProvider('validIcoProvider')]
    public function test_it_validates_known_ico_numbers(string $ico): void
    {
        $this->assertTrue(CzechIco::isValid($ico));
    }

    public static function validIcoProvider(): array
    {
        return [
            ['27604977'],
            ['00006947'],
            ['27074358'],
        ];
    }

    public function test_it_rejects_invalid_ico(): void
    {
        $this->assertFalse(CzechIco::isValid('12345678'));
    }

    public function test_it_normalizes_ico_with_leading_zeros(): void
    {
        $this->assertSame('00006947', CzechIco::normalize('6947'));
    }
}
