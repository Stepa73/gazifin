<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\CzechBankAccount;
use App\Support\Spayd;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use InvalidArgumentException;

class PaymentQrService
{
    public function buildSpayd(Invoice $invoice): ?string
    {
        $invoice->loadMissing('user');
        $bankAccount = $invoice->user->bank_account;

        if (! $bankAccount) {
            return null;
        }

        try {
            $iban = CzechBankAccount::toIban($bankAccount);
        } catch (InvalidArgumentException) {
            return null;
        }

        return Spayd::make()
            ->account($iban)
            ->amount((float) $invoice->total)
            ->currency('CZK')
            ->variableSymbol($invoice->effectiveVariableSymbol())
            ->dueDate($invoice->due_date->format('Ymd'))
            ->recipientName($invoice->user->displayCompanyName())
            ->message('FAKTURA '.$invoice->number)
            ->toString();
    }

    public function generateBase64(Invoice $invoice): ?string
    {
        $spayd = $this->buildSpayd($invoice);

        if (! $spayd) {
            return null;
        }

        $result = (new Builder(
            writer: new PngWriter(),
            data: $spayd,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 200,
            margin: 10,
        ))->build();

        return base64_encode($result->getString());
    }
}
