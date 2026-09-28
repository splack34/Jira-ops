# Jira Ops Dashboard

Local operations pipeline: Jira Cloud to PHP to MySQL (`Jira_ops`) to Grafana.

Credentials and OAuth tokens stay on this machine. `src/config.php` and `src/tokens.json` are gitignored.

## Current milestone

OAuth works, the poller can read real tickets, and MySQL upserts them so Grafana can show live data.

Not in this milestone: SLA API, ticket history, shift attribution, Grafana filters, Slack alerts.

## Layout

```
src/        PHP poller and OAuth
sql/        schema, shifts, and seed migrations
grafana/    dashboard notes and exports
launchd/    macOS scheduler example
```

`src/config.php` is created locally from `src/config.example.php`. It is never committed.
