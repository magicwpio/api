<?php
// Add a custom domain to a site: print the DNS records to add, wait until
// they're in place, then connect the domain. PHP 8 with the curl extension.
// Usage: php add-domain.php <site_id> <domain>

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

$siteId = $argv[1] ?? fail('Usage: php add-domain.php <site_id> <domain>');
$domain = $argv[2] ?? fail('Usage: php add-domain.php <site_id> <domain>');

// defer_verification: add it as pending and hand back the records, even if DNS isn't set up yet.
$added = call("/sites/$siteId/domains", 'POST', ['domain' => $domain, 'defer_verification' => true]);
echo "Add these records at your DNS provider:\n";
foreach ($added['records'] as $r) {
    echo "  {$r['type']}  {$r['name']}  {$r['value']}\n";
}

$base = "/sites/$siteId/domains/{$added['domain']['id']}";
while (!call("$base/verify", 'POST')['verified']) {
    echo "  not verified yet, checking again in 30 seconds\n";
    sleep(30);
}
echo "Verified, connecting\n";

wait(call("$base/connect", 'POST')['task']['id']);
echo "Connected: https://$domain\n";
