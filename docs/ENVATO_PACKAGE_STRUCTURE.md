# Envato Package Structure

Use this structure for the upload zip and documentation zip.

## 1. Main Download Package

Recommended top-level zip layout:

- field_staff/
- documentation/
- changelog/

## 2. Installable Module Folder

Inside field_staff/ include only runtime files:
- field_staff.php
- install.php
- controllers/
- models/
- views/
- assets/

Do not include:
- local environment files
- IDE config folders
- temporary logs
- test fixtures not required at runtime

## 3. Documentation Folder

Inside documentation/ include:
- README.md
- docs/INDEX.md
- docs/SETUP_AND_OPERATIONS.md
- docs/PERMISSIONS_MATRIX.md
- docs/API_ENDPOINTS.md
- docs/WORKFLOWS.md
- docs/ENVATO_READINESS.md
- docs/ENVATO_UAT_CHECKLIST.md
- docs/ENVATO_ITEM_PAGE_TEMPLATE.md
- docs/SUPPORT_POLICY.md
- CHANGELOG.md

## 4. Changelog Folder

Inside changelog/ include:
- CHANGELOG.md

## 5. Versioning Rules

Before each release:
- Update version in field_staff.php module header
- Update CHANGELOG.md with release date and notes
- Update documentation where behavior changed

## 6. Packaging Procedure

1. Create clean staging folder.
2. Copy runtime files into field_staff/.
3. Copy docs into documentation/.
4. Copy changelog into changelog/.
5. Create final zip from staging root.
6. Verify zip extracts with expected structure.
