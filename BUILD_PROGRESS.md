# Field Staff Module Build Progress

## Latest milestone - break lifecycle, 2026-10-22 UTC

**Implemented and locally tested:** complete authoritative clock/break lifecycle; stable employee-row transaction locking; immutable paid/unpaid break types; multiple breaks; explicit invalid/missing timestamp blockers; raw punch preservation with calculation-time rounding; midnight/overnight date handling; correction-aware operational state; duplicate-safe UI with server action allowlists, processing state, persistent timer and safe error preservation; private attendance history and authorized live Team Operations; shared payable/workweek ledger for payroll and attendance reporting.

Additive migration `2026_10_22_attendance_raw_punches` defaults existing records to legacy behavior. Manual date/time controls explicitly support overnight dates and reject writes over raw/break-bearing/approved history. No rates or statutory verification statuses were changed.

### Validation
- PASS `php tests\attendance_lifecycle_checks.php`: lifecycle, two immutable paid/unpaid breaks, elapsed state persistence, duplicate/invalid actions, staff-row locking, actual controller actor scoping, authorization/coordinate/type rejection, rollback, overnight/missing/malformed/overlapping breaks, correction-closed raw shifts, explicit manual overnight input and raw/paid-history preservation.
- PASS `php tests\payroll_regression_checks.php`, including all previous numerical regressions and new mixed-break raw-punch endpoint/snapshot/attendance report parity: 47.5 payable = 44 regular + 3.5 OT; fixture gross 985.
- PASS `php tests\field_staff_runtime_checks.php` (including additive migration checks).
- PASS lint for all 13 non-backup PHP files and rendered portal JavaScript syntax check. Package validation is repeated before delivery.
- PASS clean 41-file ZIP candidate: one `field_staff/` root, zero extra/missing/hash-mismatched files, all three suites pass from extraction. Final packaging is repeated after committing the source checkpoint.
- Local recovery fix is in progress and not yet committed: the Working-state guard and stale-processing cleanup were added in the live UI and lifecycle regression, but there is no final deployed record yet.

### Deployed and live verified
- Verified new outside-public-root backup at `/home/erpxolluxn/field_staff_backup_pre_breaks_20261022/`; all 10 then-affected existing files matched direct content read-back.
- Initial eight-file break deployment hash-verified; raw-punch migration run via module reactivation. Browser-native multipart upload worked where fetch uploads hit verification.
- Dedicated non-admin staff 2 completed Working -> On break -> Working twice -> Clocked out. The timer survived reload. Unpaid 38 seconds and paid 32 seconds were saved with immutable types; history showed 0.489444 payable hours under the existing policy.
- Duplicate UI clicks sent one request. Parallel Clock In and Break Out each returned exactly one success and one rejection. The extra short test shift (attendance 28) was clocked out.
- Invalid Clock Out on break rejected; UI displayed the safe error and preserved confirmed status; all actions disabled while processing.
- Team and payroll endpoints returned HTTP 403 to the test employee.

### Current blocker and remaining acceptance
The authoritative local branch is current and the focused lifecycle test passes, but the hosted deployment/upload and exact server-side hash verification were not completed as of the final repo check. The cPanel session was authenticated and the target module path was visible, but the last step remained a direct hosted file backup/upload and exact hash comparison, not a local package rebuild or a page-load claim.

Pending deployment: the current validated local fix in `views/attendance_dashboard.php` and the matching regression update in `tests/attendance_lifecycle_checks.php`, plus any final doc sync after the hosted files are confirmed. The current state is local source validated; live status remains pending exact host verification.

