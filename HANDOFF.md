# Field Staff Module Handoff

## Current phase - 2026-10-22 UTC
Local payroll remediation implemented, regression-tested and pushed to the existing remote. Controlled demo deployment is blocked before overwrite by host file-operation verification and an incomplete backup. This is not full module/live acceptance.

- Source root: `D:\October projects\field_staff\field_staff`
- Branch: `checkpoint/live-nav-ui-review`
- Authorized remote: `https://github.com/techpreneur02/field_staff.git`
- Resumed base: `012587b` (one existing unpushed checkpoint was preserved).
- Implementation checkpoint: `39857630830f11e9cfff4746ceb576ea54ee1391`, pushed and verified with `git ls-remote`. This handoff is committed in a follow-up documentation checkpoint.
- Current source/checkpoint identity: use `git log -1` and `git status`; no self-referential commit hash is embedded here.

## Delivered implementation
1. Configured workweek aggregation with earlier-cutoff consumption and independent week resets.
2. Validated payable duration, unpaid-only break deduction and read-time approved clock/break corrections; raw punches remain unchanged.
3. Shared versioned calculation for preview, individual save, bulk save and snapshot evidence, including explicit employee adjustments and configured deductions.
4. Explicit approved cutoff work inputs for salary, daily, per-job and per-unit pay. Missing rates/quantities/approvals reject instead of falling back. Daily coverage is complete; salary has exactly one whole-cutoff amount.
5. Review-only payroll approval, mandatory statutory verification, stale-input detection, legacy-draft recalculation and protected approved/paid history.
6. Correction blockers follow affected attendance dates rather than submission dates.
7. Payroll register workflow controls and saved CSV/print/PDF links; transactional component/snapshot/audit failure handling.

New migration: `2026_10_22_payroll_work_records`, run through the existing module activation/upgrade migration mechanism. It adds work-input storage without rewriting payroll history. Work inputs are append-only, with actor/time/reference. Do not edit/revoke approved inputs by direct database mutation.

Correction types: `clock_in`, `clock_out`, or `break:<break_id>:break_out_at|break_in_at|ended_at`; legacy `attendance` is accepted only when its before-value identifies exactly one clock field. Approved corrections are applied in request-ID order and conflicting targets block payroll.

Workweek grouping uses the attendance work date; the complete shift remains owned by that work-date cutoff. No new statutory rates or legal proration rules were invented. Non-hourly approved amounts are explicit contractual work inputs, not inferred statutory entitlements.

## Local validation
Run from the source root:

```powershell
php tests\payroll_regression_checks.php
php tests\field_staff_runtime_checks.php
```

Both pass. All **12** non-backup module PHP files pass `php -l`. Both suites also pass from the extracted 39-file clean package; SHA-256 comparison found zero missing, extra or mismatched included source files. The final ZIP is rebuilt after this documentation checkpoint.

Numerical evidence: five ten-hour shifts at a configured 44-hour cap produce **44 regular + 6 overtime**, gross **1060** at fixture hourly rate 20 / multiplier 1.5, net **932.80** at fixture-only statutory percentages. Split cutoffs produce **40/0 + 4/6**. Two weeks produce **88/12**. Paid/unpaid one-hour daily breaks yield **50/45 payable hours**. Tests also exercise actual controller saves, snapshot parity, explicit adjustments, missing payment inputs, late corrections, raw-punch immutability, authorization, rollback and workflow UI gating.

The query-builder fixture suite is not proof of MySQL migrations, PDF rendering or real role/session behavior in hosted Perfex.

## Hosted evidence and blocker
- Demo admin and cPanel were authenticated during this session; authentication alone did not make file operations reliable.
- Before deployment, `/admin/field_staff/get_team_monitor?date=2026-10-21` returned HTTP 500, empty body. No confirmed server exception/root cause was obtained.
- Outside-public-root backup attempt: `/home/erpxolluxn/field_staff_pre_payroll_v2_20261022_0328/`. It returned Permission denied and contains only a partial copy.
- After normal browser verification, the controlled affected-file copy retry still returned the host's HTML request-verification page, not successful copy results.
- No module overwrite, demo schema migration or live payroll/staff mutation was performed. No other sites, Perfex core or security settings were modified.
- Never bypass/change host verification settings. Re-establish normal authenticated file operations and verify the backup before deploying.

## Next authorized steps
1. Verify Git push/checkpoint status against the existing remote; preserve all changes.
2. Obtain a complete, hash-verified affected-file backup outside the public root. The partial folder above is not a certified rollback.
3. Deploy only to `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff/`; verify deployed SHA-256 hashes and run idempotent module activation/upgrade migrations.
4. Diagnose team-monitor HTTP 500 from authenticated host logs, then test dedicated attendance/break/correction/payroll records in `https://demo.erpxolluxn.com/admin/`.
5. Verify review/approval/payment, saved CSV/print/PDF totals and non-admin rejection without changing existing payroll history. Statutory verification must remain pending until authoritative sources are reviewed.
6. Rebuild the final package after any source change and extract/hash-compare it.

## Consolidated package
`D:\October projects\field_staff\field_staff.zip`: one top-level `field_staff/`, no Git metadata, backups, logs, credentials, temporary files or nested archives. Packaging is a source deliverable, not deployment acceptance. See BUILD_PROGRESS.md for the current acceptance boundaries.
