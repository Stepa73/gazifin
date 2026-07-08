<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_next_number_from_highest_existing_sequence(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient']);

        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0007',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0099',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);

        $this->assertSame('20260100', app(InvoiceService::class)->generateNumber($user));
    }

    public function test_it_sets_due_date_to_fifteenth_of_next_month(): void
    {
        $service = app(InvoiceService::class);

        $this->assertSame('2026-04-15', $service->defaultDueDate('2026-03-20'));
        $this->assertSame('2027-01-15', $service->defaultDueDate('2026-12-05'));
    }
}
