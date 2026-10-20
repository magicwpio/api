<?php
// List your sites, one page at a time. PHP 8 with the curl extension.
// Usage: php list-sites.php

function fail(string $message): never
{
    fwrite(STDERR, "$message\n");
    exit(1);
}

$token = getenv('MAGICWP_TOKEN') ?: fail('Set MAGICWP_TOKEN to an API token (Settings → API tokens)');

$cursor = null;
do {
    $query = http_build_query(array_filter(['limit' => 100, 'cursor' => $cursor]));
    $ch = curl_init("https://api.magicwp.io/v1/sites?$query");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
    ]);
    $body = json_decode(curl_exec($ch), true);
    if (empty($body['success'])) {
        fail(curl_getinfo($ch, CURLINFO_HTTP_CODE) . ': ' . ($body['message'] ?? 'Request failed'));
    }

    foreach ($body['data']['items'] as $site) {
        echo $site['id'], '  ', $site['custom_domain'] ?? $site['domain_name'], '  ', $site['status'], "\n";
    }
    $cursor = $body['data']['next_cursor'] ?? null;
} while ($cursor);
