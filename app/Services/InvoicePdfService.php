<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\CzechBankAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function __construct(
        private PaymentQrService $paymentQrService,
    ) {}

    public function generate(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.pdf', $this->documentData($invoice))
            ->setOption('defaultFont', 'DejaVu Sans');

        $path = "invoices/{$invoice->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function download(Invoice $invoice)
    {
        $path = $this->generate($invoice);

        return response()->download(
            Storage::disk('local')->path($path),
            "faktura-{$invoice->number}.pdf"
        );
    }

    /**
     * @return array{invoice: Invoice, user: \App\Models\User, client: \App\Models\Client, qrCode: ?string, iban: ?string}
     */
    public function documentData(Invoice $invoice): array
    {
        $invoice->load(['client', 'items', 'user']);

        return [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'client' => $invoice->client,
            'qrCode' => $this->paymentQrService->generateBase64($invoice),
            'iban' => $this->formatIban($invoice->user->bank_account),
        ];
    }

    private function formatIban(?string $bankAccount): ?string
    {
        if (! $bankAccount) {
            return null;
        }

        try {
            $iban = CzechBankAccount::toIban($bankAccount);

            return trim(chunk_split($iban, 4, ' '));
        } catch (\Throwable) {
            return null;
        }
    }
}
