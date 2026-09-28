-- Front and back halves both work Wednesday, so shift is an assignment,
-- not something derived from the day of the week.

CREATE TABLE IF NOT EXISTS shifts (
    shift_code CHAR(3) NOT NULL,
    shift_name VARCHAR(64) NOT NULL,
    half ENUM('front', 'back') NOT NULL,
    period ENUM('day', 'night') NOT NULL,
    days VARCHAR(32) NOT NULL,
    PRIMARY KEY (shift_code)
);

CREATE TABLE IF NOT EXISTS technicians (
    jira_account_id VARCHAR(128) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    slack_user_id VARCHAR(32) NULL,
    PRIMARY KEY (jira_account_id)
);

CREATE TABLE IF NOT EXISTS technician_shift_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jira_account_id VARCHAR(128) NOT NULL,
    shift_code CHAR(3) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    PRIMARY KEY (id),
    KEY idx_assignment_tech (jira_account_id, effective_from),
    CONSTRAINT fk_assignment_tech
        FOREIGN KEY (jira_account_id) REFERENCES technicians (jira_account_id),
    CONSTRAINT fk_assignment_shift
        FOREIGN KEY (shift_code) REFERENCES shifts (shift_code)
);
