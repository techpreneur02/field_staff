# API Endpoints and Controller Actions

Base path prefix in examples:
- admin/field_staff/

All mutating actions should be called with POST and CSRF tokens in authenticated admin context.

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
