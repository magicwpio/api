<?php
// Take a backup of one site and wait for it to finish. PHP 8 with the curl extension.
// Usage: php create-backup.php <site_id> [label]

function fail(string $message): never
{
    fwrite(STDERR, "$message\n");
    exit(1);
}

$token = getenv('MAGICWP_TOKEN') ?: fail('Set MAGICWP_TOKEN to an API token (Settings → API tokens)');
$siteId = $argv[1] ?? fail('Usage: php create-backup.php <site_id> [label]');
$label = $argv[2] ?? 'Backup from the API';

function call(string $path, string $method = 'GET', ?array $body = null, array $headers = []): array
{
    global $token;
    $ch = curl_init("https://api.magicwp.io/v1$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token", 'Content-Type: application/json', ...$headers],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $res = json_decode(curl_exec($ch), true);
    if (empty($res['success'])) {
        fail(curl_getinfo($ch, CURLINFO_HTTP_CODE) . ': ' . ($res['message'] ?? 'Request failed'));
    }
    return $res['data'];
}

// The Idempotency-Key makes a retry of this request safe: it won't start a second backup.
$task = call("/sites/$siteId/backups", 'POST', ['label' => $label], ['Idempotency-Key: ' . bin2hex(random_bytes(16))])['task'];
echo "Backup started, task {$task['id']}\n";

while (true) {
    $t = call("/tasks/{$task['id']}");
    echo "  {$t['status']}\n";
    if ($t['status'] === 'completed') {
        break;
    }
    if ($t['status'] === 'failed') {
        fail($t['error_message'] ?? 'Backup failed');
    }
    sleep(5);
}
