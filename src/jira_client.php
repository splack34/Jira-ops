<?php

require_once __DIR__ . '/oauth_helpers.php';

function getCloudId(string $accessToken): string
{
    $resources = requestJson(
        'https://api.atlassian.com/oauth/token/accessible-resources',
        'GET',
        [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ]
    );

    foreach ($resources as $resource) {
        $scopes = $resource['scopes'] ?? [];

        foreach ($scopes as $scope) {
            if (is_string($scope) && str_contains($scope, 'jira')) {
                return $resource['id'];
            }
        }
    }

    if (!empty($resources[0]['id'])) {
        return $resources[0]['id'];
    }

    throw new RuntimeException('No Jira site is available for this OAuth token.');
}

function searchJiraIssues(
    string $cloudId,
    string $accessToken,
    string $jql,
    array $fields,
    int $maxResults = 50
): array {
    $issues = [];
    $nextPageToken = null;

    do {
        $payload = [
            'jql' => $jql,
            'maxResults' => $maxResults,
            'fields' => $fields,
        ];

        if ($nextPageToken !== null) {
            $payload['nextPageToken'] = $nextPageToken;
        }

        $page = requestJson(
            'https://api.atlassian.com/ex/jira/' . rawurlencode($cloudId) . '/rest/api/3/search/jql',
            'POST',
            [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            $payload
        );

        foreach ($page['issues'] ?? [] as $issue) {
            $issues[] = $issue;
        }

        $nextPageToken = $page['nextPageToken'] ?? null;
    } while (is_string($nextPageToken) && $nextPageToken !== '');

    return $issues;
}