| Acceptance | Local | Live |
|---|---|---|
| Complete two-break lifecycle, timer/reload, safe errors, duplicates | PASS | PASS (initial deployed build) |
| Paid/unpaid deduction and payroll/snapshot/report parity | PASS | History verified; final admin/payroll comparison blocked |
| Overnight shift and midnight rounding | PASS | Not run |
| Missing/malformed returns and correction-aware closing | PASS | On-break Clock Out rejection verified; abandoned/closed fixture not run |
| My Work / Team Operations / history / payroll agreement | PASS calculation layer | Final team/admin comparison blocked |
| Salary/daily/job/unit required inputs | PASS | Not run |
| Non-admin restrictions | PASS | PASS HTTP 403 |
| Print/PDF | UI/export links tested | Not verified |
| Approval/payment | Local workflow PASS | Statutory verification still blocks |

Local source, GitHub checkpoint and the single ZIP are synchronized after final validation; demo parity remains explicitly pending.

## Earlier payroll milestone - 2026-10-22 UTC (superseded where noted above)

Authoritative source: `D:\October projects\field_staff\field_staff`, branch `checkpoint/live-nav-ui-review`, existing remote `https://github.com/techpreneur02/field_staff.git`. Resumed from clean checkpoint `012587b`; no existing work was reset.

### Implemented
- One versioned calculation result now feeds preview, individual save, selected-employee bulk payrun, earnings/deductions and saved snapshots. Both profile defaults and explicit employee adjustments use the same rounding and configured statutory rates.
- Weekly overtime accumulates by configured seven-day workweek, with earlier attendance in the first workweek loaded even when it belongs to the preceding cutoff. Only attendance work dates in the selected cutoff are paid. Multiple workweeks reset independently. Monday remains the compatibility default; HR Settings exposes the start day and weekly cap.
- Payroll consumes validated payable duration, deducts unpaid breaks only, rejects unresolved/malformed/reversed/out-of-shift/overlapping breaks and open attendance, and overlays approved clock/break corrections without rewriting raw punches.
- Pending corrections block the affected attendance/work-date cutoff, not their submission-date cutoff. Conflicting or invalid approved corrections fail closed.
- Salary/daily/job/unit pay requires explicit, approved, source-referenced cutoff work inputs; no hourly-rate, eight-hour-day, inferred quantity or maximum-of-two-pay fallback. Daily records cover every completed work date; salary requires exactly one complete-cutoff amount. Authorized HR work-input approval records actor/time/reference. Inputs are append-only and cannot replace approved/paid payroll.
- Direct draft approval is disabled. Review is mandatory, statutory verification remains mandatory, stale v2 inputs block approval until recalculation, and legacy drafts must be recalculated before approval. Already approved/paid history is not recalculated or reopened.
- Snapshot, component and audit-write failures reject/roll back controller transactions. Approved snapshot finances cannot be overwritten. Payroll register exposes valid review/payment actions and saved CSV/print/PDF links; headings no longer claim fixed statutory percentages.
- Holiday premium requires an explicit configured multiplier; no new legal rate was supplied. Existing statutory defaults are historical configuration, not verified legal advice.

### Locally tested
Commands: `php tests\payroll_regression_checks.php`, `php tests\field_staff_runtime_checks.php`, and PHP lint for every non-backup module PHP file.

The numerical/controller suite passes: five ten-hour shifts = **44 regular + 6 overtime**, gross **1060**, net **932.80** at deliberately configured fixture-only rates; cutoff split **40/0 + 4/6**; two workweeks **88/12**; paid versus unpaid breaks **50 versus 45 payable hours**; overtime-disabled base pay retains all hours; configured Wednesday workweek; all four non-hourly bases and missing/duplicate/unapproved inputs; late clock/break corrections; raw-punch immutability; preview/individual/bulk/snapshot parity with profile and explicit adjustments; permission rejection; component/snapshot/audit rollback; protected paid history; invalid/stale approval transitions; rendered workflow/export controls.

These are local PHP/query-builder fixtures, not a real hosted MySQL/Perfex integration run.

