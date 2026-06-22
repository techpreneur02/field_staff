# Changelog

All notable changes to this module are documented in this file.

The format follows Keep a Changelog principles and semantic versioning intent.

## [Unreleased]

### Added
- Project Assignment tab and persistence flow for assigning projects to one or multiple staff.
- fs_project_assignments schema support in runtime schema guard.
- fs_settings table support for module-level settings persistence.
- Admin endpoint to save manager/supervisor role allowlist by explicit role IDs.
- Admin UI panel to manage manager/supervisor role ID allowlist.
- Current user role context in allowlist panel (Staff ID, Role ID, Role name when available).
- Envato submission documentation kit:
  - Envato readiness guide
  - Package structure blueprint
  - Item page content template
  - UAT checklist
  - Support policy

### Changed
- HR workspace tab structure split into dedicated sections:
  - Employee Pay Setup
  - Shift Scheduling and Distribute
  - Manual Attendance Logger
  - Employee Leave Tracking
  - Reporting and Payrun
  - Project Assignment
- Project Assignment access now uses strict explicit role-ID allowlist checks instead of role-title matching.
- Access checks centralized and reinforced in controller helpers.
- Bootstrap helper access logic aligned with strict role-ID allowlist model.

### Fixed
- Removed duplicated template/script content in HR management view.
- Improved consistency between menu visibility and runtime access enforcement.

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
