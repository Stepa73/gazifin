<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = auth()->id();
        $year = (int) $request->integer('year', now()->year);

        $invoices = Invoice::query()
            ->where('user_id', $userId)
            ->whereYear('issue_date', $year)
            ->get(['issue_date', 'total', 'status']);

        $months = array_fill(1, 12, 0.0);
        $paidMonths = array_fill(1, 12, 0.0);

        foreach ($invoices as $invoice) {
            $month = (int) $invoice->issue_date->format('n');
            $months[$month] += (float) $invoice->total;

            if ($invoice->status === 'paid') {
                $paidMonths[$month] += (float) $invoice->total;
            }
        }

        $monthNames = ['Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čvn', 'Čvc', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'];

        $chart = [];
        foreach (range(1, 12) as $month) {
            $chart[] = [
                'label' => $monthNames[$month - 1],
                'total' => $months[$month],
                'paid' => $paidMonths[$month],
            ];
        }

        $availableYears = Invoice::query()
            ->where('user_id', $userId)
            ->pluck('issue_date')
            ->map(fn ($date) => (int) $date->format('Y'))
            ->push($year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $summary = [
            'year_total' => array_sum($months),
            'paid_total' => array_sum($paidMonths),
            'count' => $invoices->count(),
            'max_month' => max($months) ?: 0,
        ];

        return view('reports.index', compact('chart', 'year', 'availableYears', 'summary'));
    }
}
