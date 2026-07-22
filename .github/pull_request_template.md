## What does this PR do?

<!-- Brief description of the change and which module(s) it touches. -->

## Related issue

<!-- Closes #123, if applicable -->

## Checklist

- [ ] Followed the 10-step module pattern where applicable (see `CLAUDE.md`)
- [ ] Every new/changed write endpoint has a `FormRequest` + `JsonResource`
- [ ] Controllers stay thin — business logic lives in a Service
- [ ] `school_id` scoping preserved on any new queries
- [ ] Financial or mark-entry writes wrapped in `DB::transaction()`, not cached
- [ ] Tests added/updated and passing: `docker compose exec app php artisan test`
- [ ] Code style clean: `docker compose exec app ./vendor/bin/pint`
- [ ] Static analysis clean: `docker compose exec app ./vendor/bin/phpstan analyse`

## Screenshots (if UI-facing)

<!-- Before/after screenshots for any admin UI changes. -->
