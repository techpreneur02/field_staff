# Field Staff Module Handoff

## Current phase - 2026-10-23 UTC
The verified repository state is on `checkpoint/live-nav-ui-review` and is ready for release packaging. This update preserves the current implementation state, adds the user guide to the module, and prepares the package for GitHub publication without claiming final live demo acceptance.

- Source root: `D:\October projects\field_staff\field_staff`
- Branch: `checkpoint/live-nav-ui-review`
- Working tree: not yet committed at the start of this packaging task; all current source changes remain in the repo and will be committed as one package update.
- Documentation: the end-user guide is now included in the module under `docs/USER_GUIDE.md` and linked from `docs/INDEX.md`.
- ZIP status: the package was rebuilt to a valid single-root archive with one top-level `field_staff/` folder and explicit manifest + SHA verification. The current `field_staff.zip` is aligned with the source tree and suitable for release packaging checks.
- Demo/deployment status: exact hosted SHA comparison on `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff/views/attendance_dashboard.php` is verified against the local source (`FF08C867972CF3F571F91C90F3EF31C983AA385ABDD979854780A265BE6A31F7`). This confirms the hosted file currently matches the authoritative local source for that file.
- Access gate: the remote host cannot be trusted until its SSH host key is restored. `ssh` currently fails with `REMOTE HOST IDENTIFICATION HAS CHANGED!` for `162.35.104.112`, which blocks secure deployment access and any restricted-user setup.
- Preferred access model: a dedicated SFTP/SSH account with chroot to `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff` only. SSH-key-only access is not a directory restriction. FTPS with TLS verification is the fallback only if the host cannot support chrooted SFTP.
- Live acceptance: still unverified beyond the exact file SHA match; final live functional approval remains a separate task once access and employee-session validation are authorized.

## Break lifecycle milestone - 2026-10-22 UTC (current)
### Implemented and locally tested
- Authoritative server state and action allowlist for Clocked out -> Clock In -> Working -> Break Out -> On break -> Break In -> Working -> Clock Out.
- Every clock/break mutation locks the stable employee row with `SELECT ... FOR UPDATE` before reading state. Transactions protect insertion/return/closure, and private mutation methods prevent unguarded internal writes.
- Multiple paid/unpaid breaks are retained. Break In cannot change the type selected at Break Out. Duplicate/invalid transitions and overlapping/malformed/future timestamps fail closed. No missing return is automatically closed or deducted as a full shift.
- Approved correction overlays also drive operational state, including a closing correction on an otherwise open raw shift. Raw punches remain unchanged.
- Additive migration `2026_10_22_attendance_raw_punches`: existing rows default to 0; new device punches retain their actual timestamps with `raw_punches=1`. Configured rounding/fixed-start policy is applied at calculation time, not by rewriting the punch. Midnight rounding retains day carry.
- Manual attendance accepts explicit date/time values for overnight shifts, rejects malformed/reversed inputs, and cannot overwrite raw punches, open shifts, break-bearing records or approved/paid payroll periods. Same-day legacy time-only input remains supported.
- My Work and authorized Team Operations show status, clock-in, break start/type and elapsed timer. Controls start disabled, use only server-confirmed allowed actions, block duplicate requests and show a processing label. Errors retain the last confirmed state and display the server's safe message.
- History refreshes after success; status/timer restore on reload. Non-admin users do not see the team monitor. HR Settings links to live Team Operations.
- Payroll, attendance reports and analytics share a validated workweek/payable-hours ledger. Unresolved report totals are explicitly marked pending instead of showing successful zero-hour totals.

### Local validation
All three CLI suites pass:
```powershell
php tests\attendance_lifecycle_checks.php
php tests\payroll_regression_checks.php
php tests\field_staff_runtime_checks.php
```
All 13 non-backup PHP files pass lint, and rendered portal JavaScript parses successfully. New numerical coverage: five rounded 10-hour raw shifts with a 30-minute unpaid break and a 15-minute paid break per shift produce 47.5 payable hours = 44 regular + 3.5 overtime, gross 985 at fixture rates. Preview/individual/bulk/snapshot, attendance summary and analytics parity is asserted. Overnight 22:00-06:00 with a 30-minute unpaid break produces 7.5 hours. Missing/malformed/ended-only/overlapping breaks block calculation.

