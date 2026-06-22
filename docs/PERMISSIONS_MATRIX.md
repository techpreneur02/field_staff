# Permissions Matrix

This module uses capability checks plus strict explicit role-ID allowlist logic for Project Assignment.

## 1. Capability Definitions

Registered module capabilities:
- view_own: view own attendance context
- view: global attendance scope
- edit: modify attendance records
- create: create manual attendance data

## 2. Workspace Entry Rules

HR Management Workspace is available when any is true:
- User is admin
- User has field_staff capability: view, edit, or create
- User is recognized by manager/supervisor explicit role-ID allowlist

## 3. Tab-Level Access Rules

- Employee Pay Setup: pay-setup access path
- Shift Scheduling and Distribute: operations access path
- Manual Attendance Logger: operations access path
- Employee Leave Tracking: operations access path
- Reporting and Payrun: reporting access path
- Project Assignment: strict role-ID allowlist path (and equivalent authorized checks)

## 4. Strict Role-ID Allowlist Model

Behavior:
- Access is decided by integer role IDs only.
- Role-title matching is not used.
- Allowlist is stored in fs_settings under manager_supervisor_role_ids.
- Admin-only endpoint updates allowlist.

Admin panel support:
- Current user Staff ID and Role ID indicator displayed in UI.
- Comma-separated role IDs accepted.

## 5. Security Notes

- Unauthorized global attendance requests are denied.
- Controller-level enforcement is applied before state-changing operations.
- POST validation is required for mutating actions.

## 6. Recommended Governance

- Keep allowlist minimal and intentional.
- Review allowlist after role migrations.
- Separate reporting and operations rights where possible.
- Use admin-only process for allowlist updates with change approval.
