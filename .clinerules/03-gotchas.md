# Gotchas Learned + Key Code Patterns

Hard-won lessons from building modules 1–24. Check this before writing new code — most of these bugs have
already been hit once.

## Gotchas
- **Sanctum `tokenCan('*')` matches ANY ability string requested** — a token with bare `'*'` satisfies every
  `ability:`/`tokenCan()` check. Two same-privileged roles (e.g. `admin` vs `super_admin`) can't be
  distinguished via abilities alone — use a real `role:` (Spatie) middleware for that instead.
- **Never type-hint a second `Illuminate\Http\Request $x` alongside a `FormRequest` subclass** in one
  controller method — `FormRequest` extends `Request`, so Laravel's dependency resolver treats it as already
  satisfied and silently skips injecting the second param, causing an `ArgumentCountError`. Just use the
  FormRequest instance itself (it inherits every `Request` method).
- **Mailables implementing `ShouldQueue`**: `Mail::send()` silently redirects through `queue()`. Under
  `Mail::fake()` that's tracked as "queued," not "sent" — use `Mail::assertQueued()`, not `assertSent()`.
- **Fresh-model `JsonResource` auto-returns 201** (via `wasRecentlyCreated`). Calling `->fresh()` on a
  just-created model discards that flag (200 instead) — use `->load()` if you need relations but want to keep
  201. Force `->setStatusCode(200)` explicitly on idempotent PUT/toggle endpoints that use a freshly-inserted row.
- **Sync queue does NOT swallow job exceptions** — under `QUEUE_CONNECTION=sync`, an uncaught exception in a
  job's `handle()` propagates straight into the dispatching HTTP request as a 500. Every queued job must
  catch everything internally and never rethrow.
- **Sanctum guard caching in tests**: call `$this->app['auth']->forgetGuards()` every time a test switches to
  a different user's token, or Laravel resolves the previous (cached) user.
- **Explicit `protected $table`** needed whenever a migration's table name doesn't match Eloquent's
  pluralized-class-name guess (e.g. prefixed names like `lms_courses`).
- **`ForbiddenHttpException` doesn't exist** in Symfony — the 403 exception is `AccessDeniedHttpException`.
- Date-range queries: use `whereDate`, not `whereBetween`, against a `date`-cast column; use the full
  `Y-m-d H:i:s` string in `assertDatabaseHas` (SQLite stores `date` casts as datetime).
- **Shared-counter mutations need `DB::transaction` + `lockForUpdate`, not just "financial" writes** — any
  read-check-then-write on a shared count (stock, seats, quota) races under concurrency. Library `borrow()`
  originally checked `available_copies < 1` then decremented with no lock, so two borrows of the last copy
  both passed and both decremented — overselling and driving the `unsignedInteger` column below zero (a DB
  error → 500). Lock the row inside a transaction, same as `Payment/CreditService`.
- **Derive transient states at read time; never store them as a terminal status** — "overdue" is
  `returned_at IS NULL AND due_at < now()` (a `scopeOverdue`), computed on read. Library once wrote
  `status = 'overdue'` on a late *return*, which made returned and still-outstanding records
  indistinguishable and corrupted every status filter. A late return is still `returned`.

## Key Code Patterns

**Repository (cache-aside):**
```php
class StudentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache) { parent::__construct(Student::class, $cache); }
    public function activeByClass(int $classId, int $schoolId): Collection
    {
        return $this->remember($this->cacheKey("school:{$schoolId}:class:{$classId}:active"),
            fn () => Student::where('school_id', $schoolId)->where('class_id', $classId)
                ->where('status', 'active')->get());
    }
}
```

**Observer (cache flush):**
```php
class StudentObserver
{
    public function saved(Student $student): void { Cache::tags(['student'])->flush(); }
    public function deleted(Student $student): void { Cache::tags(['student'])->flush(); }
}
```

**Financial write (always transactional):**
```php
DB::transaction(function () use ($data) {
    $payment = $this->repository->create($data);
    $this->ledgerRepository->recordDebit($payment);
    event(new PaymentRecorded($payment));
});
```
