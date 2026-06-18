<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PaymentQrService;
use App\Support\CzechBankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentQrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_spayd_string_from_invoice(): void
    {
        $user = User::factory()->create(['bank_account' => '123456789/0100']);
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient']);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0001',
            'issue_date' => '2026-06-18',
            'due_date' => '2026-07-02',
            'status' => 'draft',
            'subtotal' => 1000,
            'vat_rate' => 21,
            'vat_amount' => 210,
            'total' => 1210,
            'is_vat_payer' => true,
            'variable_symbol' => '20260001',
        ]);
        $user->update(['company_name' => 'Demo s.r.o.']);
        $invoice->setRelation('user', $user->fresh());

        $spayd = app(PaymentQrService::class)->buildSpayd($invoice);

        $this->assertStringStartsWith('SPD*1.0*', $spayd);
        $this->assertStringContainsString('ACC:'.CzechBankAccount::toIban('123456789/0100'), $spayd);
        $this->assertStringContainsString('AM:1210.00', $spayd);
        $this->assertStringContainsString('CC:CZK', $spayd);
        $this->assertStringContainsString('X-VS:20260001', $spayd);
        $this->assertStringContainsString('DT:20260702', $spayd);
        $this->assertStringContainsString('MSG:FAKTURA 2026-0001', $spayd);
        $this->assertStringContainsString('RN:DEMO S.R.O.', $spayd);
        $this->assertDoesNotMatchRegularExpression('/MSG:[^*]*[a-z]/', $spayd);
    }

    public function test_generates_base64_qr_image(): void
    {
        $user = User::factory()->create(['bank_account' => '123456789/0100']);
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient']);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0001',
            'issue_date' => '2026-06-18',
            'due_date' => '2026-07-02',
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);

        $base64 = app(PaymentQrService::class)->generateBase64($invoice);

        $this->assertNotNull($base64);
        $this->assertNotFalse(base64_decode($base64, true));
    }

    public function test_returns_null_without_bank_account(): void
    {
        $user = User::factory()->create(['bank_account' => null]);
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient']);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0001',
            'issue_date' => '2026-06-18',
            'due_date' => '2026-07-02',
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);

        $this->assertNull(app(PaymentQrService::class)->buildSpayd($invoice));
        $this->assertNull(app(PaymentQrService::class)->generateBase64($invoice));
    }
}
