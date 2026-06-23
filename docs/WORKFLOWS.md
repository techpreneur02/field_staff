# Business Workflow Runbook

This runbook is for HR operations, supervisors, and payroll administrators.

## 1. Staff Attendance Workflow

1. Open Field Attendance.
2. Click Clock In.
3. Allow browser GPS permission.
4. Continue work shift.
5. Click Clock Out.
6. Confirm ledger row shows in/out timestamps and coordinates.
7. Confirm live map updates position beside attendance controls.
8. On mobile, verify native-style tab strip remains on current tab after refresh.

Outcome:
- One attendance row with geolocation evidence and optional notes.
- Non-admin staff can view only their own attendance ledger history.

## 2. HR Profile Setup Workflow

1. Open HR Management.
2. Select Shift and Department Setup tab.
3. Create required departments and shift templates.
4. Select Employee Pay Setup tab.
5. Select staff member.
6. Assign department and default shift.
7. Enter pay profile and statutory values.
8. Save profile.

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
5. Select all employees or only selected employees for payslip issuance.
6. Apply payrun to issue payslips.
7. Download issued payslips as needed from HR payrun workspace.

Outcome:
- Auditable reports and payrun statement for payroll cycle.

## 6. Project Assignment Workflow

1. Ensure acting staff account is allowlisted for HR/payroll workspace access.
2. Open Project Assignment tab.
3. Enter project name, date range, and status.
4. Select one or many staff.
5. Save assignment and verify history table.

Outcome:
- Project-to-staff allocation with supervisor traceability.

## 7. HR/Payroll Staff Allowlist Admin Workflow

1. Open Payroll Admin tab as admin.
2. Enter master PIN `0212` and unlock edit mode.
3. Locate HR/Payroll Staff Allowlist panel.
4. Use staff name picker to select allowed users.
5. Save allowlist.
6. Validate access behavior with test users.

Outcome:
- Strict staff-ID controlled access for all HR/payroll workspace tabs.

## 8. Holiday Management Workflow

1. Open Holidays tab in HR management.
2. Add holiday date and holiday name.
3. Verify holiday appears in registered holiday list.
4. Generate payrun for a period that includes holiday attendance.
5. Validate holiday hours and holiday pay (2x) are reflected in statement.

Outcome:
- Holiday calendar is centrally managed and payroll applies 2x holiday pay automatically.

## 9. Employee Payslip Download Workflow

1. Open Employee Portal.
2. Go to My Payslips tab.
3. Click Download on a payslip row.
4. Store statement in employee records.

Outcome:
- Staff can securely retrieve their own statement files without HR intervention.

## 10. Weekly Operating Rhythm (Recommended)

- Monday: validate shift distributions and profile changes.
- Daily: monitor attendance completion and exceptions.
- Friday: finalize leave updates and manual corrections.
- Week close: run payrun, export reports, perform approvals.
