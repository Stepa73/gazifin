<?php

namespace Tests\Unit;

use App\Services\AresService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AresServiceTest extends TestCase
{
    public function test_it_returns_company_data_from_ares(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'ico' => '27604977',
                'obchodniJmeno' => 'Google Czech Republic, s.r.o.',
                'sidlo' => [
                    'textovaAdresa' => 'Stroupežnického 3191/17, Smíchov, 15000 Praha 5',
                ],
                'dic' => 'CZ27604977',
            ]),
        ]);

        $company = app(AresService::class)->findByIco('27604977');

        $this->assertSame([
            'name' => 'Google Czech Republic, s.r.o.',
            'address' => 'Stroupežnického 3191/17, Smíchov, 15000 Praha 5',
            'ico' => '27604977',
            'dic' => 'CZ27604977',
        ], $company);
    }

    public function test_it_returns_null_when_company_is_not_found(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'kod' => 'NENALEZENO',
            ], 404),
        ]);

        $this->assertNull(app(AresService::class)->findByIco('27604977'));
    }

    public function test_it_rejects_invalid_ico_before_calling_ares(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Neplatné IČO.');

        app(AresService::class)->findByIco('12345678');
    }
}
