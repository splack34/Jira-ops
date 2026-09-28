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
            project_key,
            issue_type,
            summary,
            status,
            priority,
            assignee,
            assignee_account_id,
            created_at,
            updated_at,
            resolved_at,
            last_synced_at
        )
        VALUES (
            :issue_key,
            :project_key,
            :issue_type,
            :summary,
            :status,
            :priority,
            :assignee,
            :assignee_account_id,
            :created_at,
            :updated_at,
            :resolved_at,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            project_key = VALUES(project_key),
            issue_type = VALUES(issue_type),
            summary = VALUES(summary),
            status = VALUES(status),
            priority = VALUES(priority),
            assignee = VALUES(assignee),
            assignee_account_id = VALUES(assignee_account_id),
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
            ':project_key' => $fields['project']['key'] ?? null,
            ':issue_type' => $fields['issuetype']['name'] ?? null,
            ':summary' => $fields['summary'] ?? null,
            ':status' => $fields['status']['name'] ?? null,
            ':priority' => $fields['priority']['name'] ?? null,
            ':assignee' => $fields['assignee']['displayName'] ?? null,
            ':assignee_account_id' => $fields['assignee']['accountId'] ?? null,
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