### Live-testing hardening (implemented, locally tested, live verified)
- `save_payroll` and `apply_payrun` now reject recalculating an `under_review` payroll. Previously it was silently reset to draft without a workflow audit event.
- `submit_for_review` now rejects when the staff member has unresolved attendance or pending corrections within the cutoff work dates.
- New regressions: an under-review endpoint scenario for both routes (rejected, rolled back, status and net preserved), and a pending-correction submission block. The suite fails without the fix (mutation check).

### Current phase and hosted evidence
Phase: deployed to the demo and live-verified for hourly payroll. Full acceptance is pending statutory verification and the remaining live checks.

- **Backup:** `/home/erpxolluxn/field_staff_backup_pre_payroll_v2_20261022/` is outside the public root and contains every non-`.git` file. It was verified 43/43 with identical sizes.
  - The older `..._0328` folder is partial and is not a rollback set.
  - The earlier "Permission denied" came from unreadable `.git/objects` in the deployed folder.
- **Deployed:** checkpoint `7bcd612` (26 files), then the hardening (controller, model and payroll test).
- **SHA-256:** all files match. The controller matches after allowing for a cPanel `get_file_content` read-back quirk that strips one duplicate 22-byte meta tag; the on-disk size equals local.
- **Migrations:** run via module deactivate/reactivate.
- **Team monitor:** HTTP 500 before deployment, HTTP 200 after. The stale untracked build was the cause.
- **Live results** on dedicated staff 2 and cutoffs 1?3, group `fs_live_test`:
  - 44/6 (gross 1060, net 969.90); cross-cutoff 30/0 + 14/6
  - preview, individual save, bulk payrun and snapshot CSV identical
  - invalid transitions rejected
  - late correction blocked by work date; approved correction gave 44/5 (gross 1030, net 942.45)
  - approval stopped at the statutory gate
  - under-review re-save and bulk save rejected
  - pending correction blocked submission
- No settings were changed, no prior payroll history existed, and no other sites or core files were touched.

### Remaining acceptance
1. Authoritative statutory verification (approval and payment are blocked until then; do not mark verified for demo purposes).
2. Live checks not yet run:
   - unpaid-break deduction
   - salary/daily/job/unit inputs and missing-input rejection
   - non-admin role rejection
   - print/PDF saved-snapshot parity
3. Optional, with approval: clean junk (`.git`, `backups/`, nested ZIPs, logs) from the deployed module folder.

### Checkpoint and package evidence
- Checkpoints are pushed to `origin/checkpoint/live-nav-ui-review` and verified with `git ls-remote`; use `git log -1` for the current revision.
- PHP lint passes for all 12 non-backup PHP files; both suites pass.
- `..\field_staff.zip` is rebuilt after the final commit, extracted, SHA-256 compared with source, and both suites are run from the extraction.

## Historical progress below (superseded by the milestone above)

The following sections record earlier implementation claims and pending checks. They are not current live acceptance evidence.

## Previous phase
Phase: local feature completion and GitHub checkpointing; hosted deployment remains suspended until authenticated host-side evidence is available.

Recovered status (2026-10-18): the module source and all working changes were preserved under the local repository. The consolidated HR settings workspace, attendance validation logic, payroll snapshot safeguards, cutoff isolation checks, and correction-to-payroll gate are all in place and pass local lint/runtime validation. The source remains the authoritative implementation for the completed work, while the hosted demo remains the final gate for live acceptance. The current blocker is the hosted validation gate: the live browser session is not a reliable source of evidence because session lifetime is transient, and any cPanel or demo access must be treated as a dated, expiring verification event rather than a permanent status signal.

Local review package status: the authoritative local working tree remains the source of truth; a clean review ZIP can still be generated from it, but it is packaging only and not proof that the build is complete. The archive must be treated as a deliverable snapshot, not as live acceptance evidence.

