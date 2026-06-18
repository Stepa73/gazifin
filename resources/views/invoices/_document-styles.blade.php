@if ($forPdf ?? false)
    @page {
        margin: 14mm 18mm 16mm;
        size: A4 portrait;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .invoice-document .invoice-page,
    .invoice-document .invoice-box {
        width: 100%;
        max-width: 100%;
        min-height: 0;
        padding: 0;
        margin: 0;
    }

    .invoice-document .invoice-body {
        padding-bottom: 36mm;
    }

    .invoice-document .footer-wrap {
        position: fixed;
        bottom: 14mm;
        left: 18mm;
        right: 18mm;
        width: auto;
    }
@else
    @font-face {
        font-family: 'DejaVu Sans';
        font-style: normal;
        font-weight: 400;
        src: url('{{ asset('fonts/dejavu/DejaVuSans.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'DejaVu Sans';
        font-style: normal;
        font-weight: 700;
        src: url('{{ asset('fonts/dejavu/DejaVuSans-Bold.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'DejaVu Sans';
        font-style: italic;
        font-weight: 400;
        src: url('{{ asset('fonts/dejavu/DejaVuSans-Oblique.ttf') }}') format('truetype');
    }

    .invoice-document--preview {
        width: 210mm;
        max-width: 210mm;
        min-width: 210mm;
        margin: 0;
        background: #ffffff;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(15, 23, 42, 0.06);
        border: 1px solid #e2e8f0;
    }

    .invoice-document--preview .invoice-page {
        width: 210mm;
        min-height: 297mm;
        padding: 14mm 18mm 16mm;
        box-sizing: border-box;
        background: #ffffff;
    }

    .invoice-document--preview .invoice-box {
        position: relative;
        width: 100%;
        min-height: calc(297mm - 14mm - 16mm);
        padding: 0;
        box-sizing: border-box;
    }

    .invoice-document--preview .invoice-body {
        padding-bottom: 36mm;
    }

    .invoice-document--preview .footer-wrap {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        width: auto;
    }

    .invoice-preview-shell {
        width: 100%;
        overflow: hidden;
    }

    .invoice-preview-frame {
        transform-origin: top left;
    }
@endif

.invoice-document {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11px;
    color: #1e293b;
    line-height: 1.45;
    background: #ffffff;
}

.invoice-document .doc-accent {
    height: 3px;
    background: #007aff;
    margin: 0 0 14px 0;
}

.invoice-document .section-label {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 9px;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 5px;
}

.invoice-document .party-name {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

.invoice-document .party-text {
    font-size: 11px;
    color: #334155;
    line-height: 1.45;
}

.invoice-document .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
    table-layout: fixed;
}

.invoice-document .header-table td {
    vertical-align: top;
    padding: 0;
}

.invoice-document .header-left {
    width: 50%;
    padding-right: 12px;
}

.invoice-document .header-right {
    width: 50%;
    text-align: right;
}

.invoice-document .supplier-block {
    margin-bottom: 16px;
}

.invoice-document .invoice-kicker {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 4px;
}

.invoice-document .invoice-title {
    font-size: 24px;
    line-height: 1.1;
    color: #0f172a;
    font-weight: 700;
    margin-bottom: 10px;
}

.invoice-document .meta-table {
    width: 100%;
    border-collapse: collapse;
}

.invoice-document .meta-table td {
    padding: 2px 0;
    text-align: right;
    font-size: 11px;
}

.invoice-document .meta-table .meta-label {
    color: #64748b;
    padding-right: 10px;
    width: 55%;
}

.invoice-document .meta-table .meta-value {
    font-weight: 700;
    color: #0f172a;
    width: 45%;
    white-space: nowrap;
}

.invoice-document .meta-table .vs-value {
    color: #007aff;
}

.invoice-document .payment-strip {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
    table-layout: fixed;
}

.invoice-document .payment-strip td {
    padding: 10px 8px;
    vertical-align: middle;
    color: #ffffff;
    border-right: 1px solid #334155;
    overflow: hidden;
    word-wrap: break-word;
}

.invoice-document .payment-strip td:last-child {
    border-right: none;
}

.invoice-document .payment-strip .strip-navy {
    background-color: #0f172a;
}

.invoice-document .payment-strip .strip-dark {
    background-color: #1e293b;
}

.invoice-document .payment-strip .strip-col-iban {
    width: 38%;
}

.invoice-document .payment-strip .strip-col-vs {
    width: 20%;
}

.invoice-document .payment-strip .strip-col-date {
    width: 20%;
}

.invoice-document .payment-strip .strip-col-amount {
    width: 22%;
}

.invoice-document .strip-label {
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 5px;
    color: #cbd5e1;
    font-weight: 700;
}

.invoice-document .strip-value {
    font-size: 11px;
    font-weight: 700;
    line-height: 1.3;
    color: #ffffff;
}

.invoice-document .strip-col-iban .strip-value {
    font-size: 9px;
    font-weight: 700;
    word-wrap: break-word;
}

.invoice-document .strip-value-blue {
    color: #93c5fd;
}

.invoice-document .strip-amount {
    font-size: 15px;
    font-weight: 700;
    line-height: 1.2;
    color: #ffffff;
}

.invoice-document .items-intro {
    font-style: italic;
    margin-bottom: 8px;
    font-size: 11px;
    color: #475569;
}

.invoice-document .items-table,
.invoice-document .summary-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.invoice-document .items-table {
    page-break-inside: avoid;
    margin-bottom: 0;
}

.invoice-document .items-table thead th {
    font-weight: 700;
    font-size: 9px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    padding: 6px 4px 8px;
    border-bottom: 2px solid #0f172a;
}

.invoice-document .items-table thead th.col-name,
.invoice-document .summary-table .col-name {
    width: 44%;
    text-align: left;
}

.invoice-document .items-table thead th.col-qty,
.invoice-document .summary-table .col-qty {
    width: 14%;
    text-align: center;
}

.invoice-document .items-table thead th.col-price,
.invoice-document .summary-table .col-price {
    width: 21%;
    text-align: right;
}

.invoice-document .items-table thead th.col-total,
.invoice-document .summary-table .col-total {
    width: 21%;
    text-align: right;
}

.invoice-document .items-table tbody td {
    padding: 8px 4px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    font-size: 11px;
    color: #1e293b;
    word-wrap: break-word;
}

.invoice-document .items-table tbody td.col-name {
    font-weight: 700;
    color: #0f172a;
}

.invoice-document .items-table tbody td.col-qty {
    text-align: center;
    color: #475569;
}

.invoice-document .items-table tbody td.col-price,
.invoice-document .items-table tbody td.col-total {
    text-align: right;
    white-space: nowrap;
}

.invoice-document .summary-table {
    margin-top: 0;
    margin-bottom: 16px;
}

.invoice-document .summary-table td {
    padding: 5px 4px;
    vertical-align: top;
}

.invoice-document .summary-table tr.summary-divider td {
    border-top: 2px solid #0f172a;
    padding: 0;
    height: 0;
    line-height: 0;
    font-size: 0;
}

.invoice-document .summary-table .summary-label {
    text-align: right;
    padding-right: 8px;
    font-size: 11px;
    color: #475569;
}

.invoice-document .summary-table .summary-value {
    text-align: right;
    font-weight: 700;
    font-size: 11px;
    white-space: nowrap;
    color: #0f172a;
}

.invoice-document .summary-table tr.summary-total .summary-label,
.invoice-document .summary-table tr.summary-total .summary-value {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    padding-top: 6px;
}

.invoice-document .summary-table .summary-paid td {
    padding-top: 3px;
    font-size: 10px;
    color: #64748b;
}

.invoice-document .notes {
    margin-bottom: 14px;
    padding: 8px 10px;
    background: #f8fafc;
    border-left: 3px solid #007aff;
    font-size: 10px;
    color: #475569;
    line-height: 1.45;
}

.invoice-document .qr-section {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    margin-bottom: 0;
}

.invoice-document .qr-section td {
    vertical-align: bottom;
    padding: 0;
}

.invoice-document .qr-left {
    width: 50%;
    text-align: left;
}

.invoice-document .qr-right {
    width: 50%;
    text-align: right;
    padding-bottom: 4px;
}

.invoice-document .qr-box img {
    width: 100px;
    height: 100px;
    display: block;
    border: 1px solid #e2e8f0;
}

.invoice-document .qr-label {
    font-size: 9px;
    margin-top: 5px;
    color: #64748b;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-weight: 700;
}

.invoice-document .qr-placeholder {
    width: 100px;
    height: 100px;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
}

.invoice-document .issuer-label {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.invoice-document .issuer-name {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    margin-top: 3px;
}

.invoice-document .footer-wrap {
    page-break-inside: avoid;
}

.invoice-document .footer-issuer {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    table-layout: fixed;
}

.invoice-document .footer-issuer td {
    vertical-align: middle;
    padding: 0;
}

.invoice-document .footer-issuer .footer-issuer-cell {
    width: 25%;
}

.invoice-document .footer-issuer-name {
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
}

.invoice-document .footer-divider {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}

.invoice-document .footer-divider td {
    border-top: 1px solid #cbd5e1;
    height: 1px;
    line-height: 0;
    font-size: 0;
}

.invoice-document .footer-details {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.invoice-document .footer-details td {
    vertical-align: top;
    width: 25%;
    padding: 0 8px 0 0;
    font-size: 9px;
    line-height: 1.45;
    color: #64748b;
    word-wrap: break-word;
}

.invoice-document .footer-details td:last-child {
    padding-right: 0;
}

.invoice-document .footer-details strong {
    color: #334155;
    font-weight: 700;
}
