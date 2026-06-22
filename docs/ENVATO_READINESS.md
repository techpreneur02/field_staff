# Envato Readiness Guide

This document is the master checklist for preparing this module for Envato submission.

## 1. Submission Scope

Item type target:
- PHP Script / Add-on module package for ERP environments

Core value proposition:
- Geolocation attendance
- HR operations workspace
- Payroll and statutory handling
- Report export and payrun workflow
- Strict staff-ID allowlist access controls for HR/payroll workspace

## 2. Required Submission Assets

Prepare these assets before submission:
- Main package zip (installable)
- Documentation package zip
- Item thumbnail (80x80)
- Item preview image(s)
- Item cover image (Envato-compliant dimensions)
- Feature screenshots (desktop and responsive states)
- Optional preview video URL
- Changelog
- Support policy

## 3. Code and Packaging Standards

- No hardcoded credentials, API keys, or private URLs.
- No debug output, dev-only logs, or stack traces in shipped package.
- Include only required runtime files.
- Ensure all user-facing error messages are clear and non-sensitive.
- Keep version value aligned across module metadata and changelog.

## 4. Documentation Requirements

Must include:
- Installation and upgrade steps
- Server requirements and compatibility notes
- Permissions setup guide
- Endpoint and workflow usage guide
- Troubleshooting section
- Support scope and response policy

## 5. Functional QA Gate

Before packaging, verify:
- Attendance clock in/out with GPS data works
- Restricted tabs are hidden for unauthorized users
- Staff allowlist updates persist and enforce access rules
- Reporting exports generate expected CSV columns
- Payrun statement builds correctly for valid periods

## 6. Security and Compliance Gate

- CSRF protection enabled on mutating actions.
- Access checks enforced server-side for all protected endpoints.
- Input validation present for all payload fields.
- Output escaping applied in views where needed.
- No unsafe direct SQL interpolation for user-controlled inputs.

## 7. Envato Submission Notes

- Use clear release notes describing what changed and why.
- Use consistent naming in item page, docs, and code package.
- Include update instructions for existing buyers in each release.

## 8. Final Pre-Submission Checklist

1. Run full UAT from docs/ENVATO_UAT_CHECKLIST.md.
2. Build package using docs/ENVATO_PACKAGE_STRUCTURE.md.
3. Validate docs zip includes setup, API, permissions, workflows, support.
4. Confirm screenshots match current UI.
5. Confirm changelog includes latest release notes.
