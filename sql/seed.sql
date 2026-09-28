-- The four real shifts. No fake tickets.
-- Technicians are inserted later with their Jira account id and Slack member id.

INSERT INTO shifts (shift_code, shift_name, half, period, days)
VALUES
    ('FHD', 'Front Half Day', 'front', 'day', 'Sun-Wed'),
    ('FHN', 'Front Half Night', 'front', 'night', 'Sun-Wed'),
    ('BHD', 'Back Half Day', 'back', 'day', 'Wed-Sat'),
    ('BHN', 'Back Half Night', 'back', 'night', 'Wed-Sat')
ON DUPLICATE KEY UPDATE
    shift_name = VALUES(shift_name),
    half = VALUES(half),
    period = VALUES(period),
    days = VALUES(days);