### Controlled deployment and live evidence
- New backup: `/home/erpxolluxn/field_staff_backup_pre_breaks_20261022/`, outside the public root. Copied controllers/models/views/tests and affected root documents/files; direct read-back content comparison matched all 10 existing files that were then affected.
- Uploaded and hash-verified eight files: controller, model, portal view, HR settings view, activation/install files, lifecycle test and test bootstrap. The known cPanel controller meta-tag read-back quirk was accounted for. Reactivation ran the additive migration before the new model/view deployment.
- Browser fetch uploads hit host verification; ordinary multipart form navigation succeeded. No verification/security setting was bypassed.
- Dedicated employee session: staff 2, no administrator rights. A temporary random test password was set without email and discarded. Synthetic fixture GPS 21.8/-72.1 was used for these tests, not represented as a real employee location.
- First test shift: 2026-10-22 00:43:55-00:46:14 (business time). Unpaid break 00:44:53-00:45:31 = 38 seconds; paid break 00:45:41-00:46:13 = 32 seconds. History showed 0.489444 payable hours under the existing rounding policy, subtracting unpaid time only.
- The elapsed unpaid-break timer persisted and advanced after reload. Break In restored Working, the second paid break remained paid even when an unpaid type was posted at return, and Clock Out restored Clocked out.
- Duplicate UI Clock In clicks generated exactly one request. Parallel Break Out and parallel Clock In requests each yielded one success and one invalid-transition rejection. The duplicate-clock-in test created one additional short shift (attendance 28), subsequently clocked out with 0 rounded payable hours.
- Invalid Clock Out during a break was rejected. A deliberately invalid UI request showed the actual safe server error and retained On break; all four actions were disabled during processing.
- As staff 2, team monitoring and payroll generation both returned HTTP 403. No privileged data was returned.
- Final employee state was Clocked out. The test session was then logged out for admin restoration. No real employee attendance or prior payroll history was changed.

### Final deployment delta and remaining live work
The demo has the initial tested break build, not the final local source. After access is restored, deploy these 11 updated files and hash-verify them:
`controllers/Field_staff.php`, `models/Field_staff_model.php`, `views/attendance_dashboard.php`, `views/hr_management.php`, all four `tests/*.php` files, `HANDOFF.md`, `BUILD_PROGRESS.md`, `CHANGELOG.md`.
Activation/install files and the HR settings view already matched the final source at initial deployment; the raw-punch migration has already run.

Retest approved closing corrections, explicit overnight manual input, report/analytics parity and the final UI hardening. Then, as admin:
1. Compare staff 2's My Work, Team Operations, history and cutoff 4 `FS-BREAK-20261022` payroll (2026-10-22). No cutoff-4 payroll was saved during this session.
2. Exercise a closed/missing-return fixture through preview/submission blockers and an explicitly authorized correction, without fabricating automatic returns.
3. Run salary/daily/job/unit missing-input and approved-work-input scenarios on dedicated records.
4. Verify saved print/PDF output; unauthenticated/access-denied HTML must never be counted as a PDF pass.
5. Obtain authoritative statutory verification before live approval/payment. Do not change verification solely for testing.

The remaining requests were attempted where access allowed; admin-only live results remain blocked, not passed.

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
Both original suites pass, with the added lifecycle suite described above. Coverage includes:
- 44 regular + 6 overtime; split cutoffs 40/0 + 4/6; two weeks 88/12
- paid versus unpaid breaks (50 versus 45 hours)
- missing non-hourly inputs and late corrections
- preview/save/bulk/snapshot parity and invalid approval transitions
- under-review recalculation rejection on both routes, with rollback
- pending correction blocking submission

A mutation check confirmed the new tests fail without the fix.

## Earlier payroll deployment (demo; see current break delta above)
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
The current break milestone above is authoritative. Non-admin rejection and the employee paid/unpaid lifecycle are now live-verified; final deployment, admin-side parity, overnight/non-hourly/print/PDF and statutory approval/payment remain pending. Optional deployed junk cleanup still requires approval.

## Consolidated package
`D:\October projects\field_staff\field_staff.zip` contains 41 files under one top-level `field_staff/` folder. It excludes Git metadata, backups, logs, credentials, temporary files and nested archives. The release candidate extracted with zero extra, missing or SHA-256-mismatched files, and all three suites passed from the extraction. The ZIP is rebuilt and revalidated after the final checkpoint; the final response records its SHA-256.
