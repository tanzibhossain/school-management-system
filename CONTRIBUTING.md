# Contributing

Contributions are welcome — bug fixes, new modules, translations, docs.

## Getting started

1. Fork the repository
2. Clone it and follow the [Quick Start](README.md#-quick-start-docker) in the README to get a local environment running
3. Create a feature branch off `dev`:
   ```bash
   git checkout dev
   git checkout -b feature/amazing-feature
   ```

## Making changes

Follow the **10-step module pattern** described in `CLAUDE.md` — one commit
per step where practical:

```
Migration → Model → Repository → Service → Observer → Requests → Resources → Controller/Routes → Tests → Pint/DocBlocks
```

Commit messages follow `type(module): short description` (`feat`, `fix`,
`test`, `refactor`, `chore`, `docs`) — see `CLAUDE.md`'s Git Commit Convention
section for the full rationale and examples.

## Before opening a PR

```bash
docker compose exec app php artisan test              # tests
docker compose exec app ./vendor/bin/pint              # code style
docker compose exec app ./vendor/bin/phpstan analyse    # static analysis
```

Every PR also runs these automatically via GitHub Actions — the
[Tests](.github/workflows/tests.yml), [Code Style](.github/workflows/pint.yml),
and [Static Analysis](.github/workflows/phpstan.yml) workflows. The badges at
the top of the README reflect the current state of `dev`.

Submit your PR against `dev` (not `main`).

## Code Standards

- PHP 8.3, Laravel 13, strict types
- PSR-12 + Laravel Pint (run before commit)
- [Larastan](https://github.com/larastan/larastan) (PHPStan for Laravel) at level 5, raised over time
- Every write endpoint: `FormRequest` + `JsonResource` (never a raw Eloquent model in a response)
- Controllers ≤ 40 lines/method — business logic lives in Services
- Repository pattern for reads (cached via `Cache::tags()`), Services for writes (transactional)
- Every table/query scoped to `school_id`

## Reporting bugs / requesting features

Use the issue templates — they'll prompt you for what's actually useful
(repro steps, module, environment). Please don't use a public issue for
security vulnerabilities — see [SECURITY.md](SECURITY.md) instead.

## Scope

This project is deliberately **single-school and self-hosted** — no
multi-tenant SaaS layer, no separate frontend. PRs that reintroduce those
assumptions are likely out of scope; everything else (new modules,
localization, bug fixes, docs, tests) is welcome.
