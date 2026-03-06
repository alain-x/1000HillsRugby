<?php

declare(strict_types=1);

function pesapal_load_env(): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) return;

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') continue;
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

function pesapal_env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false || $v === '') return $default;
    return $v;
}

function pesapal_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function pesapal_api_base(): string
{
    $env = strtolower((string) pesapal_env('PESAPAL_ENV', 'sandbox'));
    if ($env === 'production' || $env === 'live') return 'https://pay.pesapal.com/v3';
    return 'https://cybqa.pesapal.com/pesapalv3';
}

function pesapal_http_json(string $method, string $url, array $headers = [], ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is required for Pesapal integration.');
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize cURL.');
    }

    $requestHeaders = array_merge([
        'Accept: application/json',
        'Content-Type: application/json',
    ], $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($body !== null) {
        $payload = json_encode($body);
        if ($payload === false) {
            throw new RuntimeException('Failed to encode JSON payload.');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $raw = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Pesapal request failed: ' . $curlErr);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = ['raw' => $raw];
    }

    return ['http_code' => $httpCode, 'data' => $data, 'raw' => $raw];
}

function pesapal_request_token(): string
{
    $key = pesapal_env('PESAPAL_CONSUMER_KEY');
    $secret = pesapal_env('PESAPAL_CONSUMER_SECRET');

    if (!$key || !$secret) {
        throw new RuntimeException('Pesapal credentials are missing. Set PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET in .env');
    }

    $url = pesapal_api_base() . '/api/Auth/RequestToken';
    $res = pesapal_http_json('POST', $url, [], [
        'consumer_key' => $key,
        'consumer_secret' => $secret,
    ]);

    $data = $res['data'] ?? [];
    $httpCode = (int) ($res['http_code'] ?? 0);
    $raw = (string) ($res['raw'] ?? '');
    $token = $data['token'] ?? null;
    if (!is_string($token) || $token === '') {
        $message = $data['message'] ?? ($data['error']['message'] ?? '');
        if (!is_string($message) || trim($message) === '') {
            $message = 'Token request failed';
        }
        throw new RuntimeException($message . ' (HTTP ' . $httpCode . ') ' . $raw);
    }

    return $token;
}

function pesapal_register_ipn(string $token, string $ipnUrl, string $notificationType = 'GET'): string
{
    $url = pesapal_api_base() . '/api/URLSetup/RegisterIPN';

    $res = pesapal_http_json('POST', $url, [
        'Authorization: Bearer ' . $token,
    ], [
        'url' => $ipnUrl,
        'ipn_notification_type' => strtoupper($notificationType) === 'POST' ? 'POST' : 'GET',
    ]);

    $data = $res['data'] ?? [];
    $httpCode = (int) ($res['http_code'] ?? 0);
    $raw = (string) ($res['raw'] ?? '');
    $ipnId = $data['ipn_id'] ?? null;
    if (!is_string($ipnId) || $ipnId === '') {
        $message = $data['message'] ?? ($data['error']['message'] ?? '');
        if (!is_string($message) || trim($message) === '') {
            $message = 'IPN registration failed';
        }
        throw new RuntimeException($message . ' (HTTP ' . $httpCode . ') ' . $raw);
    }

    return $ipnId;
}

function pesapal_submit_order(string $token, array $payload): array
{
    $url = pesapal_api_base() . '/api/Transactions/SubmitOrderRequest';

    $res = pesapal_http_json('POST', $url, [
        'Authorization: Bearer ' . $token,
    ], $payload);

    $data = $res['data'] ?? [];
    $httpCode = (int) ($res['http_code'] ?? 0);
    $raw = (string) ($res['raw'] ?? '');
    $redirectUrl = $data['redirect_url'] ?? null;

    if (!is_string($redirectUrl) || $redirectUrl === '') {
        $message = $data['message'] ?? ($data['error']['message'] ?? '');
        if (!is_string($message) || trim($message) === '') {
            $message = 'SubmitOrderRequest failed';
        }
        throw new RuntimeException($message . ' (HTTP ' . $httpCode . ') ' . $raw);
    }

    return $data;
}

function pesapal_get_transaction_status(string $token, string $orderTrackingId): array
{
    $url = pesapal_api_base() . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode($orderTrackingId);

    $res = pesapal_http_json('GET', $url, [
        'Authorization: Bearer ' . $token,
    ], null);

    $data = $res['data'] ?? [];
    if (!is_array($data)) {
        throw new RuntimeException('Invalid transaction status response.');
    }

    return $data;
}
