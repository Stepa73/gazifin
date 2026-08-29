<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIncomePlanRequest;
use App\Models\Client;
use App\Services\IncomeCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IncomeCalculatorController extends Controller
{
    public function __construct(
        private IncomeCalculatorService $calculator,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $plan = $this->calculator->planFor($user);
        $plan->load('sources');

        $years = [
            IncomeCalculatorService::PREV_YEAR,
            IncomeCalculatorService::BASE_YEAR,
            IncomeCalculatorService::NEXT_YEAR,
        ];

        $clients = Client::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('calculator.index', [
            'clients' => $clients,
            'actuals' => $this->calculator->actuals($user, $years),
            'state' => [
                'regime' => $plan->regime,
                'sideActivity' => $plan->side_activity,
                'activity' => $plan->activity,
                'expMode' => $plan->exp_mode,
                'expReal' => (float) $plan->exp_real,
                'carryAmount' => (float) $plan->carry_amount,
                'carryMonth' => $plan->carry_month,
                'sources' => $plan->sources->map->toCalculatorState()->all(),
            ],
            'years' => [
                'prev' => IncomeCalculatorService::PREV_YEAR,
                'base' => IncomeCalculatorService::BASE_YEAR,
                'next' => IncomeCalculatorService::NEXT_YEAR,
            ],
        ]);
    }

    public function update(UpdateIncomePlanRequest $request): JsonResponse
    {
        $plan = $this->calculator->planFor($request->user());

        $this->calculator->save($plan, $request->validated(), $request->validated()['sources']);

        return response()->json(['saved' => true, 'at' => now()->format('H:i')]);
    }
}
