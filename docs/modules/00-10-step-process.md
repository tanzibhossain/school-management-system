# 00 — 10-Step Process

This repository follows a consistent 10-step module development process for every new module.

## 10-Step Pattern
1. Migration(s)
2. Model
3. Repository (cache-aside)
4. Service
5. Observer (cache flush)
6. FormRequests (Store + Update)
7. Resource + Collection
8. Controller + routes
9. Tests
10. Pint + docblocks

## Purpose
- Keeps module work predictable and reviewable.
- Ensures each module has data, business logic, validation, API resources, and tests.
- Aligns with the project architecture, where controllers remain thin and services/repositories own business logic.

## Notes
- Every write endpoint should use a `FormRequest` with `authorize()` and `rules()`.
- Responses should return `JsonResource` objects, not raw Eloquent models.
- Repository methods should use cache-aside patterns and observers should flush cache tags on save/delete.
- Financial and marks-related writes should be wrapped in database transactions and should not be cached.
- Shared module-level conventions such as `school_id` scoping and tenant-aware design should be applied consistently.
