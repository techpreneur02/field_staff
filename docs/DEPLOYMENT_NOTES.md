# Deployment Notes

## Current environment
- Local authoritative source: `D:\October projects\field_staff\field_staff`
- Hosted module directory: `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff`
- Demo admin session: reachable and logged in enough to load the admin dashboard; `GET /admin/field_staff/get_attendance_state` returns HTTP 200 JSON, which confirms the attendance-state endpoint is healthy.
- Team-monitor route under live validation: `GET /admin/field_staff/get_team_monitor?date=2026-10-18` currently returns HTTP 500 in the demo app.
- cPanel access: the tool-controlled browser currently lands on the fresh cPanel login page rather than an authenticated File Manager session, so the hosted file backup and module overwrite remain blocked until secure browser sign-in is completed.
- Local backup location: `D:\October projects\field_staff\_module_backups\field_staff_backup_20261014_223943.zip`
- Local review package path: `D:\October projects\field_staff\field_staff_review_package_20261019.zip`

## Verified deployment status
- Local source is the authoritative working copy for further implementation.
- The hosted demo is reachable, the admin app loads, and the attendance-state endpoint responds successfully.
- The live team-monitor endpoint still fails with HTTP 500, which is the active blocker for remote acceptance.
- The exact PHP traceback is not yet exposed to the browser because cPanel/server-side log access is not currently authenticated in this environment; this needs secure browser sign-in before the remote host can be backed up and overwritten.
- No deployment has been claimed as complete based on source-only checks; live acceptance remains the gate for final sign-off.

## Safe update and rollback guidance
1. Save a working backup before any server-side file replacement.
2. Compare local and hosted files by actual filesystem content and hash before overwriting.
3. Preserve the module bootstrap `field_staff.php` and avoid replacing core Perfex files.
4. If a live issue is discovered, restore the last known-good module backup and refresh the browser before testing again.
5. Keep all credentials, session tokens, and cPanel access details out of source-controlled documentation.

## Outstanding differences and risks
- A known deployment risk was the presence of an accidental duplicate controller copy outside the canonical module path. This must be checked before any final upload.
- The module source and the live demo should be treated as distinct until the hosted deployment is hash-checked and behaviorally verified.
- TCI statutory/HR rules remain a separate external verification item and should not be assumed complete without authoritative source review.

## Verification checklist for the next demo pass
- Dedicated test employee created and used for attendance/break/correction workflows
- Save → reload → persisted values check for HR settings
- Unauthorized request rejection for protected endpoints
- Attendance and break state transitions validated
- Payroll approval/snapshot workflow validated
- Print/PDF/CSV totals matched to saved payroll results
- Final differences and rollback notes recorded after each accepted change
