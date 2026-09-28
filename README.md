# Jira Ops Dashboard

Local operations pipeline: Jira Cloud to PHP to MySQL (`Jira_ops`) to Grafana.

Credentials and OAuth tokens stay on this machine. `src/config.php` and `src/tokens.json` are gitignored.

## Current milestone

OAuth works, the poller can read real tickets, and MySQL upserts them so Grafana can show live data.

Not in this milestone: SLA API, ticket history, shift attribution, Grafana filters, Slack alerts.

## Layout

```
src/poll_jira.php                         Jira search to jira_tickets upsert
sql/schema.sql                            jira_tickets, SLA rows, ticket events
sql/shifts.sql                            shifts, technicians, assignments
sql/seed.sql                              FHD, FHN, BHD, BHN
grafana/README.md                         dashboard variables and panels
launchd/com.jiraops.poller.plist.example  every 5 minutes on a Mac
```

`src/config.php` holds the Jira and MySQL placeholders you fill in. Git ignores that file and `src/tokens.json`.

Run the SQL once, in order, against `Jira_ops`: `schema.sql`, then `shifts.sql`, then `seed.sql`.
