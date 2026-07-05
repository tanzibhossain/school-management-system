# 18 — DataImport

**Status:** ✅ Done · **Depends on:** Student, Academic · **Path:** `app/Modules/DataImport`

## Scope
This module imports student and staff data from uploaded files using queued processing and the existing domain services. It focuses on batch tracking and error reporting rather than a full standalone import engine.

## Tables
| Table | Purpose / key columns |
|---|---|
| `import_batches` | batch level metadata, file reference, and import status |

## API Endpoints
- Import batch create and status endpoints
- Import error review endpoints

## Services & Business Rules
- Each row is processed through the existing `StudentService::enrol()` and `StaffService::hire()` services.
- File reading is handled through the Excel package and MinIO-backed storage.
- Errors are persisted as structured JSON so failed rows can be reviewed.

## Integration Points
- Reuses Student and Staff business logic rather than duplicating it.
- Depends on Academic data for class/section mapping during import.
