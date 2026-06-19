<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GotenbergService
{
    public function renderInvoice(array $data): string
    {
        $assetBaseUrl = config('services.gotenberg.asset_base_url', config('app.url'));

        $html = view('invoices.print', array_merge($data, [
            'assetBaseUrl' => $assetBaseUrl,
        ]))->render();

        return $this->htmlToPdf($html);
    }

    public function htmlToPdf(string $html): string
    {
        $baseUrl = rtrim((string) config('services.gotenberg.url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Gotenberg URL is not configured.');
        }

        $response = Http::timeout(120)
            ->asMultipart()
            ->attach('files', $html, 'index.html')
            ->post("{$baseUrl}/forms/chromium/convert/html", [
                'paperWidth' => '8.27',
                'paperHeight' => '11.69',
                'marginTop' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'marginRight' => '0',
                'printBackground' => 'true',
                'preferCssPageSize' => 'true',
                'scale' => '1',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gotenberg PDF conversion failed: '.$response->status().' '.$response->body()
            );
        }

        return $response->body();
    }
}
