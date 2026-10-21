<?php
// Restore a site from a backup and wait for it to finish. PHP 8 with the curl extension.
// With only a site ID it lists the site's backups so you can pick one.
// Usage: php restore-backup.php <site_id> [backup_id] [full|files|db]

function fail(string $message): never
{
    fwrite(STDERR, "$message\n");
    exit(1);
}

$token = getenv('MAGICWP_TOKEN') ?: fail('Set MAGICWP_TOKEN to an API token (Settings → API tokens)');

function call(string $path, string $method = 'GET', ?array $body = null, array $headers = []): ?array
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

// Long actions return a task; poll it until it finishes.
function wait(string $taskId, int $every = 5): void
{
    while (true) {
        $t = call("/tasks/$taskId");
        echo "  {$t['status']}\n";
        if ($t['status'] === 'completed') {
            return;
        }
        if ($t['status'] === 'failed') {
            fail($t['error_message'] ?? 'Task failed');
        }
        sleep($every);
    }
}

$siteId = $argv[1] ?? fail('Usage: php restore-backup.php <site_id> [backup_id] [full|files|db]');
$backupId = $argv[2] ?? null;
$method = $argv[3] ?? 'full';

if ($backupId === null) {
    foreach (call("/sites/$siteId/backups")['items'] as $b) {
        echo $b['id'], '  ', $b['backup_datetime'], '  ', $b['backup_type'], '  ', $b['label'] ?? '', "\n";
    }
    exit;
}

// Restoring overwrites the site. The Idempotency-Key makes a retry safe: it won't restore twice.
$task = call("/sites/$siteId/backups/$backupId/restore", 'POST', ['method' => $method], ['Idempotency-Key: ' . bin2hex(random_bytes(16))])['task'];
echo "Restore started, task {$task['id']}\n";
wait($task['id']);
