@php
    $formatMoney = fn (float|string $amount): string => number_format((float) $amount, 2, ',', ' ').' Kč';
    $variableSymbol = $invoice->effectiveVariableSymbol();
@endphp

<div class="doc-accent"></div>

<div class="invoice-body">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="supplier-block">
                    <div class="section-label">Dodavatel</div>
                    <div class="party-name">{{ $user->displayCompanyName() }}</div>
                    <div class="party-text">
                        {!! nl2br(e($user->address)) !!}<br>
                        Česká republika
                    </div>
                </div>

                <div>
                    <div class="section-label">Odběratel</div>
                    <div class="party-name">{{ $client->name }}</div>
                    <div class="party-text">
                        {!! nl2br(e($client->address)) !!}<br>
                        Česká republika
                        @if ($client->ico)
                            <br>IČ: {{ $client->ico }}
                        @endif
                    </div>
                </div>
            </td>
            <td class="header-right">
                <div class="invoice-kicker">Faktura – daňový doklad</div>
                <div class="invoice-title">{{ $invoice->number }}</div>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Datum vystavení</td>
                        <td class="meta-value">{{ $invoice->issue_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Datum splatnosti</td>
                        <td class="meta-value">{{ $invoice->due_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Variabilní symbol</td>
                        <td class="meta-value vs-value">{{ $variableSymbol }}</td>
                    </tr>
                    @if ($invoice->order_number)
                        <tr>
                            <td class="meta-label">Číslo objednávky</td>
                            <td class="meta-value">{{ $invoice->order_number }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="payment-strip">
        <tr>
            <td class="strip-navy strip-col-iban">
                <div class="strip-label">IBAN</div>
                <div class="strip-value">{{ $iban ?? '—' }}</div>
            </td>
            <td class="strip-navy strip-col-vs">
                <div class="strip-label">Variabilní symbol</div>
                <div class="strip-value strip-value-blue">{{ $variableSymbol }}</div>
            </td>
            <td class="strip-navy strip-col-date">
                <div class="strip-label">Datum splatnosti</div>
                <div class="strip-value">{{ $invoice->due_date->format('d.m.Y') }}</div>
            </td>
            <td class="strip-dark strip-col-amount">
                <div class="strip-label">Částka k úhradě</div>
                <div class="strip-amount">{{ $formatMoney($invoice->total) }}</div>
            </td>
        </tr>
    </table>

    <div class="items-intro">Fakturujeme Vám následující položky:</div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-name">Název položky</th>
                <th class="col-qty">Počet</th>
                <th class="col-price">Cena (Kč)</th>
                <th class="col-total">Celkem (Kč)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="col-name">{{ $item->description }}</td>
                    <td class="col-qty">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                    <td class="col-price">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                    <td class="col-total">{{ number_format($item->total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr class="summary-divider">
            <td class="col-name"></td>
            <td class="col-qty"></td>
            <td class="col-price"></td>
            <td class="col-total"></td>
        </tr>
        @if ($invoice->is_vat_payer)
            <tr>
                <td class="col-name"></td>
                <td class="col-qty"></td>
                <td class="summary-label">Základ DPH</td>
                <td class="summary-value">{{ $formatMoney($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td class="col-name"></td>
                <td class="col-qty"></td>
                <td class="summary-label">DPH {{ number_format($invoice->vat_rate, 0) }} %</td>
                <td class="summary-value">{{ $formatMoney($invoice->vat_amount) }}</td>
            </tr>
        @endif
        <tr class="summary-total">
            <td class="col-name"></td>
            <td class="col-qty"></td>
            <td class="summary-label">Celkem (CZK)</td>
            <td class="summary-value">{{ $formatMoney($invoice->total) }}</td>
        </tr>
        @if ($invoice->status === 'paid')
            <tr class="summary-paid">
                <td class="col-name"></td>
                <td class="col-qty"></td>
                <td class="summary-label">Uhrazeno</td>
                <td class="summary-value">{{ $formatMoney($invoice->total) }}</td>
            </tr>
        @endif
    </table>

    @if ($invoice->notes)
        <div class="notes">
            <strong>Poznámka:</strong> {{ $invoice->notes }}
        </div>
    @endif

    <table class="qr-section">
        <tr>
            <td class="qr-left">
                @if ($qrCode)
                    <div class="qr-box">
                        <img src="data:image/png;base64,{{ $qrCode }}" alt="QR platba">
                    </div>
                @else
                    <div class="qr-placeholder"></div>
                @endif
                <div class="qr-label">QR platba</div>
            </td>
            <td class="qr-right">
                <div class="issuer-label">Vystavil</div>
                <div class="issuer-name">{{ $user->name }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer-wrap">
    <table class="footer-issuer">
        <tr>
            <td class="footer-issuer-cell">
                <div class="footer-issuer-name">{{ $user->name }}</div>
            </td>
            <td class="footer-issuer-cell"></td>
            <td class="footer-issuer-cell"></td>
            <td class="footer-issuer-cell"></td>
        </tr>
    </table>

    <table class="footer-divider">
        <tr>
            <td></td>
        </tr>
    </table>

    <table class="footer-details">
        <tr>
            <td>
                <strong>{{ $user->displayCompanyName() }}</strong><br>
                {!! nl2br(e($user->address)) !!}<br>
                Česká republika
            </td>
            <td>
                @if ($user->ico)
                    IČO: {{ $user->ico }}<br>
                @endif
                @if ($invoice->is_vat_payer)
                    DIČ: {{ $user->dic }}<br>
                    Plátce DPH
                @else
                    Neplátce DPH
                @endif
            </td>
            <td>
                @if ($iban)
                    <strong>IBAN</strong><br>
                    {{ $iban }}
                @endif
            </td>
            <td>
                @if ($user->bank_account)
                    <strong>Účet</strong><br>
                    {{ $user->bank_account }}<br>
                @endif
                {{ $user->email }}
            </td>
        </tr>
    </table>
</div>
