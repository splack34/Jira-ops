-- Ticket store for the Jira poller.
-- Safe to run more than once against the existing Jira_ops database.
-- New columns are added only when an older jira_tickets table does not have them.

CREATE TABLE IF NOT EXISTS jira_tickets (
    issue_key VARCHAR(32) NOT NULL,
    project_key VARCHAR(32) NULL,
    issue_type VARCHAR(64) NULL,
    summary TEXT NULL,
    status VARCHAR(64) NULL,
    priority VARCHAR(64) NULL,
    assignee VARCHAR(255) NULL,
    assignee_account_id VARCHAR(128) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    resolved_at DATETIME NULL,
    sla_breached TINYINT(1) NULL,
    first_response_minutes INT NULL,
    resolution_minutes INT NULL,
    last_synced_at DATETIME NULL,
    PRIMARY KEY (issue_key)
);

DROP PROCEDURE IF EXISTS jira_ops_add_column;

DELIMITER $$

CREATE PROCEDURE jira_ops_add_column(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @jira_ops_ddl = CONCAT(
            'ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition
        );
        PREPARE jira_ops_stmt FROM @jira_ops_ddl;
        EXECUTE jira_ops_stmt;
        DEALLOCATE PREPARE jira_ops_stmt;
    END IF;
END$$

DELIMITER ;

CALL jira_ops_add_column('jira_tickets', 'project_key', 'project_key VARCHAR(32) NULL');
CALL jira_ops_add_column('jira_tickets', 'issue_type', 'issue_type VARCHAR(64) NULL');
CALL jira_ops_add_column('jira_tickets', 'assignee_account_id', 'assignee_account_id VARCHAR(128) NULL');
CALL jira_ops_add_column('jira_tickets', 'sla_breached', 'sla_breached TINYINT(1) NULL');
CALL jira_ops_add_column('jira_tickets', 'first_response_minutes', 'first_response_minutes INT NULL');
CALL jira_ops_add_column('jira_tickets', 'resolution_minutes', 'resolution_minutes INT NULL');
CALL jira_ops_add_column('jira_tickets', 'last_synced_at', 'last_synced_at DATETIME NULL');

DROP PROCEDURE IF EXISTS jira_ops_add_column;

-- One row per Jira issue per SLA metric.
-- Filled later from GET /rest/servicedeskapi/request/{issueKey}/sla.
-- The poller does not calculate these from created_at.

CREATE TABLE IF NOT EXISTS jira_sla_current (
    issue_key VARCHAR(32) NOT NULL,
    sla_name VARCHAR(128) NOT NULL,
    goal_duration_ms BIGINT NULL,
    elapsed_ms BIGINT NULL,
    remaining_ms BIGINT NULL,
    breached TINYINT(1) NULL,
    paused TINYINT(1) NULL,
    within_calendar_hours TINYINT(1) NULL,
    breach_time DATETIME NULL,
    cycle_state VARCHAR(32) NULL,
    last_synced_at DATETIME NOT NULL,
    PRIMARY KEY (issue_key, sla_name)
);

-- Status and assignee changes from the Jira changelog.
-- Completion is attributed to the person who made the resolving transition,
-- not to whoever is left in the assignee field at the end.

CREATE TABLE IF NOT EXISTS jira_ticket_events (
    issue_key VARCHAR(32) NOT NULL,
    history_id VARCHAR(64) NOT NULL,
    field_name VARCHAR(128) NOT NULL,
    event_time DATETIME NOT NULL,
    from_value TEXT NULL,
    to_value TEXT NULL,
    actor_account_id VARCHAR(128) NULL,
    actor_display_name VARCHAR(255) NULL,
    PRIMARY KEY (issue_key, history_id, field_name)
);
