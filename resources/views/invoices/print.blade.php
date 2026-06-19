<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Faktura {{ $invoice->number }}</title>
    <style>
        @include('invoices._document-styles', ['forPdf' => false])
        .invoice-document--preview {
            box-shadow: none !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <div class="invoice-document invoice-document--preview">
        <div class="invoice-page">
            <div class="invoice-box">
                @include('invoices._document-content', compact('invoice', 'user', 'client', 'qrCode', 'iban'))
            </div>
        </div>
    </div>
</body>
</html>
