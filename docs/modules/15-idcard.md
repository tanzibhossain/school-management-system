# 15 — IdCard

**Status:** ✅ Done · **Depends on:** Student, Staff · **Path:** `app/Modules/IdCard`

## Scope
This module creates student and staff ID cards in batches and manages the card templates and generated files. It includes queued PDF generation for bulk card production.

## Tables
| Table | Purpose / key columns |
|---|---|
| `id_card_templates` | reusable ID card layout templates |
| `id_card_batches` | batch metadata for generated card sets |
| `id_card_batch_files` | generated output files for each batch |

## API Endpoints
- Template CRUD endpoints
- Batch creation and status tracking endpoints
- Batch file and preview endpoints

## Services & Business Rules
- The first queued job in the system is the `GenerateIdCardBatchJob`.
- Each PDF batch chunks card generation in groups of 200 cards.
- Photos are embedded as base64 data so the PDF renderer can generate them without depending on remote URLs.

## Integration Points
- Uses Student and Staff data for card content.
- Relies on the shared PDF rendering pipeline for output.
