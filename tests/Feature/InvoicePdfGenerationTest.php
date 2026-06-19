<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoice(User $user): Invoice
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

    public function test_it_generates_pdf_via_gotenberg_when_configured(): void
    {
        config(['services.gotenberg.url' => 'http://gotenberg:3000']);

        Http::fake([
            'gotenberg:3000/forms/chromium/convert/html' => Http::response('%PDF-1.4 gotenberg', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $invoice = $this->createInvoice(User::factory()->create());

        $path = app(InvoicePdfService::class)->generate($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertStringStartsWith(
            '%PDF-1.4 gotenberg',
            Storage::disk('local')->get($path)
        );

        Http::assertSent(fn ($request) => $request->url() === 'http://gotenberg:3000/forms/chromium/convert/html');
    }

    public function test_it_falls_back_to_dompdf_without_gotenberg(): void
    {
        config(['services.gotenberg.url' => null]);

        $invoice = $this->createInvoice(User::factory()->create());

        $path = app(InvoicePdfService::class)->generate($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($path));
    }
}
