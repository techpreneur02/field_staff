# Field Staff Module Handoff

## Current phase - 2026-10-22 UTC
Payroll remediation is implemented, regression-tested locally, deployed to the demo with hash verification, and live-verified on dedicated test records for hourly payroll. Full module acceptance is **not** claimed. Statutory verification is intentionally pending, so live approval and payment cannot complete. Non-hourly, unpaid-break, non-admin and print/PDF checks are not yet live-verified.

- Source root: `D:\October projects\field_staff\field_staff`
- Branch: `checkpoint/live-nav-ui-review`
- Authorized remote: `https://github.com/techpreneur02/field_staff.git`
- Use `git log -1` and `git status` for the current revision. The demo runs the latest checkpoint's controller, model and payroll test files (see "Deployment").

## Delivered implementation
1. Configured workweek aggregation, with earlier-cutoff consumption and independent week resets.
2. Validated payable duration and unpaid-only break deduction. Approved clock and break corrections are applied when payroll reads the data; raw punches remain unchanged.
3. One versioned calculation shared by preview, individual save, bulk save and snapshot evidence.
4. Explicitly approved cutoff work inputs for salary, daily, per-job and per-unit pay. Missing inputs are rejected; there is no fallback.
5. Review-only approval, mandatory statutory verification, stale-input detection and protected approved/paid history.
6. Correction blockers follow the affected attendance work date.
7. Hardening found during live testing:
   - `save_payroll` and `apply_payrun` reject recalculating an `under_review` record. Previously it was silently reset to draft without a workflow audit event.
   - `submit_for_review` rejects when unresolved attendance or pending corrections exist for the staff member within the cutoff.

New migration: `2026_10_22_payroll_work_records` (additive CREATE TABLE). It runs from the module activation hook; on the demo it was run by deactivating and reactivating the module. Correction types are `clock_in`, `clock_out` and `break:<id>:break_out_at|break_in_at|ended_at`. No statutory rates were invented.

## Local validation
```powershell
php tests\payroll_regression_checks.php
php tests\field_staff_runtime_checks.php
```
Both pass, and all 12 non-backup PHP files pass `php -l`. Coverage includes:
- 44 regular + 6 overtime; split cutoffs 40/0 + 4/6; two weeks 88/12
- paid versus unpaid breaks (50 versus 45 hours)
- missing non-hourly inputs and late corrections
- preview/save/bulk/snapshot parity and invalid approval transitions
- under-review recalculation rejection on both routes, with rollback
- pending correction blocking submission

A mutation check confirmed the new tests fail without the fix.

## Deployment (demo)
- **Backup:** `/home/erpxolluxn/field_staff_backup_pre_payroll_v2_20261022/` is outside the public root and contains every non-`.git` module file. It was verified 43/43 with identical sizes.
  - The older `/home/erpxolluxn/field_staff_pre_payroll_v2_20261022_0328/` is partial and is **not** a rollback set.
  - The pre-deploy demo build matched no Git commit; it was an untracked intermediate build.
- **Deployed:** checkpoint `7bcd612` (26 files: model, controller, views, assets, root, tests and docs), then the follow-up hardening (controller, model and payroll test).
- **SHA-256 verification:** all files match exactly, except the controller as read back via cPanel `get_file_content`. That API strips one duplicate `<meta charset="utf-8">` literal (22 bytes). Local content with that tag removed hashes identically, and the on-disk size equals local (146,852 bytes).
- **Untouched junk:** the deployed folder still contains junk that was not removed (unreadable `.git`, `backups/`, two nested ZIPs, `install_debug.log`, `_diff.txt`, a stray root `Field_staff.php`). It is excluded from packaging and should be cleaned only with explicit approval.
- **Team monitor:** HTTP 500 before deployment, HTTP 200 JSON after deployment. The stale build was the cause.

## Live verification (https://demo.erpxolluxn.com/admin/)
Dedicated test records were used throughout; no settings were changed and no prior payroll history existed:
- staff 2 "FS Payroll LiveTest" (`fs.payroll.livetest@example.invalid`, not an administrator, hourly at 20, group `fs_live_test`)
- cutoffs 1 `FSLT-W1` (09-07 to 09-11), 2 `FSLT-X1` (09-14 to 09-16) and 3 `FSLT-X2` (09-17 to 09-20)
- ten 07:00–17:00 attendance rows

| Check | Live result |
|---|---|
| 5 × 10 h, 44 h cap | 44 regular + 6 overtime, gross 1060, net 969.90 |
| Workweek crossing cutoffs | cutoff 2: 30/0 (gross 600); cutoff 3: 14/6 (gross 460, net 420.90) |
| Preview = individual save = bulk payrun = snapshot CSV | matched (payroll 1 cutoff 1; payroll 2 cutoff 3 via bulk) |
| Direct save as approved / approve draft / pay draft / bogus action | all rejected |
| Late correction (09-08 clock-out 17:00→16:00, submitted 10-21) | blocked cutoff 1 preview and approval (keyed to work date) |
| Correction approved | recalculated to 44/5, gross 1030, net 942.45; approval then stopped at the statutory gate |
| Re-save or bulk-save while under review (after hardening deploy) | rejected; returned to draft through the workflow |
| Pending correction on 09-17 (cutoff 3) | submission of payroll 2 rejected; correction 2 then rejected (cleanup) |

Final live state: payroll 1 is a draft at 44/5/942.45 and payroll 2 is a draft at 14/6/420.90. Corrections are 1 (approved) and 2 (rejected).

## Remaining work
1. Statutory and legal verification by an authorized person. Approval and payment cannot complete until then, and must never be marked verified just to make the demo pass.
2. Live tests still to run:
   - unpaid-break scenario (manual attendance has no break input; use a clock or break flow)
   - salary/daily/job/unit work-input and missing-input checks
   - non-admin role rejection
   - print/PDF output parity
3. Optional, with approval: remove junk from the deployed module folder.

## Consolidated package
`D:\October projects\field_staff\field_staff.zip` contains one top-level `field_staff/` folder. It excludes Git metadata, backups, logs, credentials, temporary files and nested archives. It is rebuilt and extraction-compared after the final checkpoint.
