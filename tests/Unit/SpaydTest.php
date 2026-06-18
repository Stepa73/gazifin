<?php

namespace Tests\Unit;

use App\Support\Spayd;
use Tests\TestCase;

class SpaydTest extends TestCase
{
    public function test_builds_canonical_spayd_string(): void
    {
        $spayd = Spayd::make()
            ->account('CZ6508000000192000145399')
            ->amount(450.5)
            ->currency('CZK')
            ->variableSymbol('1234567890')
            ->dueDate('20121231')
            ->recipientName('Petr Dvořák')
            ->message('Platba za zboží')
            ->toString();

        $this->assertSame(
            'SPD*1.0*ACC:CZ6508000000192000145399*AM:450.50*CC:CZK*X-VS:1234567890*DT:20121231*RN:PETR DVORAK*MSG:PLATBA ZA ZBOZI',
            $spayd,
        );
    }
}
