# Changelog

All notable changes to this module are documented in this file.

The format follows Keep a Changelog principles and semantic versioning intent.

## [1.1.1] - 2026-06-22

### Added
- Payslip download support for employee self-service portal (My Payslips tab).
- Payslip download support for HR admin in Reporting and Payrun workspace.
- Admin-issued payslip listing panel in HR Reporting and Payrun workspace.
- Download payslip endpoint with access-scoped authorization:
  - Staff can download only their own payslips.
  - Reporting-authorized HR users can download all eligible payslips.
- Master PIN lock in Payroll Admin tab for allowlist edit/save workflow.

### Changed
- Payroll Admin allowlist save flow now requires master PIN verification (hardcoded code `0212`).
- Shift setup form layout reorganized to remove control drift and improve responsive alignment.
- Documentation set updated to reflect selective payrun issuance and holiday double-pay operations.

### Fixed
- Resolved broken HR management JavaScript tail that prevented payrun and holiday actions from executing.
- Resolved holiday settings persistence issues caused by incorrect settings helper usage.
- Resolved holiday save/delete guard mismatch in controller permission checks.
- Stabilized payrun generation flow after holiday logic rollout and restored button action behavior.

## [1.1.0] - 2026-06-22

### Added
- Project Assignment tab and persistence flow for assigning projects to one or multiple staff.
- fs_project_assignments schema support in runtime schema guard.
- fs_settings table support for module-level settings persistence.
- Admin endpoint to save HR/payroll staff allowlist by explicit staff IDs.
- Name-based HR/payroll allowlist picker in HR setup flow with searchable multi-select.
- Bootstrap-safe whitelist behavior: admin recovery access when allowlist is empty.
- Employee attendance map panel beside the attendance capture form.
- Show/Hide map toggle in employee attendance workflow.
- Payslip management documentation for statement lifecycle and operations.
- Envato submission documentation kit:
  - Envato readiness guide
  - Package structure blueprint
  - Item page content template
  - UAT checklist
  - Support policy

### Changed
- HR workspace tab structure split and sequenced into dedicated sections:
  - Shift and Department Setup
  - Employee Pay Setup
  - Manual Attendance Logger
  - Employee Leave Tracking
  - Reporting and Payrun
  - Project Assignment
- HR and payroll workspace access now uses strict explicit staff-ID allowlist checks.
- Access checks centralized and reinforced in controller helpers and menu helpers.
- Allowlist save endpoint now supports both multi-select array payloads and legacy comma-separated text.
- Employee attendance portal tabs updated for native-style mobile behavior (touch-friendly pills, swipeable tab strip, sticky tabs).
- Attendance auto-refresh behavior now surfaces clock in/out updates immediately and preserves active tab state.

### Fixed
- Removed duplicated template/script content in HR management view.
- Improved consistency between menu visibility and runtime access enforcement.
- Enforced own-only attendance ledger visibility for non-admin staff accounts.
- Removed stale role-allowlist UI references from HR management flow.
- Restored attendance map visibility and aligned mobile tab usability in employee portal.

## [1.0.0] - 2026-06-22

### Added
- Initial module bootstrap and activation installer.
- Attendance ledger with GPS clock-in/clock-out capture.
- Attendance dashboard with map preview actions.
- Payroll profile storage with statutory fields and payment details.
- Department, shift, shift distribution, and leave management flows.
- Attendance analytics and multi-report exports to CSV.
- Payroll summary and weekly payroll calculations.
- EAV payroll attribute/value support with default seeded attributes.

### Business Rules
- Weekly regular hours cap set to 44.0 hours.
- Overtime multiplier set to 1.5.
- NIB split configured as 5.5% employee and 6.5% employer.
- NHIP split configured as 3.0% employee and 3.0% employer.
- NHIP monthly ceiling baseline set to 7800.00.
