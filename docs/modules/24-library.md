# 24 — Library

**Status:** ✅ Done · **Depends on:** Student, Staff · **Path:** `app/Modules/Library`

## Scope
Optional module for library operations: book cataloging and inventory, member management (students and staff), and the borrow/return circulation lifecycle. Gated behind the `module.enabled:library` middleware and available to `admin` and `librarian` roles.

## Tables
| Table | Purpose / key columns |
|---|---|
| `books` | library inventory and metadata; `total_copies`, `available_copies` (unsigned) |
| `library_members` | student/staff membership records |
| `borrow_records` | lending lifecycle; `borrowed_at`, `due_at`, `returned_at`, `status` enum(`borrowed`,`returned`,`overdue`) |

## API Endpoints
- Admin/librarian endpoints for book catalog management (CRUD).
- Member enrollment and (de)activation endpoints.
- Borrow and mark-returned circulation endpoints.

## Services & Business Rules
- **Circulation is concurrency-safe.** `BorrowRecordService::borrow()` and `markReturned()` run inside a `DB::transaction` and take a `lockForUpdate` on the `books` row before touching `available_copies`. Without the lock, two concurrent borrows of the last copy both pass the `available_copies < 1` check and both decrement — overselling the book and driving the `unsignedInteger` column below zero (a DB error). The stock change and the `BorrowRecord` insert commit atomically, so a failed decrement can never orphan a borrow row.
- **"Overdue" is derived, never stored.** A book that is handed back is always `status = 'returned'`, even if late. Whether a loan is overdue is computed from the data — `returned_at IS NULL AND due_at < now()` — via `BorrowRecord::scopeOverdue()` (with a companion `scopeOutstanding()`). Storing `overdue` as a terminal status would make returned and still-outstanding records indistinguishable and corrupt every status filter.
- All queries are `school_id`-scoped via `scopeForSchool`; the repository is cache-aside and flushed on write.

## Integration Points
- Depends on Student and Staff for the members that borrow books.
- The `Librarian` role's access is limited to this module (see README role matrix).
