# 27 — Language

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/Language`

## Scope

Database-backed translations and locale management for the platform UI. Active
languages and their translated strings are stored in the database (not language
files), so non-English locales can be edited at runtime without redeploying. The
module also ships a scanner that finds every `__('...')` source string in views
and app code and registers untranslated rows for every active non-English locale,
so the editor always shows the complete current set of UI strings.

English is the source language (English-as-key translation), so `en` needs no
`Translation` rows — untranslated locales simply fall back to the key itself.

## Tables

| Table | Purpose / key columns |
|---|---|
| `languages` | `code` (ISO 639-1, unique), `name`, `native_name`, `flag` (emoji), `is_rtl`, `is_active`, `is_default`, `sort_order` |
| `translations` | `locale`, `key` (TEXT — the English source string), `key_hash` (sha1 of `key`, since TEXT can't be uniquely indexed), `value` (null = not translated yet). Unique on `(locale, key_hash)` |

## Models

`Language`, `Translation`

## Services & Artisan Commands

- `TranslationScanner`
  - `keys()` — regex-scans `resources/views` and `app` for single/double-quoted
    first arguments of `__()` and returns the distinct key set.
  - `sync()` — for every active non-English locale, inserts a `value = null`
    `Translation` row for every key not already present, chunked 500/insert.
    Returns the count of rows added. Called by the `translations:scan` command.
- `translations:scan` — Artisan command wrapping `TranslationScanner::sync()`.
- `translations:export {locale}` — writes a locale's non-null translations to
  `database/seeders/data/translations/{locale}.json` so future seeds ship them.

There is no `LanguageService` — `Language` reads are cached
(`Language::activeCached()`, `Language::defaultCode()`) and writes are expected
to flush the cache via `Language::flushCache()`.

## Integration with the Request Lifecycle

`App\Http\Middleware\SetLocale` (registered in the `web` middleware group in
`bootstrap/app.php`) runs on every web request:

1. Loads active languages and the default code (both cached). Pre-migration
   (installer / early artisan), it catches the error and leaves the app locale
   alone so setup commands don't crash.
2. Resolves the chosen locale from the session (`app_locale`), falling back to
   the default language, and rejecting codes that aren't in the active set.
3. Calls `app()->setLocale($chosen)`.
4. For non-English locales, loads the DB translations
   (`Translation::linesFor()`, cached per locale) and feeds them to the
   translator via `addLines()` with the `*` group (JSON-style keys).
5. Shares `$appLanguages`, `$appLanguage`, and `$appIsRtl` with all views —
   driving the language switcher and RTL layout flag.

## Caching

- `languages:active` (1h) — active language list.
- `languages:default` (1h) — default language code.
- `translations:lines:{locale}` (1h) — the translated lines for a locale.

`Language::flushCache()` clears both language keys; `Translation::flushCache($locale)`
clears a single locale's lines (fired from the `Translation` model's `saved`/`deleted`
observers, which are defined inline on the model).

## Notes

- The module intentionally has **no Http/Controllers, Requests, Resources, or
  routes of its own** in the `Language/` module directory — admin management is
  handled by `App\Http\Controllers\Admin\Setup\LanguageController` (CRUD +
  `setDefault` + `scan`), with views at
  `resources/views/admin/setup/languages/translations.blade.php`. A public
  language switcher route (`GET /language/{code}`) sets the session locale. The
  module itself is data + a service + middleware, which is all a translation
  store needs.
- The `(locale, key_hash)` unique constraint plus the `sha1(key)` computation in
  the `Translation::saving` boot callback keep keys unique even though the `key`
  column is `TEXT` (MySQL can't uniquely index unlimited `TEXT`).
- RTL languages are supported via the `is_rtl` flag, which is shared with views
  as `$appIsRtl` for layout direction.
