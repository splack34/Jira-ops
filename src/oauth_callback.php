<?php

session_start();

require_once __DIR__ . '/oauth_helpers.php';

try {
    $config = loadConfig();

    if (empty($_GET['code'])) {
        throw new RuntimeException('Missing OAuth authorization code.');
    }

    if (
        empty($_GET['state']) ||
        empty($_SESSION['oauth_state']) ||
        !hash_equals($_SESSION['oauth_state'], (string) $_GET['state'])
    ) {
        throw new RuntimeException('Invalid OAuth state.');
    }

    $tokenResponse = requestJson(
        'https://auth.atlassian.com/oauth/token',
        'POST',
        ['Content-Type: application/json'],
        [
            'grant_type' => 'authorization_code',
            'client_id' => $config['atlassian']['client_id'],
            'client_secret' => $config['atlassian']['client_secret'],
            'code' => $_GET['code'],
            'redirect_uri' => $config['atlassian']['redirect_uri'],
        ]
    );

    $tokenResponse['expires_at'] = time() + ($tokenResponse['expires_in'] ?? 3600) - 60;
    saveTokens($tokenResponse);

    echo '<h2>OAuth successful.</h2>';
    echo '<p>You can close this page.</p>';
    echo '<p>From the src folder, run: php poll_jira.php</p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>OAuth failed.</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
