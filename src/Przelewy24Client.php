<?php

declare(strict_types=1);

/**
 * Klient REST API Przelewy24 (v1) — bez Composera/SDK, tylko curl.
 *
 * Zaimplementowane wg oficjalnej referencyjnej biblioteki PHP Przelewy24
 * (github.com/piatkowski/przelewy24-php, zweryfikowane bezpośrednio w kodzie
 * źródłowym 2026-08-23 — sandbox developerski był niedostępny w momencie
 * pisania tego klienta, więc WSZYSTKIE dane w config/config.local.php są
 * PLACEHOLDERAMI. Kod jest gotowy, ale nie został przetestowany na żywym
 * API — testy w tym repo mockują transport HTTP i sprawdzają wyłącznie
 * logikę po naszej stronie (budowanie żądania, liczenie sign, obsługa
 * błędów). Przed produkcją: podmienić placeholdery na realne dane z panelu
 * Przelewy24 i wykonać RĘCZNY test end-to-end na sandboxie.
 *
 * Adresy bazowe:
 *   sandbox: https://sandbox.przelewy24.pl
 *   live:    https://secure.przelewy24.pl
 * Uwierzytelnianie: HTTP Basic Auth, login = posId, hasło = klucz API.
 * Endpointy:
 *   GET  /api/v1/testAccess              — weryfikacja poświadczeń
 *   POST /api/v1/transaction/register     — rejestracja transakcji
 *   PUT  /api/v1/transaction/verify       — weryfikacja transakcji (UWAGA: PUT, nie GET)
 *
 * Podpis "sign":
 *   register: sha384(json_encode(['sessionId','merchantId'(int),'amount'(int,grosze),'currency','crc']))
 *   verify:   sha384(json_encode(['sessionId','orderId'(int),'amount'(int,grosze),'currency','crc']))
 *   JSON musi być kodowany z JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
 *   inaczej hash się nie zgodzi (P24 liczy sign po swojej stronie z takim
 *   samym, "surowym" formatem JSON).
 */
final class Przelewy24Client
{
    private const BASE_URL_SANDBOX = 'https://sandbox.przelewy24.pl';
    private const BASE_URL_LIVE    = 'https://secure.przelewy24.pl';

    private string $baseUrl;
    private string $merchantId;
    private string $posId;
    private string $crc;
    private string $apiKey;

    /** @var callable(string,string,array,string,string):array{status:int,data:array} */
    private $httpTransport;

    /**
     * @param callable|null $httpTransport Wstrzykiwalny transport HTTP — do testów
     *        (podmiana prawdziwego curla na fałszywkę zwracającą gotową odpowiedź).
     *        W produkcji zostaw null — użyty zostanie curl.
     */
    public function __construct(array $przelewy24Config, ?callable $httpTransport = null)
    {
        $this->baseUrl    = !empty($przelewy24Config['sandbox']) ? self::BASE_URL_SANDBOX : self::BASE_URL_LIVE;
        $this->merchantId = (string) $przelewy24Config['merchant_id'];
        $this->posId      = (string) $przelewy24Config['pos_id'];
        $this->crc        = (string) $przelewy24Config['crc_key'];
        $this->apiKey     = (string) $przelewy24Config['api_key'];
        $this->httpTransport = $httpTransport ?? [$this, 'curlTransport'];
    }

    public static function fromAppConfig(?callable $httpTransport = null): self
    {
        return new self(Database::loadConfig()['przelewy24'], $httpTransport);
    }

    /** @throws RuntimeException gdy poświadczenia są nieprawidłowe / API niedostępne */
    public function testAccess(): bool
    {
        $this->request('GET', '/api/v1/testAccess', []);

        return true; // request() rzuca wyjątek przy statusie innym niż 2xx
    }

