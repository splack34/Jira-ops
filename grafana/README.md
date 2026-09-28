# Grafana

Datasource: the existing MySQL connection to `Jira_ops`.

Dashboard variables, top to bottom:

| Variable | Values |
| --- | --- |
| Date | Last 24h, 7d, 30d, or a custom range |
| Shift | All, FHD, FHN, BHD, BHN |
| Half | All, Front, Back |
| Period | All, Day, Night |
| Assignee | All, or one technician |
| Priority | All, or one Jira priority |

Panels:

- SLA compliance %
- Tickets completed
- SLA breaches
- Average resolution time
- Completion by shift
- SLA compliance by shift
- Tickets by status
- Tickets by priority
- Ticket volume over time
- Current SLA risk
- Oldest open tickets
- Current ticket table

Shift, half, and period filters use `technician_shift_assignments`. Wednesday belongs to both the front half and the back half, so those panels cannot use the day of the week. They use the assignment whose `effective_from` / `effective_to` covers the event.

Until the changelog poller is writing `jira_ticket_events`, completion means `resolved_at` is set. After that, completion is the resolving status change, and the actor on that event is the technician who completed the ticket.

Ticket table, using the rows the poller writes today:

```sql
SELECT
    issue_key,
    project_key,
    issue_type,
    summary,
    status,
    priority,
    assignee,
    created_at,
    updated_at,
    resolved_at
FROM jira_tickets
WHERE updated_at >= $__timeFrom()
  AND updated_at < $__timeTo()
ORDER BY updated_at DESC;
```

SLA risk and breach alerts use `jira_sla_current.remaining_ms` once that table is being filled from Jira Service Management. Thresholds: 30 minutes remaining is a warning, 10 minutes is critical, zero or negative is breached. Slack mentions use `technicians.slack_user_id`.

Export the dashboard JSON into this folder when the panels are built in Grafana. Do not put webhook URLs or API tokens in the export.
