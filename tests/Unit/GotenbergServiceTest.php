<?php

namespace Tests\Unit;

use App\Services\GotenbergService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GotenbergServiceTest extends TestCase
{
    public function test_it_converts_html_to_pdf_via_gotenberg(): void
    {
        config(['services.gotenberg.url' => 'http://gotenberg:3000']);

        Http::fake([
            'gotenberg:3000/forms/chromium/convert/html' => Http::response('%PDF-1.4 test', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $pdf = app(GotenbergService::class)->htmlToPdf('<html><body>Test</body></html>');

        $this->assertSame('%PDF-1.4 test', $pdf);

        Http::assertSent(fn ($request) => $request->url() === 'http://gotenberg:3000/forms/chromium/convert/html');
    }
}
