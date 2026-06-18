<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Faktura {{ $invoice->number }}</title>
    <style>
        @include('invoices._document-styles', ['forPdf' => true])
    </style>
</head>
<body class="invoice-document">
    <div class="invoice-page">
        <div class="invoice-box">
            @include('invoices._document-content', compact('invoice', 'user', 'client', 'qrCode', 'iban'))
        </div>
    </div>
</body>
</html>
