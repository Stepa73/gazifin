<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\UserGmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class InvoiceSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_requires_gmail_connection(): void
    {
        $user = User::factory()->create(['google_refresh_token' => null]);
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient', 'email' => 'client@example.com']);
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

        $response = $this->actingAs($user)->post(route('invoices.send', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('draft', $invoice->fresh()->status);
    }

    public function test_send_marks_invoice_as_sent_when_gmail_succeeds(): void
    {
        $user = User::factory()->create([
            'google_refresh_token' => encrypt('refresh-token'),
        ]);
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient', 'email' => 'client@example.com']);
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
        $invoice->items()->create([
            'description' => 'Služba',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->mock(UserGmailService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendInvoice')->once();
        });

        $response = $this->actingAs($user)->post(route('invoices.send', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('sent', $invoice->fresh()->status);
    }
}
