# Field Staff User Guide

This guide is written for end users, HR staff, managers, payroll operators, and system administrators using the Field Staff module.

It covers:
- who should use each role
- what each role can do
- day-to-day action flow
- key FYI notes and common warnings
- frequently asked questions

## 1. Module overview

Field Staff is a workforce management module for attendance, time tracking, payroll setup, leave tracking, and HR review workflows.

Typical users include:
- Admins and system administrators
- HR and payroll staff
- Supervisors and managers
- Employees
- Reviewers or approvers

The module is designed to support:
- attendance check-in / check-out
- leave tracking
- shift assignment and distribution
- payroll profile management
- payroll cutoff and snapshot review
- CSV export and payroll reporting

## 2. Role map

| Role | Typical access | Typical tasks |
|---|---|---|
| System Administrator | Full setup and configuration | install module, configure permissions, manage HR settings, manage payroll gate rules |
| HR / Payroll Admin | Payroll and HR operations | employee setup, attendance review, payroll processing, approval workflows, exports |
| Supervisor / Manager | Team-focused access | review team attendance, approve adjustments, staff assignment visibility |
| Employee | Personal attendance and self-service | clock in / out, submit leave, view personal records |
| Reviewer / Approver | limited review and approval access | approve corrections, sign off payroll actions where permitted |

## 3. Role-by-role guide

### 3.1 System Administrator

Responsibilities:
- module installation and activation
- permission setup
- role allowlist configuration
- review module settings and access rules
- confirm payroll and HR workflows are configured correctly

Typical tasks:
1. Install or activate the Field Staff module.
2. Verify the module is enabled in the admin area.
3. Configure the list of staff users allowed to access HR/payroll tools.
4. Set manager/supervisor role access when project assignment or team actions are required.
5. Review attendance and payroll data before sign-off.

Key FYI:
- Module access should be restricted to the exact roles that need it.
- Do not grant unlimited access to everyone in the company.
- Payroll and review actions should follow your internal approval process.

### 3.2 HR / Payroll Admin

Responsibilities:
- maintain employee payroll profiles
- manage attendance disputes and corrections
- validate payroll snapshots
- prepare/report payroll totals
- review employees and departments

Typical tasks:
1. Open HR Settings or payroll/workspace pages.
2. Review employee profile data and payroll setup.
3. Confirm pay rates, deductions, and allowances are correct.
4. Validate attendance records before payroll generation.
5. Generate reports or exports for review.
6. Review cutoff totals and saved snapshots for accuracy.

Key FYI:
- Payroll exports should be checked against the saved record before final approval.
- Historical payroll data should not be silently overwritten.
- Corrections and reviewed records should preserve an audit trail.

### 3.3 Supervisor / Manager

Responsibilities:
- monitor assigned staff activity
- review team attendance and leave
- approve or verify assignment changes
- help resolve schedule or attendance issues

Typical tasks:
1. Review live team attendance.
2. Check employee status, clock-in/out records, and notes.
3. Review leave records for assigned staff.
4. Verify work assignments and role access where applicable.

Key FYI:
- Supervisors should only see the teams and staff they are authorized to manage.
- If a staff member is missing from view, check access rights and assignment scope.

### 3.4 Employee

Responsibilities:
- clock in and out
- view personal attendance
- log or request leave
- maintain basic personal updates related to workforce activity

Typical tasks:
1. Access the employee attendance portal.
2. Clock in when starting work.
3. Clock out when finishing work.
4. Submit a leave request or note if required.
5. Check your attendance or schedule status.

Key FYI:
- Clock in/out actions should be completed in a timely and accurate manner.
- Keep any notes or exceptions factual and clear.
- If a time entry appears incorrect, raise it through the correct review process.

### 3.5 Reviewer / Approver

Responsibilities:
- review payroll or attendance exceptions
- approve corrections or decisions
- confirm payroll is ready for final action

Typical tasks:
1. Open the relevant review screen.
2. Check the correction or payroll item.
3. Confirm statutory and policy rules are still satisfied.
4. Approve or reject according to policy.

