<?php

function loadConfig(): array
{
    $path = __DIR__ . '/config.php';

    if (!is_file($path)) {
        throw new RuntimeException(
            'Missing src/config.php. Copy src/config.example.php to src/config.php and add your OAuth client id and secret.'
        );
    }

    $config = require $path;

    if (!is_array($config)) {
        throw new RuntimeException('src/config.php must return an array.');
    }

    return $config;
}

function tokenFilePath(): string
{
    return __DIR__ . '/tokens.json';
}

function loadTokens(): array
{
    $file = tokenFilePath();

    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data : [];
}

function saveTokens(array $tokens): void
{
    $path = tokenFilePath();

    file_put_contents(
        $path,
        json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    @chmod($path, 0600);
}

function requestJson(
    string $url,
    string $method = 'GET',
    array $headers = [],
    ?array $body = null
): array {
    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($curl);

    if ($response === false) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException('cURL error: ' . $error);
    }

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decoded = json_decode($response, true);

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("HTTP $status\n$response");
    }

    return is_array($decoded) ? $decoded : [];
}

function refreshAccessToken(array $config): array
{
    $tokens = loadTokens();

    if (empty($tokens['refresh_token'])) {
        throw new RuntimeException(
            'No refresh token. Open http://localhost:8081/oauth_start.php and approve access first.'
        );
    }

    $newTokens = requestJson(
        'https://auth.atlassian.com/oauth/token',
        'POST',
        ['Content-Type: application/json'],
        [
            'grant_type' => 'refresh_token',
            'client_id' => $config['atlassian']['client_id'],
            'client_secret' => $config['atlassian']['client_secret'],
            'refresh_token' => $tokens['refresh_token'],
        ]
    );

    $tokens['access_token'] = $newTokens['access_token'];

    if (!empty($newTokens['refresh_token'])) {
        $tokens['refresh_token'] = $newTokens['refresh_token'];
    }

    $tokens['expires_at'] = time() + ($newTokens['expires_in'] ?? 3600) - 60;

    saveTokens($tokens);

    return $tokens;
}

function getValidTokens(array $config): array
{
    $tokens = loadTokens();

    if (empty($tokens['access_token'])) {
        throw new RuntimeException(
            'No access token. Open http://localhost:8081/oauth_start.php and approve access first.'
        );
    }

    if (empty($tokens['expires_at']) || time() >= $tokens['expires_at']) {
        return refreshAccessToken($config);
    }

    return $tokens;
}
