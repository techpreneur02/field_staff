# Envato UAT Checklist

Run this checklist before every Envato submission or update.

## 1. Installation and Upgrade

- Fresh install completes without fatal errors
- Existing install upgrade path keeps data intact
- Runtime schema guard creates missing tables/columns

## 2. Attendance

- Clock in captures GPS and saves row
- Clock out closes open row and saves GPS
- Ledger updates show in/out coordinates and notes
- Users without global rights cannot access unauthorized scope

## 3. HR Workspace and Permissions

- Employee Pay Setup tab appears only for authorized users
- Operations tabs appear only for authorized users
- Reporting and Payrun tab appears only for authorized users
- Project Assignment tab appears only for allowlisted role IDs

## 4. Project Assignment and Role Allowlist

- Admin can save role allowlist values
- Role allowlist persists across reloads
- Current user role indicator is visible in allowlist panel
- Non-allowlisted users cannot access project assignment routes

## 5. Reporting and Exports

- Attendance record export downloads valid CSV
- Attendance summary export downloads valid CSV
- Daily attendance export downloads valid CSV
- Monthly attendance export downloads valid CSV
- Department-wise export downloads valid CSV

## 6. Payrun

- Generate payrun returns rows for valid dataset
- Empty dataset returns safe message
- Statutory values are present in generated totals

## 7. Security

- Mutating routes reject invalid request methods
- Protected endpoints enforce authorization checks
- Input fields are validated and normalized
- View output remains escaped where expected

## 8. Packaging

- Final package matches docs/ENVATO_PACKAGE_STRUCTURE.md
- Documentation bundle includes all required docs
- CHANGELOG.md reflects current release notes
