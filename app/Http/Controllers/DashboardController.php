<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $userId = $user->id;
        $today = now()->toDateString();

        $invoices = Invoice::query()
            ->with('client')
            ->where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'invoices_total' => Invoice::where('user_id', $userId)->count(),
            'invoices_unpaid' => Invoice::where('user_id', $userId)->whereIn('status', ['draft', 'sent'])->count(),
            'clients_total' => Client::where('user_id', $userId)->count(),
        ];

        $overviewCards = [
            [
                'label' => 'Faktury po splatnosti',
                'count' => Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'paid')
                    ->whereDate('due_date', '<', $today)
                    ->count(),
                'total' => (float) Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'paid')
                    ->whereDate('due_date', '<', $today)
                    ->sum('total'),
            ],
            [
                'label' => 'Neuhrazené faktury',
                'count' => Invoice::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', ['draft', 'sent'])
                    ->count(),
                'total' => (float) Invoice::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', ['draft', 'sent'])
                    ->sum('total'),
            ],
            [
                'label' => 'Neodeslané faktury',
                'count' => Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', 'draft')
                    ->count(),
                'total' => (float) Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', 'draft')
                    ->sum('total'),
            ],
            [
                'label' => 'Uhrazené faktury',
                'count' => Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', 'paid')
                    ->count(),
                'total' => (float) Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', 'paid')
                    ->sum('total'),
            ],
        ];

        return view('dashboard', compact('invoices', 'stats', 'overviewCards', 'user'));
    }
}
