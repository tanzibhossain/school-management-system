# Global Product Rules, Git Convention & Run/Ship

## Global Product Rules
V2 is global (v1 was BD-only) — never bake BD assumptions into core code.
- Locale (currency/timezone/locale/academic_year_pattern, weekend days) — BD values are a seed template only.
- Gateway policy by `country_code`: BD=bKash+SSLCommerz, else Stripe+PayPal. Each school has its own gateway
  credentials. Gateways declare `SUPPORTED_CURRENCIES`; never hardcode a gateway choice.
- Grading templates: `bd_national_5.0`, `us_letter_4.0`, `uk_9_1`, `percentage_only` — school picks one, seeds
  `grade_boundaries`, editable per class after.
- Result strategy is pluggable per class: `bd_national`, `simple_average`, `weighted_average`, `percentage_only`.
- All user-facing strings via translation keys (English default).
- Institution code is generic (label configurable). Addresses are free-form, no BD geo tables.
- Scope: BD schools class 3–10 + college (HSC 11–12). Degree/credit-hour systems out of scope.

## Git Commit Convention
```
type(module): short description
Types: feat | fix | test | refactor | chore | docs
```
Aim for 2–3 commits per session, one per 10-step stage where practical.

## After Every Module — Run & Ship
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/{Module}/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/{module}-module
git add app/Modules/{Module}/ tests/Feature/{Module}/ <other touched shared files>
git commit -m "feat({module}): <short description>"
git checkout dev
git merge --no-ff feature/{module}-module
git push origin dev
git branch -d feature/{module}-module
```
Rules: never merge with failing tests; shared-file edits (AppServiceProvider, bootstrap/app.php,
routes/console.php, the build-status table in `.clinerules/01-build-status.md` or `CLAUDE.md`) belong in the
module's commits; update the module's status before merging.
