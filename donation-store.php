<?php

declare(strict_types=1);

function donation_store_path(): string
{
    return __DIR__ . '/data/donations.json';
}

function donation_store_load(): array
{
    $path = donation_store_path();
    if (!file_exists($path)) return [];

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') return [];

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function donation_store_save(array $data): void
{
    $path = donation_store_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Failed to open donation store file.');
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('Failed to lock donation store file.');
        }

        ftruncate($fp, 0);
        rewind($fp);

        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Failed to encode donation store data.');
        }

        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function donation_create(array $donation): array
{
    $data = donation_store_load();

    $id = $donation['id'] ?? '';
    if (!is_string($id) || $id === '') {
        throw new InvalidArgumentException('Donation id is required');
    }

    $data[$id] = $donation;
    donation_store_save($data);
    return $donation;
}

function donation_update(string $id, array $patch): ?array
{
    $data = donation_store_load();
    if (!isset($data[$id]) || !is_array($data[$id])) return null;

    $data[$id] = array_merge($data[$id], $patch);
    donation_store_save($data);
    return $data[$id];
}

function donation_find_by_tracking_id(string $orderTrackingId): ?array
{
    $data = donation_store_load();
    foreach ($data as $row) {
        if (!is_array($row)) continue;
        if (($row['order_tracking_id'] ?? '') === $orderTrackingId) return $row;
    }
    return null;
}

function donation_find_by_merchant_reference(string $merchantReference): ?array
{
    $data = donation_store_load();
    foreach ($data as $row) {
        if (!is_array($row)) continue;
        if (($row['merchant_reference'] ?? '') === $merchantReference) return $row;
    }
    return null;
}

function donation_update_by_tracking_id(string $orderTrackingId, array $patch): ?array
{
    $data = donation_store_load();
    foreach ($data as $id => $row) {
        if (!is_array($row)) continue;
        if (($row['order_tracking_id'] ?? '') !== $orderTrackingId) continue;

        $data[$id] = array_merge($row, $patch);
        donation_store_save($data);
        return $data[$id];
    }
    return null;
}

function donation_update_by_merchant_reference(string $merchantReference, array $patch): ?array
{
    $data = donation_store_load();
    foreach ($data as $id => $row) {
        if (!is_array($row)) continue;
        if (($row['merchant_reference'] ?? '') !== $merchantReference) continue;

        $data[$id] = array_merge($row, $patch);
        donation_store_save($data);
        return $data[$id];
    }
    return null;
}
