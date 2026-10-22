# Changelog

All notable changes to this module are documented in this file.

The format follows Keep a Changelog principles and semantic versioning intent.

## [Unreleased]

### Added
- Supervisor correction flow and payroll-blocking guard for unresolved attendance or pending corrections in the selected period.
- Unresolved break metadata in the attendance summary payloads (`has_unresolved_breaks`, `invalid_break_count`, and `unresolved_break_ids`).
- Local runtime coverage for malformed timestamps, unresolved break rows, and correction-to-payroll gating.
- GitHub checkpoint history for the current working branch while cPanel deployment remains suspended.

### Changed
- Reconciled the current source tree to the authoritative local working branch and preserved release history without inventing a new released version.
- Updated documentation to distinguish implemented behavior, locally tested behavior, and pending integration-verified behavior.
- Replaced stale browser-session claims with date-stamped evidence language; session state is transient and must not be treated as permanent validation.

### Fixed
- Corrected the correction-to-payroll approval flow so payroll cannot be approved while unresolved attendance issues remain in the selected period.
- Preserved the historical 1.2.0 and 1.0.0 release entries without creating a duplicate 1.2.0 section.
- Kept the statutory constants labeled as historical implementation defaults rather than verified current legal requirements.

### Verification status
- Verified locally: PHP lint passes across the module PHP files; runtime checks pass for attendance/break guards, cutoff/snapshot logic, correction gating, and effective-dated policy overrides.
- Pending integration verification: live Perfex demo acceptance is still outstanding and requires authenticated host access plus server-log diagnosis for any HTTP 500.
- Packaging status: a ZIP from the authoritative working tree is a source snapshot only; it is not proof of complete deployment or final acceptance.
- Current deployment gate: hosted deployment remains suspended while the current local source is the authoritative baseline.

## [1.2.0] - 2026-10-14

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

## [1.2.0] - 2026-10-14 (duplicate consolidated)

### Added
- Dedicated HR Settings workspace for managing staff access, payroll cutoffs, and configuration outside operational attendance/payroll workflows.
- Persisted payroll cutoff records with date windows, group metadata, and status tracking for cutoff-based payroll processing.
- Cutoff-aware payroll reporting and selected-period review so the payrun workspace operates against a valid payroll window rather than ad hoc date filters alone.
- Legacy payroll compatibility handling for older saved payroll rows while newer cutoff-driven processing is introduced.
- Payroll-group assignment persistence and staff-directory normalization for employee payroll membership validation.
- Migration and schema guard work for cutoff-aware tables, payroll-group fields, and upgrade-safe database changes.

### Changed
- Payrun generation and apply flow now require a valid cutoff selection, derive payroll dates from the cutoff, and validate employee payroll-group membership before saving results.
- Payroll status handling was tightened to keep draft/approved/paid behavior aligned across save, query, UI, and report logic.
- Controller and view logic now preserve cutoff identity, selected cutoff metadata, and preview invalidation behavior when the cutoff changes.
- HR workspace access and configuration flow were refocused around HR Settings while routine operations remain in the operational workspace.
- Version metadata and module packaging were reviewed and aligned with the current source package state.

### Fixed
- Prevented invalid payrun submissions without a valid cutoff and blocked editing of approved/closed cutoff periods.
- Corrected mixed status assumptions between legacy records and newer cutoff-based payroll data.
- Tightened duplicate protection logic and payroll write transactions to reduce duplicate employee/cutoff submissions and unsafe concurrent updates.
- Preserved finalized payroll state and prevented accidental mutations to saved payroll rows after cutoff approval or closure.
- Improved migration safeguards so schema changes require successful completion before the module records upgrade success.
- Reworked legacy history presentation to keep historical payroll records separate from recalculated cutoff-driven views.

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
