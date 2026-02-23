<?php
declare(strict_types=1);

// Start a secure session (required for CSRF + temporarily storing donation details)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => 1,
        'cookie_lifetime' => 0
    ]);
}

// Load environment variables from .env file (same pattern as form-system)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

function pesapal_base_url(): string {
    $env = strtolower((string) (getenv('PESAPAL_ENV') ?: 'production'));
    return ($env === 'sandbox' || $env === 'demo')
        ? 'https://cybqa.pesapal.com/pesapalv3/api'
        : 'https://pay.pesapal.com/v3/api';
}

function pesapal_request_token(): string {
    $key = (string) getenv('PESAPAL_CONSUMER_KEY');
    $secret = (string) getenv('PESAPAL_CONSUMER_SECRET');

    if ($key === '' || $secret === '') {
        throw new RuntimeException('Pesapal keys are not configured. Set PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET in .env');
    }

    $url = pesapal_base_url() . '/Auth/RequestToken';
    $payload = json_encode(['consumer_key' => $key, 'consumer_secret' => $secret], JSON_UNESCAPED_SLASHES);

    $resp = pesapal_http_json('POST', $url, $payload, []);

    if (!isset($resp['token']) || !is_string($resp['token']) || $resp['token'] === '') {
        throw new RuntimeException('Failed to authenticate with Pesapal.');
    }

    return $resp['token'];
}

function pesapal_http_json(string $method, string $url, ?string $jsonBody, array $headers): array {
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize HTTP client.');
    }

    $baseHeaders = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    $mergedHeaders = array_merge($baseHeaders, $headers);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $mergedHeaders,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    if ($jsonBody !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('HTTP request failed: ' . $err);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON response from Pesapal (HTTP ' . $httpCode . ').');
    }

    if ($httpCode >= 400) {
        $msg = isset($decoded['message']) ? (string) $decoded['message'] : 'Pesapal HTTP error.';
        throw new RuntimeException($msg . ' (HTTP ' . $httpCode . ')');
    }

    return $decoded;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool {
    return isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function current_origin(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function pesapal_db(): ?PDO {
    $host = (string) (getenv('DB_HOST') ?: '');
    $user = (string) (getenv('DB_USER') ?: '');
    $pass = (string) (getenv('DB_PASS') ?: '');
    $name = (string) (getenv('DB_NAME') ?: '');

    if ($host === '' || $user === '' || $name === '') {
        return null;
    }

    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    return new PDO($dsn, $user, $pass, $options);
}

function pesapal_ensure_tables(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS donations_pesapal (\n" .
        "  id INT AUTO_INCREMENT PRIMARY KEY,\n" .
        "  merchant_reference VARCHAR(100) NOT NULL UNIQUE,\n" .
        "  order_tracking_id VARCHAR(100) DEFAULT NULL,\n" .
        "  amount DECIMAL(12,2) NOT NULL,\n" .
        "  currency VARCHAR(5) NOT NULL,\n" .
        "  donor_name VARCHAR(255) DEFAULT NULL,\n" .
        "  email VARCHAR(255) DEFAULT NULL,\n" .
        "  phone VARCHAR(50) DEFAULT NULL,\n" .
        "  purpose VARCHAR(255) DEFAULT NULL,\n" .
        "  payment_method VARCHAR(100) DEFAULT NULL,\n" .
        "  confirmation_code VARCHAR(255) DEFAULT NULL,\n" .
        "  status_code INT DEFAULT NULL,\n" .
        "  status_description VARCHAR(255) DEFAULT NULL,\n" .
        "  raw_status_json MEDIUMTEXT DEFAULT NULL,\n" .
        "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n" .
        "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function pesapal_record_init(array $initData): void {
    $pdo = pesapal_db();
    if (!$pdo) return;

    pesapal_ensure_tables($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO donations_pesapal (merchant_reference, amount, currency, donor_name, email, phone, purpose, status_description) ' .
        'VALUES (:merchant_reference, :amount, :currency, :donor_name, :email, :phone, :purpose, :status_description) ' .
        'ON DUPLICATE KEY UPDATE amount=VALUES(amount), currency=VALUES(currency), donor_name=VALUES(donor_name), email=VALUES(email), phone=VALUES(phone), purpose=VALUES(purpose)'
    );

    $stmt->execute([
        ':merchant_reference' => (string) ($initData['merchant_reference'] ?? ''),
        ':amount' => (string) ($initData['amount'] ?? '0'),
        ':currency' => (string) ($initData['currency'] ?? ''),
        ':donor_name' => (string) ($initData['donor_name'] ?? ''),
        ':email' => (string) ($initData['email'] ?? ''),
        ':phone' => (string) ($initData['phone'] ?? ''),
        ':purpose' => (string) ($initData['purpose'] ?? ''),
        ':status_description' => (string) ($initData['status_description'] ?? 'Initiated')
    ]);
}

function pesapal_record_status(string $orderTrackingId, string $merchantReference, array $statusData): void {
    $pdo = pesapal_db();
    if (!$pdo) return;

    pesapal_ensure_tables($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO donations_pesapal (merchant_reference, order_tracking_id, amount, currency, payment_method, confirmation_code, status_code, status_description, raw_status_json) ' .
        'VALUES (:merchant_reference, :order_tracking_id, :amount, :currency, :payment_method, :confirmation_code, :status_code, :status_description, :raw_status_json) ' .
        'ON DUPLICATE KEY UPDATE order_tracking_id=VALUES(order_tracking_id), payment_method=VALUES(payment_method), confirmation_code=VALUES(confirmation_code), status_code=VALUES(status_code), status_description=VALUES(status_description), raw_status_json=VALUES(raw_status_json)'
    );

    $stmt->execute([
        ':merchant_reference' => $merchantReference,
        ':order_tracking_id' => $orderTrackingId,
        ':amount' => (string) ($statusData['amount'] ?? '0'),
        ':currency' => (string) ($statusData['currency'] ?? ''),
        ':payment_method' => (string) ($statusData['payment_method'] ?? ''),
        ':confirmation_code' => (string) ($statusData['confirmation_code'] ?? ''),
        ':status_code' => isset($statusData['status_code']) ? (int) $statusData['status_code'] : null,
        ':status_description' => (string) ($statusData['payment_status_description'] ?? ($statusData['message'] ?? '')),
        ':raw_status_json' => json_encode($statusData, JSON_UNESCAPED_SLASHES)
    ]);
}
