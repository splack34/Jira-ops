<?php

session_start();

require_once __DIR__ . '/oauth_helpers.php';

try {
    $config = loadConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo $e->getMessage();
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'audience' => 'api.atlassian.com',
    'client_id' => $config['atlassian']['client_id'],
    'scope' => implode(' ', $config['atlassian']['scopes']),
    'redirect_uri' => $config['atlassian']['redirect_uri'],
    'state' => $state,
    'response_type' => 'code',
    'prompt' => 'consent',
];

header('Location: https://auth.atlassian.com/authorize?' . http_build_query($params));
exit;
