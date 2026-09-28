<?php

require_once __DIR__ . '/jira_client.php';

try {
    $config = loadConfig();
    $pdo = require __DIR__ . '/db.php';
    $tokens = getValidTokens($config);
    $cloudId = getCloudId($tokens['access_token']);

    $issues = searchJiraIssues(
        $cloudId,
        $tokens['access_token'],
        $config['jql'],
        [
            'summary',
            'status',
            'priority',
            'assignee',
            'created',
            'updated',
            'resolutiondate',
            'issuetype',
            'project',
        ]
    );

    $sql = "
        INSERT INTO jira_tickets (
            issue_key,
            summary,
            status,
            priority,
            assignee,
            created_at,
            updated_at,
            resolved_at,
            sla_breached,
            first_response_minutes,
            resolution_minutes,
            last_synced_at
        )
        VALUES (
            :issue_key,
            :summary,
            :status,
            :priority,
            :assignee,
            :created_at,
            :updated_at,
            :resolved_at,
            NULL,
            NULL,
            NULL,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            summary = VALUES(summary),
            status = VALUES(status),
            priority = VALUES(priority),
            assignee = VALUES(assignee),
            created_at = VALUES(created_at),
            updated_at = VALUES(updated_at),
            resolved_at = VALUES(resolved_at),
            last_synced_at = NOW()
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($issues as $issue) {
        $fields = $issue['fields'] ?? [];

        $stmt->execute([
            ':issue_key' => $issue['key'] ?? null,
            ':summary' => $fields['summary'] ?? null,
            ':status' => $fields['status']['name'] ?? null,
            ':priority' => $fields['priority']['name'] ?? null,
            ':assignee' => $fields['assignee']['displayName'] ?? null,
            ':created_at' => jiraDateToMysql($fields['created'] ?? null),
            ':updated_at' => jiraDateToMysql($fields['updated'] ?? null),
            ':resolved_at' => jiraDateToMysql($fields['resolutiondate'] ?? null),
        ]);

        $project = $fields['project']['key'] ?? '?';
        $type = $fields['issuetype']['name'] ?? '?';
        echo "Synced {$issue['key']} ({$project} / {$type})\n";
    }

    echo "\nDone. Synced " . count($issues) . " Jira issues.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function jiraDateToMysql(?string $date): ?string
{
    if (!$date) {
        return null;
    }

    return (new DateTime($date))->format('Y-m-d H:i:s');
}
