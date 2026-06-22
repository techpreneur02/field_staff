# Field Staff Module

Author: Sherwin Armas (scaenterprise.com)

Field Staff is a white-labeled workforce management module for modern ERP environments.

It provides:
- Geolocation-backed attendance logging
- HR management workspace with tab-level access controls
- Payroll profile management and payrun generation
- Shift scheduling and distribution
- Leave management
- Project assignment controls with strict role-ID allowlist gating
- CSV exports for reporting workflows

## Documentation Map

Use the split documentation set for role-specific usage:
- docs/INDEX.md
- docs/SETUP_AND_OPERATIONS.md
- docs/PERMISSIONS_MATRIX.md
- docs/API_ENDPOINTS.md
- docs/WORKFLOWS.md

Envato readiness documents:
- docs/ENVATO_READINESS.md
- docs/ENVATO_PACKAGE_STRUCTURE.md
- docs/ENVATO_ITEM_PAGE_TEMPLATE.md
- docs/ENVATO_UAT_CHECKLIST.md
- docs/SUPPORT_POLICY.md

## 1. Module Scope

The module is built for PHP MVC deployments with a MySQL backend and a jQuery + Bootstrap admin UI.

Primary business rules implemented:
- Weekly regular hours cap: 44.00
- Overtime rate: 1.5x for hours above 44.00
- NIB split: 5.5% employee, 6.5% employer
- NHIP split: 3.0% employee, 3.0% employer
- NHIP monthly ceiling: 7800.00
- Payroll additions/deductions via EAV attributes

## 2. Architecture Overview

Folder layout:
- field_staff.php: module bootstrap, hooks, capabilities, helper access guards
- install.php: activation installer and idempotent schema bootstrap
- controllers/Field_staff.php: attendance endpoints, HR workspace actions, exports, payrun orchestration
- models/Field_staff_model.php: persistence and reporting logic
- views/attendance_dashboard.php: self-service attendance capture screen
- views/hr_management.php: multi-tab HR workspace
- views/payroll_report.php: payroll summary view
- assets/css/field_staff.css: module styles
- assets/js/field_staff.js: shared module JavaScript

## 3. Feature Coverage

### 3.1 Attendance

- Clock in and clock out with GPS capture
- Automatic open-clock detection
- Personal or global attendance history based on permissions
- In/Out coordinate tracking and map preview links
- Optional notes per attendance event

Endpoint:
- POST admin/field_staff/clock_action

### 3.2 HR Management Workspace

The HR workspace is split into dedicated tabs, each with permission-driven visibility:
- Employee Pay Setup
- Shift Scheduling and Distribute
- Manual Attendance Logger
- Employee Leave Tracking
- Reporting and Payrun
- Project Assignment

Access is computed server-side and only authorized tabs are rendered.

### 3.3 Employee Pay Setup

- Staff profile selection
- Department and default shift assignment
- Payroll profile editing:
  - Base hourly rate
  - Overtime multiplier
  - Daily field allowance
  - Employee and employer statutory percentages
  - Vacation pay, outstanding loan, loan repayment
  - Payment method and bank account info

Endpoint:
- POST admin/field_staff/save_payroll_profile

### 3.4 Operations

- Add and manage departments
- Add and manage shift templates
- Distribute shifts by selected staff and/or department
- Manual attendance entry/adjustment
- Leave creation and leave status updates

Endpoints:
- POST admin/field_staff/save_department
- POST admin/field_staff/save_shift
- POST admin/field_staff/distribute_shift
- POST admin/field_staff/save_manual_attendance
- POST admin/field_staff/save_leave_record
- POST admin/field_staff/update_leave_status

### 3.5 Reporting and Payrun

- Attendance record report
- Attendance summary report
- Daily attendance report
- Monthly attendance matrix
- Department-wise productivity report
- CSV export for all report types
- Payrun statement generation for selected filters

Endpoints:
- GET admin/field_staff/export_operations_report
- POST admin/field_staff/generate_payrun

### 3.6 Project Assignment

- Assign one project to one or many staff in one action
- Track supervisor/manager owner, date range, status, notes
- Filterable assignment history

Endpoint:
- POST admin/field_staff/save_project_assignment

### 3.7 Strict Role-ID Allowlist (Project Assignment)

Project Assignment access uses explicit role IDs, not role-title matching.

- Allowlist storage key: manager_supervisor_role_ids
- Persisted in fs_settings
- Managed via admin-only panel in HR workspace
- Current user Staff ID and Role ID are displayed in the panel for fast setup

Endpoint:
- POST admin/field_staff/save_manager_supervisor_roles

## 4. Permission Model

Module capabilities registered:
- view_own: view personal attendance only
- view: view global attendance scope
- edit: modify records
- create: create manual attendance data

Workspace access:
- HR Management appears when user is admin, has field_staff capability (view/edit/create), or is in role-ID allowlist for manager/supervisor project assignment access.

Tab-level controls (server-side):
- Employee Pay Setup: pay-setup access check
- Shift/Manual/Leave operations: operations access check
- Reporting and Payrun: reporting access check
- Project Assignment: strict role-ID allowlist or equivalent authorized access path

Security notes:
- Unauthorized global attendance requests are rejected.
- Access checks are enforced in controllers before data operations.
- JSON actions require POST validation for state changes.

