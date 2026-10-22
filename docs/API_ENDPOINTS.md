# API Endpoints and Controller Actions

Base path prefix in examples:
- admin/field_staff/

All mutating actions should be called with POST and CSRF tokens in authenticated admin context.

## Payroll calculation contract (2026-10-22)

`POST generate_payrun`, `POST save_payroll` and `POST apply_payrun` require a valid `cutoff_id`; the server derives its date window and validates employee group membership. Preview and bulk save use the same versioned engine as individual save. Explicit single-employee adjustments use `commission`, `vacation_pay`, `loan_adjustment` and `advance` (finite non-negative values). Multi-employee requests cannot apply one employee's explicit adjustments to everyone.

Unresolved timestamps, open shifts, malformed/overlapping/out-of-shift breaks and pending work-date corrections reject calculation. Payable hours retain paid breaks and deduct unpaid breaks. Workweek totals include earlier-cutoff work dates for cap consumption only.

### POST save_payroll_work_record

Authorization: the existing HR Settings/manage-settings gate; POST and normal CSRF validation.

Fields:
- `staff_id`, `cutoff_id`: employee and editable cutoff in the same payroll group.
- `start_date`, `end_date`: valid work dates within the selected cutoff.
- `quantity`, `rate`: explicit finite positive numbers; no fallback rates/quantities.
- `reference`: unique approved source reference, up to 120 characters.
- `attest_approved=1`: authorized actor attests to approval against the source record.

The server resolves `payment_basis` from the employee's policy, not from a client-supplied override. Salary needs one complete-cutoff amount and quantity 1; daily needs one work date and quantity 1. Every completed daily work date must have a record before calculation. Job/unit quantities use their explicit approved rates. Actor and approval time are stored server-side.

Work-input writes lock the cutoff and reject duplicates or replacements for approved/paid payroll. Inputs are append-only; this endpoint is not an edit/revoke API.

### POST payroll_workflow_action

Fields: `payroll_id`, `action`, `notes`; payment also requires `payment_reference`, `payment_method` (`cash|online_transfer|check`), `payment_date` (`Y-m-d`) and `payment_amount` equal to saved net salary.

Transitions:
- `submit_for_review`: draft -> under_review, with saved snapshot.
- `return_to_draft`: under_review -> draft only, updating snapshot status too.
- `approve`: under_review -> approved only; current engine snapshot, unchanged calculation inputs, cleared attendance/corrections and verified statutory configuration required.
- `record_payment`: approved -> paid only; paid history cannot be reopened.

No direct-draft-approval mode is configured. Invalid transitions, legacy unapproved calculations and stale snapshots return `success=false`. Controllers lock payroll rows and roll back snapshot/component/audit persistence failures.

### Attendance correction fields

Correction types: `clock_in`, `clock_out`, `break:<id>:break_out_at`, `break:<id>:break_in_at`, or `break:<id>:ended_at`. Legacy `attendance` is accepted only when its before-value identifies exactly one clock field. `before_value` must match the effective source field, and `after_value` must be a valid timestamp. Only pending requests can be approved/rejected; approved overlays preserve raw clock and break punches.

## 1. Attendance

### GET attendance

Purpose:
- Render attendance dashboard.

Notes:
- Non-admin users always receive own attendance records only.
- Admin users can retrieve global attendance records.

### POST clock_action

Purpose:
- Process clock_in or clock_out with coordinates and notes.

Request fields:
- action: clock_in | clock_out
- latitude: float
- longitude: float
- notes: string optional

Response:
- success: boolean
- message: string
- record: object (latest/updated attendance row)

## 2. HR Workspace

### GET hr_management

Purpose:
- Render unified HR workspace with server-authorized tabs and filtered datasets.

## 3. Payroll and Operations

### GET payroll

Purpose:
- Render payroll summary for selected date range.

### POST save_payroll_profile

Purpose:
- Save payroll profile for selected staff.

Key fields:
- staff_id, department_id, default_shift_id
- base_hourly_rate, overtime_multiplier, daily_field_allowance
- employee_nib_rate, employer_nib_rate, employee_nhip_rate
- vacation_pay, outstanding_loan, loan_repayment
- payment_method, bank_account_info

### POST save_department

Purpose:
- Create/maintain department.

### POST save_shift

Purpose:
- Create/update shift template.

### POST distribute_shift

Purpose:
- Assign a shift to selected staff and/or department over date range.

### POST save_manual_attendance

Purpose:
- Insert or update manual attendance data.

### POST save_leave_record

Purpose:
- Create/update leave record.

### POST update_leave_status

Purpose:
- Update leave approval status.

## 4. Reporting

### GET export_operations_report

Purpose:
- Export selected report type as CSV.

Supported report type values:
- attendance_record
- attendance_summary
- daily_attendance
- monthly_attendance
- department_wise

### POST generate_payrun

Purpose:
- Build payrun statement rows for selected date range and filters.

Response:
- success: boolean
- message: string
- statement: object with rows and aggregates

## 5. Project Assignment and Access Settings

### POST save_project_assignment

Purpose:
- Save assignment for one or many staff members.

Key fields:
- project_name
- staff_ids[]
- start_date, end_date
- status
- notes

### POST save_hr_payroll_staff_ids

Purpose:
- Save explicit HR/payroll workspace staff-ID allowlist.

Access:
- Admin only

Key fields:
- staff_ids: array of integer IDs from multi-select, or legacy comma-separated integer string

Response:
- success: boolean
- message: string
- staff_ids: normalized integer array

## 6. Error and Access Behavior

Common response patterns:
- success false with message on validation or processing failure
- access_denied for unauthorized route access
- request validation failure for non-POST mutation attempts