Status: the reconciled source includes the consolidated HR settings workspace, employee configuration forms, and the correction-to-payroll safety guard. The permission gate and settings validation remain tightened so HR settings and payroll configuration save paths reject invalid inputs and rely on module-level access checks before accepting POSTs. The local module is ready for the next checkpoint, but the hosted app still needs the final live verification for the live team-monitor route, dedicated payroll approval flows, saved snapshot totals, export parity, and unauthorized access rejection before the module is considered fully complete. The live team-monitor route remains the exact dependency for final acceptance, and it must be verified in the running demo before sign-off is claimed, with any host-side exception diagnosed from server logs rather than browser-side noise.

### Current verification status
- Verified locally: PHP lint passes across the module PHP files; runtime checks for cutoff/snapshot logic pass in the project harness.
- Pending live verification: no authenticated live host-side exception evidence is available in the current environment, so the demo’s team-monitor route remains a timed, expiring validation target rather than a confirmed success.
- Pending live acceptance: dedicated employee attendance → break → clock-out → correction workflow; payroll review/approval; print/PDF/CSV parity with saved totals; unauthorized access rejection.
- Unresolved: authoritative TCI legal/statutory verification remains external and must stay explicitly flagged as pending.

## Requirement review against implementation

| Requirement | Status | Evidence |
|---|---|---|
| Single consolidated white/blue UI for My Work, Team Operations, Payroll, Reports, HR Settings | Implemented in source | `views/ui_preview.php` contains the full preview layout; `views/hr_management.php` uses a single workspace with five section links; `views/hr_settings.php` is the centralized settings surface. |
| Remove redundant interfaces and visibly disable unfinished actions | Implemented in source | The settings view explicitly identifies live controls and keeps non-live items gated/placeholder-labeled; redundant operational config remains excluded from the dedicated settings workspace. |
| HR Settings configuration under Settings; no duplicate operational config | Implemented in source | `field_staff.php` registers the module entry and `controllers/Field_staff.php` exposes `hr_settings()` as the actual centralized settings page. |
| Employee settings, departments, attendance/break/policies, payroll groups, holidays, statutory config, access/approvals | Implemented in source | `controllers/Field_staff.php` posts standard settings sections and `views/hr_settings.php` includes employee profile, department, attendance policy, pay policy, statutory policy, cutoff, and access forms. |
| Payroll cutoff logic, saved snapshots, register totals, print/PDF/CSV totals | Implemented in source | The model/controller stack includes cutoff selection, saved snapshot retrieval, and report/export wiring in the payroll/reporting path. |
| No cutoff selected means no payroll results | Implemented in source | The report/controller logic guards empty or invalid cutoff state before rendering results. |
| Cutoff A never includes cutoff B | Implemented in source | Cutoff-scoped filtering and overlap checks are enforced in the model/controller logic. |
| Historical financial values come from saved snapshots | Implemented in source | Snapshot-based totals are used in report/export logic; historical values are not treated as live recomputation-only results. |
| Unauthorized requests are rejected server-side | Implemented in source | Controller endpoints call access checks (`can_manage_hr_settings`, `respond_unauthorized_json`, etc.) before save actions. |
| Employee policies and effective-dated overrides in calculations | Implemented in source | `Field_staff_model` includes effective-dated policy scope, override resolution, and `get_payroll_profile()` merge logic. |
| Attendance, breaks, corrections, raw punch immutability, approvals | Implemented in source | The model includes attendance transitions, break validation, correction requests, and protected workflow paths; raw punch preservation is represented in the schema and logic. |
| Server-side authorization and concurrent action protection | Implemented in source | Workflow actions and save endpoints guard duplicate or unauthorized actions and enforce state transitions. |
| Final live Perfex data validation | Pending runtime validation | No authenticated live admin/session is currently available in this environment to verify real payroll permissions, exports, and data flows end-to-end. |
| Statutory/legal TCI configuration source | Pending external verification | The module stores configuration values but the authoritative legal source still must be reviewed in the live compliance workflow. |

## Implemented and verified status

