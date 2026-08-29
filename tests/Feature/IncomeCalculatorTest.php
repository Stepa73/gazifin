<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IncomePlan;
use App\Models\Invoice;
use App\Models\User;
use App\Services\IncomeCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(User $user, Client $client, string $issued, string $status, float $total, ?string $due = null, ?string $paidAt = null): Invoice
    {
        return Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => 'F'.$user->id.'-'.$client->id.'-'.substr(md5($issued.$status.$total), 0, 6),
            'issue_date' => $issued,
            'due_date' => $due ?? $issued,
            'status' => $status,
            'paid_at' => $paidAt,
            'subtotal' => $total,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => $total,
            'is_vat_payer' => false,
        ]);
    }

    public function test_first_visit_seeds_one_source_per_client(): void
    {
        $user = User::factory()->create();
        Client::create(['user_id' => $user->id, 'name' => 'Alfa']);
        Client::create(['user_id' => $user->id, 'name' => 'Beta']);

        $this->actingAs($user)->get(route('calculator.index'))->assertOk();

        $plan = IncomePlan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(['Alfa', 'Beta'], $plan->sources->pluck('name')->all());
        $this->assertSame(2, $plan->sources->whereNotNull('client_id')->count());
    }

    public function test_user_without_clients_gets_a_single_blank_source(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('calculator.index'))->assertOk();

        $plan = IncomePlan::where('user_id', $user->id)->firstOrFail();

        $this->assertCount(1, $plan->sources);
        $this->assertNull($plan->sources->first()->client_id);
    }

    public function test_actuals_split_paid_from_merely_invoiced(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);

        $this->invoice($user, $client, '2026-03-20', 'paid', 10000, '2026-04-15', '2026-03-25');
        $this->invoice($user, $client, '2026-03-31', 'sent', 4000, '2026-04-15');

        $actuals = app(IncomeCalculatorService::class)->actuals($user, [2026]);

        $this->assertSame(10000.0, $actuals['byClient'][$client->id]['2026-2']['paid']);
        $this->assertSame(4000.0, $actuals['byClient'][$client->id]['2026-2']['open']);
        $this->assertSame(10000.0, $actuals['totals']['2026-2']['paid']);
    }

    public function test_an_invoice_counts_into_the_month_it_was_issued_not_when_it_is_due(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);

        // Aplikace nastavuje splatnost na 15. dne následujícího měsíce, takže
        // podle splatnosti by červnová práce spadla celá do července.
        $this->invoice($user, $client, '2026-06-18', 'sent', 85000, '2026-07-15');
        $this->invoice($user, $client, '2026-06-30', 'sent', 11000, '2026-07-15');
        $this->invoice($user, $client, '2026-07-06', 'sent', 20000, '2026-08-15');

        $totals = app(IncomeCalculatorService::class)->actuals($user, [2026])['totals'];

        $this->assertSame(96000.0, $totals['2026-5']['open']);
        $this->assertSame(20000.0, $totals['2026-6']['open']);
    }

    public function test_a_paid_invoice_stays_in_its_issue_month_even_when_paid_much_later(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);

        $this->invoice($user, $client, '2026-05-10', 'paid', 7000, '2026-06-15', '2026-08-01');

        $actuals = app(IncomeCalculatorService::class)->actuals($user, [2026]);

        $this->assertSame(7000.0, $actuals['byClient'][$client->id]['2026-4']['paid']);
        $this->assertArrayNotHasKey('2026-7', $actuals['totals']);
    }

    public function test_actuals_ignore_other_users(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $mine = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);
        $theirs = Client::create(['user_id' => $stranger->id, 'name' => 'Cizí']);

        $this->invoice($user, $mine, '2026-02-15', 'paid', 1000, null, '2026-02-15');
        $this->invoice($stranger, $theirs, '2026-02-15', 'paid', 999000, null, '2026-02-15');

        $actuals = app(IncomeCalculatorService::class)->actuals($user, [2026]);

        $this->assertSame(1000.0, $actuals['totals']['2026-1']['paid']);
    }

    public function test_marking_an_invoice_paid_records_the_payment_date(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);
        $invoice = $this->invoice($user, $client, '2026-06-30', 'sent', 5000);

        $this->actingAs($user)->patch(route('invoices.mark-paid', $invoice))->assertRedirect();
        $this->assertSame(now()->toDateString(), $invoice->fresh()->paid_at->toDateString());

        $this->actingAs($user)->patch(route('invoices.mark-unpaid', $invoice))->assertRedirect();
        $this->assertNull($invoice->fresh()->paid_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'regime' => 'pausal',
            'sideActivity' => true,
            'activity' => '80',
            'expMode' => 'real',
            'expReal' => 120000,
            'carryAmount' => 5000,
            'carryMonth' => 2,
            'sources' => [[
                'clientId' => null,
                'name' => 'Uložený zdroj',
                'mode' => 'fixed',
                'rate' => 0,
                'unit' => 'h',
                'hoursPerDay' => 8,
                'lag' => 1,
                'payDay' => 20,
                'from' => '2026-02-01',
                'to' => null,
                'fixed' => 60000,
                'date' => null,
                'amount' => 0,
                'vacation' => array_fill(0, 12, 0),
            ]],
        ], $overrides);
    }

    public function test_plan_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('calculator.update'), $this->payload())
            ->assertOk()
            ->assertJson(['saved' => true]);

        $plan = IncomePlan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('pausal', $plan->regime);
        $this->assertTrue($plan->side_activity);
        $this->assertSame('80', $plan->activity);
        $this->assertSame(2, $plan->carry_month);

        $source = $plan->sources()->firstOrFail();
        $this->assertSame('Uložený zdroj', $source->name);
        $this->assertSame('fixed', $source->mode);
        $this->assertSame('2026-02-01', $source->starts_on->format('Y-m-d'));
        $this->assertNull($source->ends_on);
        $this->assertSame('60000.00', $source->fixed_amount);
    }

    public function test_saving_replaces_previous_sources(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('calculator.update'), $this->payload())->assertOk();
        $this->actingAs($user)->putJson(route('calculator.update'), $this->payload([
            'sources' => [],
        ]))->assertOk();

        $this->assertSame(0, IncomePlan::where('user_id', $user->id)->firstOrFail()->sources()->count());
    }

    public function test_a_saved_plan_comes_back_on_the_next_visit(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);

        $payload = $this->payload();
        $payload['sources'][0]['clientId'] = $client->id;
        $payload['sources'][0]['name'] = 'Alfa — retainer';

        $this->actingAs($user)->putJson(route('calculator.update'), $payload)->assertOk();

        $state = $this->actingAs($user)
            ->get(route('calculator.index'))
            ->assertOk()
            ->viewData('state');

        $this->assertSame('pausal', $state['regime']);
        $this->assertTrue($state['sideActivity']);
        $this->assertCount(1, $state['sources']);
        $this->assertSame('Alfa — retainer', $state['sources'][0]['name']);
        $this->assertSame($client->id, $state['sources'][0]['clientId']);
        $this->assertSame('2026-02-01', $state['sources'][0]['from']);
        $this->assertSame('', $state['sources'][0]['to']);
    }

    public function test_a_deleted_client_leaves_the_source_in_place_unlinked(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);

        $payload = $this->payload();
        $payload['sources'][0]['clientId'] = $client->id;
        $this->actingAs($user)->putJson(route('calculator.update'), $payload)->assertOk();

        $client->delete();

        $state = $this->actingAs($user)->get(route('calculator.index'))->assertOk()->viewData('state');

        $this->assertCount(1, $state['sources']);
        $this->assertNull($state['sources'][0]['clientId']);
    }

    public function test_a_source_cannot_be_linked_to_someone_elses_client(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirClient = Client::create(['user_id' => $stranger->id, 'name' => 'Cizí']);

        $payload = $this->payload();
        $payload['sources'][0]['clientId'] = $theirClient->id;

        $this->actingAs($user)
            ->putJson(route('calculator.update'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('sources.0.clientId');
    }

    public function test_the_page_needs_a_signed_in_user(): void
    {
        $this->get(route('calculator.index'))->assertRedirect(route('login'));
        // Ukládání je XHR, takže odmítnutí přijde jako 401, ne jako HTML redirect.
        $this->putJson(route('calculator.update'), $this->payload())->assertStatus(401);
        $this->assertSame(0, IncomePlan::count());
    }

    public function test_a_rejected_save_answers_in_json_rather_than_redirecting(): void
    {
        $user = User::factory()->create();

        $payload = $this->payload();
        $payload['sources'][0]['mode'] = 'neco-jineho';

        $this->actingAs($user)
            ->putJson(route('calculator.update'), $payload)
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors('sources.0.mode');
    }

    public function test_the_page_carries_the_beta_label_and_real_data(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Alfa']);
        $this->invoice($user, $client, '2026-04-15', 'paid', 33000, '2026-04-15');

        $this->actingAs($user)
            ->get(route('calculator.index'))
            ->assertOk()
            ->assertSee('Beta')
            ->assertSee('Alfa')
            ->assertSee('"2026-3"', false);
    }
}
