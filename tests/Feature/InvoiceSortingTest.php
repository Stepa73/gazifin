<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSortingTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoice(User $user, Client $client, string $number, string $issueDate): Invoice
    {
        return Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => $number,
            'issue_date' => $issueDate,
            'due_date' => '2026-12-31',
            'status' => 'draft',
            'subtotal' => 100,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 100,
            'is_vat_payer' => false,
        ]);
    }

    /**
     * @return array{0: User, 1: Invoice, 2: Invoice}
     */
    private function twoInvoices(): array
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klient s.r.o.']);

        // Založené v opačném pořadí, než v jakém byly vystavené.
        $newest = $this->createInvoice($user, $client, '2026-0001', '2026-08-20');
        $oldest = $this->createInvoice($user, $client, '2026-0002', '2026-01-10');

        return [$user, $oldest, $newest];
    }

    public function test_invoices_can_be_sorted_by_issue_date_ascending(): void
    {
        [$user, $oldest, $newest] = $this->twoInvoices();

        $invoices = $this->actingAs($user)
            ->get(route('invoices.index', ['sort' => 'issue_date', 'direction' => 'asc']))
            ->assertOk()
            ->viewData('invoices');

        $this->assertSame([$oldest->id, $newest->id], $invoices->pluck('id')->all());
    }

    public function test_invoices_can_be_sorted_by_issue_date_descending(): void
    {
        [$user, $oldest, $newest] = $this->twoInvoices();

        $invoices = $this->actingAs($user)
            ->get(route('invoices.index', ['sort' => 'issue_date', 'direction' => 'desc']))
            ->assertOk()
            ->viewData('invoices');

        $this->assertSame([$newest->id, $oldest->id], $invoices->pluck('id')->all());
    }

    public function test_default_listing_keeps_the_newest_created_invoice_first(): void
    {
        [$user, $oldest, $newest] = $this->twoInvoices();

        $invoices = $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->viewData('invoices');

        $this->assertSame([$oldest->id, $newest->id], $invoices->pluck('id')->all());
    }

    public function test_unknown_sort_and_direction_fall_back_to_the_default(): void
    {
        [$user] = $this->twoInvoices();

        $this->actingAs($user)
            ->get(route('invoices.index', ['sort' => 'total; drop table invoices', 'direction' => 'sideways']))
            ->assertOk()
            ->assertViewHas('sort', 'created_at')
            ->assertViewHas('direction', 'desc');
    }

    public function test_both_layouts_render_a_sorting_control(): void
    {
        [$user] = $this->twoInvoices();

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            // Hlavička tabulky (desktop) i přepínač v seznamu (mobil).
            ->assertSee('Seřadit podle data vystavení')
            ->assertSee('Datum vystavení');
    }

    public function test_sorting_link_keeps_the_active_search_and_filter(): void
    {
        [$user] = $this->twoInvoices();

        $this->actingAs($user)
            ->get(route('invoices.index', ['q' => 'Klient', 'status' => 'unpaid']))
            ->assertOk()
            ->assertSee(route('invoices.index', [
                'q' => 'Klient',
                'status' => 'unpaid',
                'sort' => 'issue_date',
                'direction' => 'desc',
            ]));
    }
}
