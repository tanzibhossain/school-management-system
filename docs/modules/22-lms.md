# 22 — LMS

**Status:** ✅ Done · **Depends on:** Academic, Student · **Path:** `app/Modules/LMS`

## Scope
This optional module supports learning management features such as courses, lessons, assignments, submissions, and AI-based submission checks.

## Tables
| Table | Purpose / key columns |
|---|---|
| `lms_courses` | course definitions |
| `lms_lessons` | course lesson content |
| `lms_assignments` | assignments attached to lessons or courses |
| `lms_submissions` | student assignment submissions |
| `lms_submission_ai_checks` | AI evaluation results for submissions |

## API Endpoints
- Course, lesson, assignment, submission, and AI-check endpoints
- Module-enabled routes controlled by the shared module-setting middleware

## Services & Business Rules
- AI submission checking has two interchangeable providers, picked by `LMS_AI_PROVIDER` (`config/lms.php`):
  - `anthropic` (default) — calls the real Anthropic Messages API using each school's own key, entered in
    School Settings (`schools.lms_ai_api_key`). Paid, per-request cost, no extra infrastructure.
  - `self_hosted` — calls a small FastAPI service (`services/ai-detector/`, added as the `ai-detector` service
    in `docker-compose.yml`) running `desklib/ai-text-detector-v1.01` (Hugging Face, MIT license) entirely
    locally. Free and unlimited, but needs its own container and does **not** run on plain shared cPanel
    hosting the way the rest of this app does (see `docs/cpanel-deployment.md`) — only pick this provider if
    you're running the full Docker Compose stack. Authenticates with a single shared secret
    (`LMS_AI_SELF_HOSTED_SECRET`, set identically on the app and the `ai-detector` container), not a
    per-school key — `SchoolResource`'s `lms_ai_checker_configured` flag reflects this (always `true` under
    `self_hosted`, since there's no per-school credential to be missing).
  - Both providers implement the same `AiCheckerContract` and return the same `AiCheckResult` shape
    (`ai_score` 0–100, `likely_ai_generated`, `originality_note`) — switching providers needs no other code
    change. `AppServiceProvider` binds the contract based on `config('lms.ai_provider')`.
  - The underlying model scores "how AI-generated does this text look", not plagiarism (matching against
    sources) — same scope either way.
  - **Known limitation (self-hosted provider):** less reliable on very short or casual submissions — verified
    by hand: a two-sentence formal paragraph and a two-sentence casual one both scored 90+ ("likely AI") even
    though only one actually was. Full assignment-length essays scored correctly (a genuinely AI-style essay
    scored 100, a genuinely human one with a personal voice and normal imperfections scored 48/"not AI"). This
    is a known weak spot of AI-text detectors generally, not specific to this integration — treat a flagged
    *short* submission as a signal to look closer, not a verdict on its own.
- `AssignmentAiCheckJob` deliberately catches every exception from the checker and never rethrows, recording
  `status=failed` on the check record instead. This is intentional: under `QUEUE_CONNECTION=sync` (tests, and
  any deployment without Horizon running) an uncaught exception crashes the HTTP request that dispatched the
  job rather than triggering a queue retry. `$tries`/`backoff()` only matter for a real async Horizon worker.
- Uses the shared `module.enabled:{name}` middleware for optional enablement.
- Submission evaluation is asynchronous and external-service based.

## Integration Points
- Depends on Academic for course structure and Student for learner context.
- Works as an optional teaching and assessment extension.

## Setting Up the Self-Hosted AI Checker
Only needed if you want the free/unlimited provider instead of Anthropic. Requires the full Docker Compose
stack (not plain cPanel hosting).

1. In `.env`, set:
   ```
   LMS_AI_PROVIDER=self_hosted
   LMS_AI_SELF_HOSTED_SECRET=<any random string>
   ```
   `docker-compose.yml`'s `ai-detector` service reads the same variable, so the app and the detector always
   share the same secret automatically — nothing else to sync by hand.
2. Build and start the container:
   ```
   docker compose up -d --build ai-detector
   ```
   The first build takes several minutes — it bakes the ~1.7GB model into the image at build time so the
   container starts ready to serve, with no internet access needed at runtime after that.
3. Confirm it's healthy:
   ```
   docker compose logs -f ai-detector
   docker compose exec app curl http://ai-detector:8000/health
   ```
   There's no published host port — `ai-detector` is only reachable from other containers on the same
   Compose network (same as `db`/`redis`/`minio`), so the health check has to run from inside another
   container.
4. Restart the app and queue worker so they pick up the new `.env` values:
   ```
   docker compose restart app horizon
   ```

To switch back to Anthropic later, set `LMS_AI_PROVIDER=anthropic` in `.env` and restart `app`/`horizon` again
— `ai-detector` can keep running idle or be stopped, either is fine. If you do want to stop it, a plain
`docker compose down` **will not** — that only tears down services in the currently active profile set, which
is empty by default, so a profile-gated service like `ai-detector` is silently left running (`docker compose
ps -a` will still show it as `Up` after `down`). Stop it explicitly instead:
```
docker compose stop ai-detector
```
or tear the whole stack down including it:
```
docker compose --profile ai-detector down
```
