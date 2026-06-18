<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => config('demo.email')],
            [
                'name' => 'Demo Uživatel',
                'password' => Hash::make(config('demo.password')),
                'email_verified_at' => now(),
                'company_name' => 'Demo s.r.o.',
                'address' => "Demo ulice 1\n110 00 Praha",
                'ico' => '12345678',
                'dic' => 'CZ12345678',
                'bank_account' => '123456789/0100',
                'is_vat_payer' => true,
            ]
        );

        if ($user->clients()->exists()) {
            return;
        }

        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Ukázkový klient a.s.',
            'email' => 'klient@example.com',
            'address' => "Klientská 5\n602 00 Brno",
            'ico' => '87654321',
            'dic' => 'CZ87654321',
            'phone' => '+420 777 123 456',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => now()->year.'-0001',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'notes' => 'Ukázková faktura pro demo účet.',
            'subtotal' => 10000,
            'vat_rate' => 21,
            'vat_amount' => 2100,
            'total' => 12100,
            'is_vat_payer' => true,
            'variable_symbol' => now()->year.'0001',
        ]);

        $invoice->items()->createMany([
            [
                'description' => 'Konzultační služby',
                'quantity' => 10,
                'unit_price' => 1000,
                'total' => 10000,
            ],
        ]);
    }
}
