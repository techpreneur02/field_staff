# Permissions Matrix

This module uses capability checks for attendance actions and strict explicit staff-ID allowlist logic for HR and payroll workspace access.

## 1. Capability Definitions

Registered module capabilities:
- view_own: view own attendance context
- view: global attendance scope
- edit: modify attendance records
- create: create manual attendance data

## 2. Workspace Entry Rules

HR Management Workspace and Master Payroll HR are available when either is true:
- Whitelist is empty and user is admin (bootstrap recovery mode)
- User staff ID is explicitly allowlisted in fs_settings under hr_payroll_staff_ids

## 3. Tab-Level Access Rules

- Shift and Department Setup: HR/payroll workspace access path
- Employee Pay Setup: HR/payroll workspace access path
- Manual Attendance Logger: HR/payroll workspace access path
- Employee Leave Tracking: HR/payroll workspace access path
- Reporting and Payrun: HR/payroll workspace access path
- Project Assignment: HR/payroll workspace access path

## 4. Strict Staff-ID Allowlist Model

Behavior:
- Access is decided by integer staff IDs only.
- Role-title and role-ID matching are not used for HR/payroll workspace gates.
- Allowlist is stored in fs_settings under hr_payroll_staff_ids.
- Admin-only endpoint updates allowlist.

Admin panel support:
- Current user Staff ID indicator displayed in UI.
- Searchable name picker writes normalized staff IDs.

## 5. Security Notes

- Non-admin attendance ledger visibility is own-records only.
- Admin attendance ledger visibility supports global records.
- Controller-level enforcement is applied before state-changing operations.
- POST validation is required for mutating actions.

## 6. Recommended Governance

- Keep allowlist minimal and intentional.
- Always keep at least two trusted staff IDs allowlisted.
- Review allowlist after staffing changes.
- Separate reporting and operations rights where possible.
- Use admin-only process for allowlist updates with change approval.
