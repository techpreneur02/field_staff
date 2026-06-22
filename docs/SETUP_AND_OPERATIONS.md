# Setup and Operations

## 1. Prerequisites

- PHP MVC environment compatible with this module architecture
- MySQL-compatible database
- Existing staff/auth/session infrastructure in host application
- jQuery and Bootstrap available in admin views

## 2. Installation

1. Place module folder as field_staff inside your modules path.
2. Activate module from the admin module manager.
3. Activation runs installer script and creates required tables/columns idempotently.
4. Confirm menu visibility based on user access:
   - Field Attendance
   - Master Payroll HR
   - HR Management Workspace (authorized users)

## 3. Upgrade-Safe Schema Handling

- Installer: creates baseline schema during activation.
- Runtime guard: controller runs ensure_operations_schema to create missing newer tables/columns for previously activated installs.

Operational benefit:
- Environments can receive new module features without manual SQL in most cases.

## 4. Post-Install Configuration

1. Assign module capabilities to roles.
2. Configure HR/payroll staff allowlist in Shift and Department Setup.
3. Configure departments and shift templates.
4. Configure payroll profiles per staff.

## 5. Data Objects Created

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

## 6. Baseline Payroll Rules

- Weekly regular hours cap: 44.00
- Overtime multiplier: 1.5
- NIB: 5.5% employee, 6.5% employer
- NHIP: 3.0% employee, 3.0% employer
- NHIP monthly ceiling: 7800.00

## 7. Operating Checklist

Daily:
- Verify attendance entries are being recorded with GPS.
- Review open clocks and ensure end-of-shift clock-outs.

Weekly:
- Review attendance summaries and late/early metrics.
- Verify leave and manual attendance corrections.
- Run payrun statement after attendance lock.

Monthly:
- Revalidate HR/payroll staff allowlist against active staffing.
- Verify statutory rates and payroll profile drift.

## 8. QA and Validation

- Clock in/out success with valid coordinates.
- Unauthorized users blocked from restricted HR tabs.
- Allowlisted staff IDs gain HR/payroll workspace access.
- CSV report exports generate expected columns.
- Payrun returns rows for completed attendance periods.

## 9. Troubleshooting

### Missing Access

- Verify allowlist values in fs_settings for hr_payroll_staff_ids.
- Confirm user session resolves to expected staff ID.
- Confirm at least one trusted admin staff ID remains allowlisted.

### Empty Payrun

- Confirm records include both clock_in and clock_out.
- Confirm date range and department filters.

### Schema Gaps

- Re-activate module or load HR controller path to trigger runtime schema guard.
- Review module install_debug.log for installer diagnostics.
