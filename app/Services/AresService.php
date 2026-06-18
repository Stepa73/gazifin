<?php

namespace App\Services;

use App\Support\CzechIco;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AresService
{
    private const BASE_URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty';

    /**
     * @return array{name: string, address: ?string, ico: string, dic: ?string}
     */
    public function findByIco(string $ico): ?array
    {
        if (! CzechIco::isValid($ico)) {
            throw new RuntimeException('Neplatné IČO.');
        }

        $normalizedIco = CzechIco::normalize($ico);

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get(self::BASE_URL.'/'.$normalizedIco);
        } catch (RequestException) {
            throw new RuntimeException('ARES není momentálně dostupný. Zkuste to prosím později.');
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException('ARES není momentálně dostupný. Zkuste to prosím později.');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        $name = $data['obchodniJmeno'] ?? null;

        if (! is_string($name) || $name === '') {
            return null;
        }

        $address = $data['sidlo']['textovaAdresa'] ?? null;
        $dic = $data['dic'] ?? null;

        return [
            'name' => $name,
            'address' => is_string($address) ? $address : null,
            'ico' => is_string($data['ico'] ?? null) ? $data['ico'] : $normalizedIco,
            'dic' => is_string($dic) ? $dic : null,
        ];
    }
}
