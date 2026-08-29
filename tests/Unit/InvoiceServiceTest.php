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

    public function test_it_uses_client_prefix_and_suffix_around_the_year(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klient',
            'invoice_number_prefix' => 'FA',
            'invoice_number_suffix' => '-',
        ]);

        $this->createInvoice($user, $client, 'FA2026-0004');

        $this->assertSame('FA2026-0005', app(InvoiceService::class)->generateNumber($user, $client, 2026));
    }

    public function test_each_client_has_its_own_sequence(): void
    {
        $user = User::factory()->create();
        $withAffixes = Client::create([
            'user_id' => $user->id,
            'name' => 'S předponou',
            'invoice_number_prefix' => 'FA',
        ]);
        $plain = Client::create(['user_id' => $user->id, 'name' => 'Bez předpony']);

        $this->createInvoice($user, $withAffixes, 'FA20260042');
        $this->createInvoice($user, $plain, '20260007');

        $service = app(InvoiceService::class);

        $this->assertSame('FA20260043', $service->generateNumber($user, $withAffixes, 2026));
        $this->assertSame('20260008', $service->generateNumber($user, $plain, 2026));
    }

    public function test_suffix_series_does_not_leak_into_the_plain_series(): void
    {
        $user = User::factory()->create();
        $withSuffix = Client::create([
            'user_id' => $user->id,
            'name' => 'S příponou',
            'invoice_number_suffix' => 'A',
        ]);
        $plain = Client::create(['user_id' => $user->id, 'name' => 'Bez přípony']);

        $this->createInvoice($user, $withSuffix, '2026A0300');

        $this->assertSame('20260001', app(InvoiceService::class)->generateNumber($user, $plain, 2026));
    }

    public function test_it_generates_a_number_for_every_client_at_once(): void
    {
        $user = User::factory()->create();
        $first = Client::create([
            'user_id' => $user->id,
            'name' => 'První',
            'invoice_number_prefix' => 'FA',
        ]);
        $second = Client::create(['user_id' => $user->id, 'name' => 'Druhý']);

        $this->createInvoice($user, $first, 'FA20260010');

        $numbers = app(InvoiceService::class)->generateNumbersForClients(
            $user,
            Client::whereIn('id', [$first->id, $second->id])->get(),
            2026,
        );

        $this->assertSame([
            $first->id => 'FA20260011',
            $second->id => '20260001',
        ], $numbers);
    }

    private function createInvoice(User $user, Client $client, string $number): Invoice
    {
        return Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => $number,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);
    }

    public function test_it_sets_due_date_to_fifteenth_of_next_month(): void
    {
        $service = app(InvoiceService::class);

        $this->assertSame('2026-04-15', $service->defaultDueDate('2026-03-20'));
        $this->assertSame('2027-01-15', $service->defaultDueDate('2026-12-05'));
    }
}
