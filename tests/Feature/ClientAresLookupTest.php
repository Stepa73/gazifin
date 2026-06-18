<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientAresLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_lookup_company_by_ico(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'ico' => '27604977',
                'obchodniJmeno' => 'Google Czech Republic, s.r.o.',
                'sidlo' => [
                    'textovaAdresa' => 'Stroupežnického 3191/17, Smíchov, 15000 Praha 5',
                ],
                'dic' => 'CZ27604977',
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('clients.lookup-ico', ['ico' => '27604977']));

        $response->assertOk()
            ->assertJson([
                'name' => 'Google Czech Republic, s.r.o.',
                'ico' => '27604977',
                'dic' => 'CZ27604977',
            ]);
    }

    public function test_guest_cannot_lookup_company_by_ico(): void
    {
        $this->getJson(route('clients.lookup-ico', ['ico' => '27604977']))
            ->assertRedirect(route('login'));
    }
}
