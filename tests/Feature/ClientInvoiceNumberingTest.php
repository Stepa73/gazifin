<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientInvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefix_and_suffix_are_stored_with_the_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Klient s.r.o.',
            'invoice_number_prefix' => 'FA',
            'invoice_number_suffix' => '-',
        ])->assertRedirect(route('clients.index'));

        $client = Client::where('name', 'Klient s.r.o.')->firstOrFail();

        $this->assertSame('FA', $client->invoice_number_prefix);
        $this->assertSame('-', $client->invoice_number_suffix);
    }

    public function test_prefix_with_unsupported_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Klient s.r.o.',
            'invoice_number_prefix' => 'FA 2026!',
        ])->assertSessionHasErrors('invoice_number_prefix');
    }

    public function test_new_invoice_form_offers_the_number_series_of_each_client(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klient s.r.o.',
            'invoice_number_prefix' => 'FA',
            'invoice_number_suffix' => '-',
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => 'FA'.now()->year.'-0012',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);

        $this->actingAs($user)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('FA'.now()->year.'-0013');
    }
}
