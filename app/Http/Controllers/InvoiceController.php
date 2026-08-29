<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\PaymentQrService;
use App\Services\UserGmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private InvoicePdfService $invoicePdfService,
        private PaymentQrService $paymentQrService,
        private UserGmailService $userGmailService,
    ) {}

    public function index(): View
    {
        $query = Invoice::query()
            ->with('client')
            ->where('user_id', auth()->id());

        $search = request('q', '');
        $statusFilter = request('status', 'all');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($statusFilter === 'unsent') {
            $query->where('status', 'draft');
        } elseif ($statusFilter === 'unpaid') {
            $query->whereIn('status', ['draft', 'sent']);
        } elseif ($statusFilter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($statusFilter === 'overdue') {
            $query->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', now()->toDateString());
        } elseif ($statusFilter === 'this_year') {
            $query->whereYear('issue_date', now()->year);
        }

        $sort = request('sort') === 'issue_date' ? 'issue_date' : 'created_at';
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';

        $invoices = $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate(15)
            ->withQueryString();

        $isFiltered = $search !== '' || $statusFilter !== 'all';

        return view('invoices.index', compact('invoices', 'search', 'statusFilter', 'isFiltered', 'sort', 'direction'));
    }

    public function create(): View
    {
        $clients = Client::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $products = \App\Models\Product::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $user = auth()->user();
        $issueDate = now()->format('Y-m-d');
        $clientNumbers = $this->invoiceService->generateNumbersForClients($user, $clients);
        $selectedClientId = old('client_id');
        $suggestedNumber = $clientNumbers[$selectedClientId] ?? $this->invoiceService->generateNumber($user);
        $invoiceNumber = old('number', $suggestedNumber);
        $variableSymbol = old('variable_symbol', $this->invoiceService->defaultVariableSymbol($invoiceNumber));
        $defaultDueDate = old('due_date', $this->invoiceService->defaultDueDate($issueDate));
        $isVatPayer = $user->is_vat_payer;

        return view('invoices.create', compact(
            'clients',
            'products',
            'invoiceNumber',
            'suggestedNumber',
            'clientNumbers',
            'variableSymbol',
            'issueDate',
            'defaultDueDate',
            'isVatPayer',
        ));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->integer('client_id'));
        $this->authorize('view', $client);

        $user = auth()->user();
        $totals = $this->invoiceService->calculateTotals($request->input('items'), $user->is_vat_payer);
        $number = $request->string('number')->toString();

        $invoice = DB::transaction(function () use ($request, $user, $client, $totals, $number) {
            $invoice = $user->invoices()->create([
                'client_id' => $client->id,
                'number' => $number,
                'order_number' => $request->input('order_number'),
                'issue_date' => $request->input('issue_date'),
                'due_date' => $request->input('due_date'),
                'status' => 'draft',
                'notes' => $request->input('notes'),
                'variable_symbol' => $request->input('variable_symbol') ?: $this->invoiceService->defaultVariableSymbol($number),
                'is_vat_payer' => $user->is_vat_payer,
                ...$totals,
            ]);

            foreach ($request->input('items') as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($quantity * $unitPrice, 2),
                ]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Faktura byla vytvořena.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices.show', $this->invoicePdfService->documentData($invoice));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $invoice->load('items');
        $clients = Client::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $products = \App\Models\Product::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $isVatPayer = $invoice->is_vat_payer;
        $clientNumbers = $this->invoiceService->generateNumbersForClients(auth()->user(), $clients);
        $suggestedNumber = $clientNumbers[$invoice->client_id] ?? $this->invoiceService->generateNumber(auth()->user());

        return view('invoices.edit', compact('invoice', 'clients', 'products', 'isVatPayer', 'suggestedNumber', 'clientNumbers'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $client = Client::findOrFail($request->integer('client_id'));
        $this->authorize('view', $client);

        $totals = $this->invoiceService->calculateTotals($request->input('items'), $invoice->is_vat_payer);

        DB::transaction(function () use ($request, $invoice, $client, $totals) {
            $invoice->update([
                'client_id' => $client->id,
                'number' => $request->string('number')->toString(),
                'order_number' => $request->input('order_number'),
                'issue_date' => $request->input('issue_date'),
                'due_date' => $request->input('due_date'),
                'notes' => $request->input('notes'),
                'variable_symbol' => $request->input('variable_symbol'),
                ...$totals,
            ]);

            $invoice->items()->delete();

            foreach ($request->input('items') as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($quantity * $unitPrice, 2),
                ]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Faktura byla upravena.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Faktura byla smazána.');
    }

    public function markPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update(['status' => 'paid']);

        return back()->with('success', 'Faktura byla označena jako zaplacená.');
    }

    public function markUnpaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update(['status' => 'sent']);

        return back()->with('success', 'Úhrada faktury byla zrušena.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return $this->invoicePdfService->download($invoice);
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $user = auth()->user();

        if (! $user->hasGmailConnected()) {
            return back()->with('error', 'Nejdřív propojte Gmail v profilu.');
        }

        if (! $invoice->client->email) {
            return back()->with('error', 'Klient nemá vyplněný e-mail.');
        }

        $invoice->load('client');

        try {
            $pdfPath = $this->invoicePdfService->generate($invoice);
            $this->userGmailService->sendInvoice($user, $invoice, $pdfPath, $invoice->client->email);
            $invoice->update(['status' => 'sent']);

            return back()->with('success', 'Faktura byla odeslána na '.$invoice->client->email.'.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Odeslání faktury se nezdařilo. Zkontrolujte propojení Gmailu.');
        }
    }
}