Key FYI:
- Approval is not a substitute for proper data validation.
- Reviewers should verify evidence before approving.

## 4. Recommended workflow by process

### 4.1 Attendance workflow

1. Employee logs in to the attendance portal.
2. Employee clocks in.
3. Employee works the assigned shift.
4. Employee clocks out.
5. Manager or HR reviews records if there is a late entry, correction, or exception.
6. Any correction is approved through the defined process.

### 4.2 Leave workflow

1. Employee submits leave request.
2. Manager or HR reviews request.
3. Leave status is updated.
4. Attendance and payroll calculations account for the approved leave state.

### 4.3 Payroll workflow

1. HR or payroll admin selects the relevant cutoff.
2. Review saved payroll data and totals.
3. Validate attendance and adjustments before generating or saving payroll.
4. Export CSV or generate print/PDF results when needed.
5. Review final totals and compare with the register or saved snapshot.
6. Approve only when totals and source data match.

## 5. FYI: important operational notes

- The module is designed for workforce and payroll processes, not for general accounting replacement.
- Payroll totals must be checked against the saved cutoff data and exported output before approval.
- Never assume a page text or loaded summary is enough proof; compare the actual numeric totals and records.
- Approved historical payroll snapshots should remain protected from silent overwrite.
- Changes to permissions and workflow access should follow your internal staff governance process.
- Please keep employee records and payroll output confidential and only share them with authorized people.

## 6. FAQ

### Q: I can’t see a tab or menu option.
A: Check whether your role or staff ID has the required access. Admins and HR/payroll managers may need to configure the allowlist or permission group.

### Q: Why does a payroll record display a different total than expected?
A: Check the selected cutoff, saved payroll snapshot, and any correction or approval state. Compare the register total with the saved snapshot and exported totals before approving.

### Q: Can employees edit payroll data?
A: No. Payroll data and related review processes are typically restricted to HR/payroll admin and designated approvers.

### Q: What if I find a wrong attendance record?
A: Follow the correction workflow and use the authorized correction/review process. Do not change records outside the approved path.

### Q: What if a PDF export opens as HTML or a blank page?
A: This usually indicates a PDF generation or export issue. Check the actual response headers and the generated output. Use the supported PDF facility in the system and validate that the output begins with a valid PDF signature.

### Q: Why is my staff member missing from team views?
A: This is often caused by missing access permissions, role assignment, or a department/assignment scope issue.

### Q: Can a manager view all employees in the company?
A: Only if they are explicitly granted the correct access. The module should use role and permission restrictions to limit access.

### Q: Is the module a replacement for legal or tax compliance review?
A: No. This module supports operational workflow and payroll processing, but statutory and legal compliance should still be reviewed by the correct internal or external authority.

## 7. Best practices

- Use least-privilege access.
- Keep HR, payroll, and employee access separate.
- Review attendance exceptions early.
- Validate totals before export and before approval.
- Maintain documentation of any approved correction or policy override.
- Keep backups and clear change records for operational continuity.

## 8. Quick reference

### Admin actions
- module activation
- permission allowlists
- Role / access review
- payroll and HR settings

### HR actions
- payroll profile updates
- export review
- correction workflow
- approval checks

### Employee actions
- clock in/out
- leave submission
- personal attendance review

### Manager actions
- team attendance monitoring
- assignment checks
- leave review
- correction escalation

## 9. Support and escalation

If a user cannot complete a task or sees an unexpected result:
1. confirm the correct role and access rights
2. check the relevant cutoff or selected record
3. verify the data source and saved snapshot
4. escalate to HR/admin support if the workflow or data validity is in question

Document the problem clearly and include the following:
- employee or staff name
- date/time of issue
- page or workflow used
- expected result
- actual result
- any screenshot or export file

## 10. Summary

The Field Staff module is most effective when each user works within the role that matches their responsibility. The main principle is simple:

- Employees handle their own attendance and leave tasks.
- Supervisors monitor assigned operations.
- HR and payroll admins handle setup, validation, and approvals.
- Administrators maintain access and configuration integrity.

This keeps the module secure, auditable, and easier to operate consistently.
