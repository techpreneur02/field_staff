# Field Staff Module Build Plan

## Source root and checkpoint
- Source root identified: `d:\October projects\field_staff\field_staff`
- Recoverable Git checkpoint created: branch `checkpoint/pre-full-build`
- Timestamped backup created outside the module: `d:\October projects\field_staff\_module_backups\field_staff_backup_20261014_223943.zip`
- Module boundaries preserved: no Perfex core files modified; all implementation remains within the `field_staff` module folder.
- Current live-validation status: browser session is active; the module UI loads in the hosted demo, but final workflow acceptance remains pending on the live app.

## Operating principles
- Preserve existing data and compatibility with Perfex.
- Keep module-only boundaries intact; no edits outside the module unless required for safe redirects or compatibility glue.
- Prefer focused services/partials/views over a single giant controller/view.
- Protect payroll integrity with centralized calculations, cutoff locking, and finalization rules.
- Use server-side validation to prevent duplicate submissions, stale UI callbacks, and race conditions.
- Do not invent statutory rules or change rates without explicit configuration and verification.

## Inventory of existing features
The current module already includes the following feature areas:
- Attendance logging and geolocation capture
- Open-clock detection and attendance history
- HR management workspace with permissioned tabs
- Payroll profile management and payrun generation
- Shift scheduling and distribution
- Leave management and project assignment controls
- CSV/report export flows
- Optional compatibility hooks for module install and schema upgrades

## Outstanding defects and implementation targets
Key issues previously identified and kept in scope for the full rebuild:
- Payroll component persistence can accumulate duplicates or fail without rollback.
- Draft component values should be replaced/upserted rather than appended.
- employee ID validation and duplicate rejection must happen before dedupe logic.
- Preview, individual save, and bulk save should share one server-side calculation path.
- Explicit authorized adjustments must be preserved without clobbering user intent.
- Finalized payroll records must be protected from overwrite or bypass.
- Cutoff coverage edits should be allowed only before generation and rejected afterward.
- Migration accuracy, schema verification, and historical state handling need repair.
- Stale Apply callbacks and in-flight state must be prevented.
- UI/workspace layout needs simplification and separation by operational area.
- Attendance and break workflows need strict state validation and duplicate protection.
- Payroll finalization must not bypass TCI rules or statutory verification.

## Implementation order and scope

### 1. Complete payroll/data foundations
A. Payroll component seed and persistence guardrails
- Seed required payroll component attributes.
- Replace or upsert component values rather than duplicate rows.
- Roll back if component persistence fails.
- Validate employee IDs and reject duplicates before deduplication.
- Use one calculation service for preview, individual save, and bulk save.
- Preserve user-authorized adjustments.
- Keep cutoff locking and finalized-payroll protection.
- Fix in-flight Apply state and stale callbacks.
- Allow cutoff coverage edits before generation; reject them afterward.
- Repair migration error handling, schema verification, and inaccurate migration states.
- Never auto-delete conflicting historical payroll records.

B. Data-layer safety
- Centralize payroll calculation and validation logic.
- Keep cutoff-level isolation and historical preservation.
- Add robust rollback/JSON error handling for duplicate-key and transaction failures.

### 2. Replace navigation and workspace layout
- Split the UI into main areas: My Work, Team Operations, Payroll, Reports, HR Settings.
- Break the existing giant view/controller into focused views/partials/services.
- Add HR Settings sections:
  - Employees
  - Departments
  - Attendance & Break Policies
  - Pay Policies
  - Payroll Groups & Calendar
  - Holiday Calendar
  - Statutory Configuration
  - Access & Approvals
- Keep operational functions in operational areas and preserve old URLs with redirects where practical.
- Follow a light corporate design language: white surfaces, restrained blue accents, compact tables, responsive controls, accessible navigation.

### 3. Employee configuration
- Add explicit employee metadata for position, employment arrangement, payment basis, and attendance requirement.
- Add effective-dated rates, payroll-group assignment, policy templates, and overrides.
- Include inherited-value visibility and audit history.
- Prevent automatic statutory exemptions from position or payment basis alone.

### 4. Attendance and breaks
- Implement the validated state lifecycle: Clock In -> Working -> Break Out -> On Break -> Break In -> Working -> Clock Out.
- Support multiple paid/unpaid breaks, break monitoring, overrun warnings, missing-return exceptions, and approved corrections.
- Keep raw punches separate from payable-time calculation.
- Enforce server-side state validation and duplicate-request protection.
- Support overnight shifts, grace policies, and schedule overrides.
- Avoid automatic full-shift deductions for a missing Break In.
- Require lawful, verified rounding rules before pay-affecting rounding is enabled.

### 5. Payroll
- Support configured payment bases with explicit calculation details.
- Handle per-job/unit pay using approved work records and agreed rates.
- Distinguish workweek calculation from payroll cutoff periods.
- Keep selected-cutoff reports isolated from legacy and other-cutoff data.
- Snapshot calculation inputs, policy values, components, and results.
- Add prepare/review/approve/payment-record workflow controls.
- Prevent status changes that bypass workflow permissions.
- Flag missing rates, unresolved attendance, and incomplete statutory configuration.

### 6. TCI rules
- Maintain effective-dated rules with source and verification status.
- Do not assume constants are complete or current.
- Do not invent rates, ceilings, exemptions, or rounding rules.
- Flag unresolved statutory setup and block finalization when it materially affects payroll.
- Preserve historical payroll when rules change.

### 7. Reports and printing
- Provide cutoff-based payroll summaries, employee statements, attendance, breaks, exceptions, and statutory contribution schedules.
- Use saved payroll for historical reports.
- Add print-friendly layouts and PDF output through supported Perfex facilities.
- Keep legacy history separate.
- Ensure no payroll rows appear until a cutoff is selected.
- Keep attendance rows aligned to the displayed date filters at load, refresh, and export.

## Delivery and verification
- Implement in order with recoverable checkpoints.
- After each stage, update `BUILD_PROGRESS.md` with actual changes and checks.
- Run meaningful validation for calculations, persistence, permissions, migrations, duplicate requests, cutoff isolation, and attendance/break transitions.
- Use a test harness where feasible; do not claim live Perfex/database testing when the runtime is unavailable.
- Do not deploy to production.

## Final deliverable
- `field_staff_full_build_review.zip` with one top-level `field_staff` folder and no Git metadata, debug logs, temp files, or nested archives.
- Extract the final ZIP and verify contents match the current source.
