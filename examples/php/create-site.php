<?php
// Create a WordPress site and wait until it's ready. PHP 8 with the curl extension.
// Usage: php create-site.php [label]   (label: 5-15 characters)

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

$label = $argv[1] ?? 'My new site';

// The Idempotency-Key makes a retry of this request safe: it won't create a second site.
$created = call('/sites', 'POST', ['site_label' => $label, 'site_type' => 'empty_site'], ['Idempotency-Key: ' . bin2hex(random_bytes(16))]);
echo "Creating site {$created['site']['id']}, task {$created['task']['id']}\n";
wait($created['task']['id'], 10);

$site = call("/sites/{$created['site']['id']}");
echo "Ready: https://{$site['domain_name']}\n";
