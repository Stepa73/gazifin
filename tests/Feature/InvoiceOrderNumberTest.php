<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceOrderNumberTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoice(User $user, ?string $orderNumber): Invoice
    {
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klient s.r.o.',
            'email' => 'client@example.com',
            'address' => "Testovací 1\nPraha",
        ]);

        return Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => '2026-0001',
            'order_number' => $orderNumber,
            'issue_date' => '2026-06-18',
            'due_date' => '2026-07-02',
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);
    }

    public function test_document_shows_order_number_when_filled(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice($user, 'OBJ-2026-42');

        $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Číslo objednávky')
            ->assertSee('OBJ-2026-42');
    }

    public function test_document_hides_order_number_when_empty(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice($user, null);

        $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Číslo objednávky');
    }

    public function test_order_number_is_stored_from_form(): void
    {
        $user = User::factory()->create(['is_vat_payer' => false]);
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klient s.r.o.',
            'address' => "Testovací 1\nPraha",
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'number' => '2026-0002',
            'order_number' => 'OBJ-7',
            'client_id' => $client->id,
            'issue_date' => '2026-06-18',
            'due_date' => '2026-07-02',
            'items' => [
                ['description' => 'Práce', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $this->assertSame('OBJ-7', Invoice::where('number', '2026-0002')->firstOrFail()->order_number);
    }
}
