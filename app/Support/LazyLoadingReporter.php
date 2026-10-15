<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Lazy-loading violation handler (see AppServiceProvider): records each
 * distinct N+1 — a relation lazy-loaded on a model that came from a
 * multi-row query — once per process to storage/logs/lazy-loading.log.
 *
 * Under the test suite it throws (Laravel's default behaviour), so a new N+1
 * fails CI. Everywhere else outside production it only logs, and Laravel still
 * performs the load after the handler returns, so local browsing never breaks.
 * Note: Laravel only flags models from queries returning more than one row, so
 * a test only catches an N+1 if it creates at least two rows for that list.
 */
final class LazyLoadingReporter
{
    /** @var array<string, true> */
    private static array $seen = [];

    public static function report(Model $model, string $relation): void
    {
        // Same exemption as Laravel's default handler: unsaved / just-created
        // models have nothing to eager-load yet.
        if (! $model->exists || $model->wasRecentlyCreated) {
            return;
        }

        // In the test suite a new N+1 is a failure, not a log line.
        if (app()->runningUnitTests()) {
            throw new LazyLoadingViolationException($model, $relation);
        }

        $where = self::callSite();
        $key = $model::class.'::'.$relation.'@'.$where;

        if (isset(self::$seen[$key])) {
            return;
        }
        self::$seen[$key] = true;

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/lazy-loading.log'),
        ])->warning(sprintf('Lazy loaded [%s] on [%s] at %s', $relation, $model::class, $where));
    }

    /** First stack frame outside vendor/ — the line that touched the relation. */
    private static function callSite(): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $vendor = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;
        $compiledViews = 'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views';

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60) as $frame) {
            $file = $frame['file'] ?? '';

            if ($file === '' || $file === __FILE__ || str_contains($file, $vendor)) {
                continue;
            }

            $line = $frame['line'] ?? 0;

            if (str_contains($file, $compiledViews)) {
                return (self::bladeSource($file) ?? $file).' (compiled view line '.$line.')';
            }

            return str_replace($base, '', $file).':'.$line;
        }

        return 'unknown';
    }

    /** Compiled Blade files end with a PATH comment naming the source template. */
    private static function bladeSource(string $compiled): ?string
    {
        $contents = @file_get_contents($compiled);

        if ($contents !== false && preg_match('#/\*\*PATH (.+?) ENDPATH\*\*/#', $contents, $m)) {
            return str_replace(base_path().DIRECTORY_SEPARATOR, '', $m[1]);
        }

        return null;
    }
}
