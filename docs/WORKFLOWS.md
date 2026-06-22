# Business Workflow Runbook

This runbook is for HR operations, supervisors, and payroll administrators.

## 1. Staff Attendance Workflow

1. Open Field Attendance.
2. Click Clock In.
3. Allow browser GPS permission.
4. Continue work shift.
5. Click Clock Out.
6. Confirm ledger row shows in/out timestamps and coordinates.

Outcome:
- One attendance row with geolocation evidence and optional notes.

## 2. HR Profile Setup Workflow

1. Open HR Management.
2. Select Employee Pay Setup tab.
3. Select staff member.
4. Assign department and default shift.
5. Enter pay profile and statutory values.
6. Save profile.

Outcome:
- Payroll profile stored and ready for reporting/payrun.

## 3. Shift Operations Workflow

1. Create/maintain shift templates.
2. Choose shift, date range, and target staff/department.
3. Distribute shift assignments.
4. Review assignment outcomes.

Outcome:
- Shift schedule data available for attendance analytics.

## 4. Leave Management Workflow

1. Record leave request with type and date range.
2. Update status as Pending, Approved, or Rejected.
3. Review leave table for payroll/reporting impact.

Outcome:
- Leave records aligned with operations and reporting.

## 5. Reporting and Payrun Workflow

1. Set reporting date range and optional filters.
2. Review summary cards and report tables.
3. Export CSV report when needed.
4. Generate payrun statement.
5. Validate rows before payroll settlement updates.

Outcome:
- Auditable reports and payrun statement for payroll cycle.

## 6. Project Assignment Workflow

1. Ensure acting role is allowlisted for Project Assignment.
2. Open Project Assignment tab.
3. Enter project name, date range, and status.
4. Select one or many staff.
5. Save assignment and verify history table.

Outcome:
- Project-to-staff allocation with supervisor traceability.

## 7. Role Allowlist Admin Workflow

1. Open Project Assignment panel as admin.
2. Read Current user Staff ID and Role ID indicator.
3. Enter comma-separated role IDs.
4. Save role allowlist.
5. Validate access behavior with test users.

Outcome:
- Strict role-ID controlled access for Project Assignment tab.

## 8. Weekly Operating Rhythm (Recommended)

- Monday: validate shift distributions and profile changes.
- Daily: monitor attendance completion and exceptions.
- Friday: finalize leave updates and manual corrections.
- Week close: run payrun, export reports, perform approvals.
