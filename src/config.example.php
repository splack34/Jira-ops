<?php

return [
    'atlassian' => [
        'client_id' => 'YOUR_CLIENT_ID',
        'client_secret' => 'YOUR_CLIENT_SECRET',

        // Must exactly match the callback URL in the Atlassian Developer Console.
        'redirect_uri' => 'http://localhost:8081/oauth_callback.php',

        'scopes' => [
            'read:jira-work',
            'offline_access',
        ],
    ],

    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'Jira_ops',
        'username' => 'jira_poller',
        'password' => 'ChangeMe!',
    ],

    // First run can use a wider window, for example: updated >= -30d ORDER BY updated ASC
    // After the table is filled, switch back to a short overlap:
    // updated >= -10m ORDER BY updated ASC
    'jql' => 'updated >= -10m ORDER BY updated ASC',
];
