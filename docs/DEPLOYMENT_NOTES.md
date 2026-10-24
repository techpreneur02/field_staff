# Deployment Notes

## Current environment - 2026-10-23 UTC
- Local authoritative source: `D:\October projects\field_staff\field_staff`
- Hosted module directory: `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff`
- Demo admin session: authenticated dashboard was available. Earlier attendance-state success is historical evidence, not a fresh validation of the current source.
- Secure access gate: the SSH host key for 162.35.104.112 has changed, and `ssh` now fails with `REMOTE HOST IDENTIFICATION HAS CHANGED!`. The host identity must be verified and trusted before remote deployment access can proceed.
- Preferred deployment model: a dedicated SFTP/SSH account with chroot or internal-sftp confinement to `/home/erpxolluxn/demo.erpxolluxn.com/modules/field_staff` only. An SSH key alone is not a directory restriction. FTPS with TLS verification is the fallback only if the host cannot support chrooted SFTP.
- Team-monitor request: `GET /admin/field_staff/get_team_monitor?date=2026-10-21` returned HTTP 500 and an empty body before deployment.
- cPanel File Manager: authenticated, but whole-module copy returned Permission denied and created only a partial backup at `/home/erpxolluxn/field_staff_pre_payroll_v2_20261022_0328/`. After normal browser verification, the controlled affected-file copy retry still received the host HTML request-verification page. This is not a certified rollback set.
- Local backup location: `D:\October projects\field_staff\_module_backups\field_staff_backup_20261014_223943.zip`
- Consolidated package path: `D:\October projects\field_staff\field_staff.zip`

## Required manual access step
1. Verify the server host fingerprint from the provider/admin records.
2. Remove the stale entry for 162.35.104.112 from `%USERPROFILE%\.ssh\known_hosts` if it is no longer valid.
3. Reconnect and confirm the host fingerprint matches the trusted provider value.
4. Create a dedicated restricted SFTP/SSH account and confine it to the module directory only.
5. Do not use unrestricted main-account access or an SSH key alone as the directory restriction.

## Verified deployment status
- Local source is the authoritative working copy for further implementation.
- The hosted demo is reachable and authenticated, but no new source files or schema changes were deployed during this remediation.
- The live team-monitor endpoint still fails with HTTP 500, which is the active blocker for remote acceptance.
- The exact PHP exception has not been obtained. The listed application logs were older than this request; stale logs cannot establish the current HTTP 500 cause.
- No deployment has been claimed as complete based on source-only checks; live acceptance remains the gate for final sign-off.
- Run existing module activation/upgrade migrations only after verified backup and deployment; the new work-input migration is `2026_10_22_payroll_work_records`. Do not mark statutory verification cleared merely to pass an approval test.

## Safe update and rollback guidance
1. Save a working backup before any server-side file replacement.
2. Compare local and hosted files by actual filesystem content and hash before overwriting.
3. Preserve the module bootstrap `field_staff.php` and avoid replacing core Perfex files.
4. If a live issue is discovered, restore the last known-good module backup and refresh the browser before testing again.
5. Keep all credentials, session tokens, and cPanel access details out of source-controlled documentation.

## Outstanding differences and risks
- A duplicate `Field_staff.php` was observed at the hosted module root in addition to the canonical `controllers/Field_staff.php` path. It was not altered; investigate it from a verified backup rather than deleting files speculatively.
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
