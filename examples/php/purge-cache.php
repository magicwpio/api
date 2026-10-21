<?php
// Purge a site's page cache. PHP 8 with the curl extension.
// Usage: php purge-cache.php <site_id>

function fail(string $message): never
{
    fwrite(STDERR, "$message\n");
    exit(1);
}

$token = getenv('MAGICWP_TOKEN') ?: fail('Set MAGICWP_TOKEN to an API token (Settings → API tokens)');
$siteId = $argv[1] ?? fail('Usage: php purge-cache.php <site_id>');

$ch = curl_init("https://api.magicwp.io/v1/sites/$siteId/nginx/cache/flush");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
]);
$body = json_decode(curl_exec($ch), true);
if (empty($body['success'])) {
    fail(curl_getinfo($ch, CURLINFO_HTTP_CODE) . ': ' . ($body['message'] ?? 'Request failed'));
}
echo $body['message'], "\n";