## 5. Database Schema

Tables created by installer and/or runtime schema guard:
- fs_attendance
- fs_payroll_attributes
- fs_payroll_values
- fs_payroll_master
- fs_payroll_profiles
- fs_departments
- fs_shifts
- fs_shift_distributions
- fs_leaves
- fs_project_assignments
- fs_settings

EAV seed attributes:
- loan_adjustment (deduction)
- advance (deduction)
- commission (addition)
- vacation_pay (addition)

## 6. Setup Instructions

### 6.1 Install

1. Place the module folder under your modules path as field_staff.
2. Activate the module from your admin module manager.
3. Activation runs install.php and creates required tables/columns idempotently.
4. Confirm menu entries appear:
   - Field Attendance
   - Master Payroll HR
   - HR Management Workspace (if user can manage workspace)

### 6.2 Upgrade Existing Installations

On controller construction, ensure_operations_schema runs defensive checks to create missing operations/settings tables and columns for already-activated deployments.

This protects older environments that predate newer tabs/features.

### 6.3 Post-Install Configuration

1. Assign module capabilities to relevant roles.
2. Open HR Management and configure departments and shifts.
3. Configure payroll profiles for staff.
4. If using Project Assignment tab for supervisors/managers:
   - Open Manager/Supervisor Role ID Allowlist panel (admin only)
   - Add explicit role IDs (comma-separated), for example: 3,7
   - Save allowlist

## 7. Complete Workflows

### 7.1 Attendance Workflow (Staff)

1. Open Field Attendance.
2. Click Clock In.
3. Browser requests GPS and submits coordinates.
4. Complete work period.
5. Click Clock Out.
6. Attendance ledger row updates with in/out times, coordinates, and notes.

### 7.2 Payroll Setup Workflow (HR)

1. Open HR Management.
2. In Employee Pay Setup, select employee.
3. Assign department and default shift.
4. Enter payroll profile details.
5. Save payroll profile.
6. Validate profile values on reload.

### 7.3 Operations Workflow (HR)

1. Add departments as needed.
2. Create shift templates.
3. Distribute shifts to staff/departments for a date range.
4. Capture manual attendance adjustments when needed.
5. Record and update leave requests.

### 7.4 Reporting and Payrun Workflow

1. Set date range and optional staff/department filters.
2. Review attendance summary cards and report tables.
3. Export required CSV report(s).
4. Generate payrun statement.
5. Review output rows before downstream payroll settlement.

### 7.5 Project Assignment Workflow

1. Ensure current user role ID is allowlisted.
2. Open Project Assignment tab.
3. Select one or many staff.
4. Enter project metadata (name, dates, status, notes).
5. Save assignment.
6. Review assignment history table.

### 7.6 Role Allowlist Administration Workflow (Admin)

1. Open HR Management -> Project Assignment.
2. In allowlist panel, read current user Staff ID and Role ID indicator.
3. Enter comma-separated role IDs.
4. Save role allowlist.
5. Validate that authorized users can access Project Assignment tab and unauthorized users cannot.

## 8. Payroll Calculation Notes

Weekly payroll baseline formula:
- regular_hours = min(total_hours, 44)
- ot_hours = max(total_hours - 44, 0)
- gross_salary = regular_hours * hourly_rate + ot_hours * hourly_rate * 1.5
- nib_ee = gross_salary * 0.055
- nib_er = gross_salary * 0.065
- nhip_ee = nhip_base * 0.03
- nhip_er = nhip_base * 0.03
- net_salary = gross_salary - (nib_ee + nhip_ee)

Operational expectation:
- NHIP should respect monthly ceiling (7800.00) in monthly aggregation scenarios.

## 9. Validation and QA Checklist

Use this checklist after setup or upgrade:
- Attendance clock in/out works and records coordinates.
- Own-only attendance users cannot fetch global scope.
- HR tabs render only for authorized users.
- Project Assignment tab is hidden for non-allowlisted roles.
- Role allowlist save action persists and reloads values.
- Payrun generation returns rows for date ranges with completed attendance.
- CSV exports download for each report type.
- Department/shift/leave CRUD flows work end to end.

## 10. Troubleshooting

### 10.1 Missing Tables or Columns

- Re-run module activation, or open HR pages to trigger runtime schema guard.
- Check installer logs in module root install_debug.log.

### 10.2 Permission Denied in Workspace

- Verify role capabilities for field_staff.
- Verify role ID allowlist values for Project Assignment access.
- Confirm user session has the expected staff role assignment.

### 10.3 Payrun Returns Empty

- Confirm attendance rows exist with both clock_in and clock_out in selected period.
- Confirm selected department filter includes active staff.

### 10.4 Static Analyzer Shows Undefined Framework Symbols

In this module architecture, framework globals/types may appear unresolved in static diagnostics. Validate behavior in runtime context.

## 11. Operational Guidelines

- Keep role allowlist explicit and minimal.
- Review role IDs after any role migration.
- Avoid broad view permissions unless operationally required.
- Keep payroll profile data current before running payrun.
- Review generated statements before status transitions.

## 12. Versioning and Change Tracking

See CHANGELOG.md for release and enhancement history.