    /**
     * Rejestruje transakcję i zwraca pełny URL przekierowania do bramki płatności.
     *
     * @param array{sessionId:string,amount:int,description:string,email:string,urlReturn:string,urlStatus?:string,currency?:string} $params
     *        amount w GROSZACH (najmniejsza jednostka waluty).
     * @throws InvalidArgumentException brak wymaganego pola
     * @throws RuntimeException błąd API / brak tokenu w odpowiedzi
     */
    public function registerTransaction(array $params): string
    {
        foreach (['sessionId', 'amount', 'description', 'email', 'urlReturn'] as $required) {
            $value = $params[$required] ?? null;
            if (empty($value) && $value !== 0) {
                throw new InvalidArgumentException("Przelewy24: brak wymaganego pola \"{$required}\".");
            }
        }

        $currency = $params['currency'] ?? 'PLN';
        $amount   = (int) $params['amount'];

        $body = array_merge(
            [
                'merchantId' => (int) $this->merchantId,
                'posId'      => (int) $this->posId,
                'currency'   => $currency,
                'country'    => 'PL',
                'language'   => 'pl',
            ],
            $params,
            ['amount' => $amount]
        );
        $body['sign'] = $this->signRegister((string) $params['sessionId'], $amount, $currency);

        $response = $this->request('POST', '/api/v1/transaction/register', $body);
        $token = $response['data']['data']['token'] ?? null;

        if (!$token) {
            throw new RuntimeException('Przelewy24: odpowiedź rejestracji transakcji nie zawiera tokenu.');
        }

        return rtrim($this->baseUrl, '/') . '/trnRequest/' . $token;
    }

    /**
     * Autorytatywna weryfikacja transakcji — JEDYNE źródło prawdy o tym, czy
     * płatność faktycznie się powiodła. Nigdy nie ufaj samej treści
     * powiadomienia (notify) bez tego wywołania: notify to tylko sygnał
     * "sprawdź teraz", a nie dowód płatności.
     *
     * @throws RuntimeException błąd połączenia/API — NIE oznacza to automatycznie
     *         "płatność nieudana": może to być przejściowy błąd sieci, więc
     *         wywołujący NIE powinien oznaczać zamówienia jako opłaconego,
     *         ale też nie powinien go automatycznie anulować.
     */
    public function verifyTransaction(string $sessionId, string $orderId, int $amountCents, string $currency = 'PLN'): bool
    {
        $body = [
            'merchantId' => (int) $this->merchantId,
            'posId'      => (int) $this->posId,
            'sessionId'  => $sessionId,
            'amount'     => $amountCents,
            'currency'   => $currency,
            'orderId'    => (int) $orderId,
        ];
        $body['sign'] = $this->signVerify($sessionId, $orderId, $amountCents, $currency);

        // request() rzuca wyjątek przy statusie innym niż 2xx — HTTP 200 z tego
        // endpointu JEST potwierdzeniem (tak działa oficjalna referencyjna
        // implementacja: brak wyjątku == sukces, nie ma dodatkowego pola do
        // sprawdzenia w odpowiedzi).
        $this->request('PUT', '/api/v1/transaction/verify', $body);

        return true;
    }

    private function signRegister(string $sessionId, int $amountCents, string $currency): string
    {
        return $this->sign([
            'sessionId'  => $sessionId,
            'merchantId' => (int) $this->merchantId,
            'amount'     => $amountCents,
            'currency'   => $currency,
            'crc'        => $this->crc,
        ]);
    }

    private function signVerify(string $sessionId, string $orderId, int $amountCents, string $currency): string
    {
        return $this->sign([
            'sessionId' => $sessionId,
            'orderId'   => (int) $orderId,
            'amount'    => $amountCents,
            'currency'  => $currency,
            'crc'       => $this->crc,
        ]);
    }

    private function sign(array $data): string
    {
        return hash('sha384', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @throws RuntimeException przy statusie HTTP spoza zakresu 2xx */
    private function request(string $method, string $path, array $body): array
    {
        $result = ($this->httpTransport)($method, $this->baseUrl . $path, $body, $this->posId, $this->apiKey);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            $errorMsg = $result['data']['error'] ?? ('HTTP ' . $result['status']);
            throw new RuntimeException('Przelewy24 API: ' . $errorMsg);
        }

        return $result;
    }

    /** Domyślny transport produkcyjny — curl. Podmieniany w testach. */
    private function curlTransport(string $method, string $url, array $body, string $posId, string $apiKey): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => $posId . ':' . $apiKey,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        if ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $raw = curl_exec($curl);

        if ($raw === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Przelewy24: błąd połączenia — ' . $error);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return ['status' => $status, 'data' => json_decode($raw, true) ?? []];
    }
}
