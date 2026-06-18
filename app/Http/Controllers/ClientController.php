<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\AresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ClientController extends Controller
{
    public function __construct(
        private AresService $aresService,
    ) {}

    public function index(): View
    {
        $clients = Client::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        auth()->user()->clients()->create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Klient byl vytvořen.');
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return redirect()->route('clients.index')->with('success', 'Klient byl upraven.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Klient byl smazán.');
    }

    public function lookupByIco(Request $request): JsonResponse
    {
        $request->validate([
            'ico' => ['required', 'string', 'max:20'],
        ]);

        try {
            $company = $this->aresService->findByIco($request->string('ico')->toString());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($company === null) {
            return response()->json(['message' => 'Subjekt s tímto IČO nebyl v ARES nalezen.'], 404);
        }

        return response()->json($company);
    }
}