### Verified in source and code paths
- UI preview route is live and renders correctly in the demo host at `https://demo.erpxolluxn.com/admin/field_staff/ui_preview#team-operations`.
- The preview shows the five main sections: My Work, Team Operations, Payroll, Reports, and HR Settings.
- The sidebar navigation trap was repaired by narrowing the click interception to real in-page tab targets only.
- `field_staff.php` preserves the module bootstrap, menu wiring, sidebar registration, and asset hooks.
- `views/hr_settings.php` includes settings form sections for access control, payroll calendar, cutoff setup, payment policy, and service configuration.
- `views/hr_management.php` enforces a single workspace nav and avoids trapping navigation outside the section.
- `views/payroll_report.php` guards summary errors and avoids DataTables initialization on empty tables.
- `Field_staff_model` includes attendance transition validation, break validation, open-attendance lookup, and policy snapshot resolution.
- PHP lint passes across the module PHP files using `php -l`.
- The final ZIP was rebuilt and extracted for verification; the extracted package contents match the intended source after excluding non-source artifacts.

### Verified evidence files
- [field_staff.php](<D:/October projects/field_staff/field_staff/field_staff.php>)
- [views/ui_preview.php](<D:/October projects/field_staff/field_staff/views/ui_preview.php>)
- [views/hr_management.php](<D:/October projects/field_staff/field_staff/views/hr_management.php>)
- [views/hr_settings.php](<D:/October projects/field_staff/field_staff/views/hr_settings.php>)
- [views/payroll_report.php](<D:/October projects/field_staff/field_staff/views/payroll_report.php>)
- [controllers/Field_staff.php](<D:/October projects/field_staff/field_staff/controllers/Field_staff.php>)
- [models/Field_staff_model.php](<D:/October projects/field_staff/field_staff/models/Field_staff_model.php>)
- [BUILD_PROGRESS.md](<D:/October projects/field_staff/field_staff/BUILD_PROGRESS.md>)

## Partial / pending work

### UI and workflow completion
- Final live testing of the complete payroll and reporting flow in authenticated Perfex remains pending.
- Some settings screens are implemented in source but still need runtime confirmation under the real admin roles and data set.
- Final review of export/PDF/print totals should be measured against the approved saved payroll snapshot, not a recomputation path.

### Payroll and attendance logic
- Core engine logic, effective-date policy lookup, cutoff scoping, and transition validation exist.
- Real policy overrides, approval chains, and cutoff snapshot enforcement need final live verification under real data.
- Break, overtime, rounding, late/absence handling, and correction flows are implemented at the model layer but need app-level validation in the live environment.

### Permissions and authorization
- Access checks exist in controllers and save routes.
- Real non-admin rejection must still be confirmed in the live app with authenticated user roles.

## Genuine blockers
1. The live demo is still returning an HTTP 500 on `GET /admin/field_staff/get_team_monitor?date=2026-10-18` in the authenticated app; this is the current live blocker requiring the corrected attendance/break guard to be deployed or verified in the running instance.
2. The source fix has been implemented locally for malformed break timestamps, stale break-state detection, and invalid transitions while a break is active, but it must still be validated against the live deployed module before closing the issue.
3. No authoritative statutory or legal rule source is available for final TCI/legal validation.
4. Any claim of “fully complete” payroll/reporting behavior would be premature without those live checks.

## Final source package
- Package path: `D:\October projects\field_staff\field_staff_synced_20261016.zip`
- Integrity check: extracted zip content matched the intended source hashes after excluding non-source artifacts, and the expected entry file `field_staff/field_staff.php` is present.
- Source remains synchronized with the working demo structure and with the reconciled local UI fixes.

## Current recommendation
Continue with the live Perfex validation pass only in the real app environment, using the current source as the authoritative local build. The source is ready for final runtime validation; it is not false-positive “tested complete” without real admin data and permission checks.
