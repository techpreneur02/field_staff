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

1. In HR Settings, confirm payroll groups/cutoff dates, weekly cap and workweek start day (Sunday = 0 through Saturday = 6). The compatibility default is Monday; it is not a legal determination. Attendance is grouped by its work date, and a full shift belongs to that work-date cutoff.
2. Resolve open/malformed attendance, overlapping/out-of-shift breaks and pending corrections. Unpaid breaks reduce payable duration; paid breaks do not. Approved corrections overlay raw punches rather than replacing them. A correction submitted after cutoff close still blocks the affected work-date cutoff.
3. For salary/daily/job/unit employees, use **Approve non-hourly work inputs** in HR Settings. Select the employee/cutoff, dates, explicit quantity/rate, unique source reference and authorized approval attestation:
   - Salary: exactly one amount for the complete cutoff, quantity 1.
   - Daily: quantity 1 and explicit daily rate for every completed work date, backed by valid attendance.
   - Job/unit: explicit approved quantity and rate, source-referenced within the complete selected cutoff.
   - Inputs are append-only. Repeated source references, duplicate salary/daily inputs and changes to approved/paid payroll are rejected. Incorrect approved work inputs require a separately authorized amendment workflow; direct database changes are not an accepted correction process.
4. Generate the selected-cutoff payrun preview. The calculation includes prior-cutoff attendance in the first workweek only to consume its weekly cap; it never pays that attendance again. Each subsequent workweek resets independently.
5. Save draft payroll individually or through the selected-employee payrun. The same engine generates earnings, configured deductions and snapshots. Explicit adjustments are employee-scoped; preview/save/bulk endpoints support the same values for a single selected employee.
6. Open the saved payroll register and submit the draft for review. Direct draft approval is disabled; legacy drafts must first be recalculated by the current engine.
7. Approve only reviewed, current snapshots after attendance/correction and authoritative statutory verification gates pass. Changed inputs require return-to-draft, recalculation and resubmission. Approved/paid payroll cannot return to draft.
8. Record payment using a reference, date, supported method and amount matching saved net pay. Export CSV, print or PDF from the selected-cutoff saved register. These links use saved snapshot totals, not new calculations.

Outcome:
- Audited draft -> under_review -> approved -> paid payroll with protected history and explicit rollback on persistence failure.

Holiday work requires an explicit configured premium multiplier. No rate or statutory entitlement is inferred. Verification must not be marked cleared merely to pass a demo test.

Local numerical/controller regression checks are in `tests/payroll_regression_checks.php`. Real hosted MySQL migration, PDF and permission/session acceptance are separate pending gates; page loads or fixture passes do not prove those gates.

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
2. Locate HR/Payroll Staff Allowlist panel.
3. Use staff name picker to select allowed users.
4. Save allowlist.
5. Validate access behavior with test users.

Outcome:
- Strict staff-ID controlled access for all HR/payroll workspace tabs.

## 8. Weekly Operating Rhythm (Recommended)

- Monday: validate shift distributions and profile changes.
- Daily: monitor attendance completion and exceptions.
- Friday: finalize leave updates and manual corrections.
- Week close: run payrun, export reports, perform approvals.
