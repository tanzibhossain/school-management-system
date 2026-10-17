<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * One-time move for installs without object storage (shared cPanel hosting).
 * Before 1.5.0 the 'minio' disk's local fallback kept every upload — student
 * and staff photos, documents, certificates, payslips, message attachments —
 * in storage/app/public, where anyone with the path could open it. The
 * fallback is now private (config/filesystems.php); this moves the old files
 * across.
 *
 * Everything under the public disk's root moves except the folders the public
 * disk owns itself (site logo/favicon, admission photos from before those went
 * private). Safe to re-run: a file already at the destination is never
 * overwritten, it is reported and left where it is.
 */
class MakeUploadsPrivateCommand extends Command
{
    protected $signature = 'storage:make-uploads-private {--dry-run : List what would be moved without moving anything}';

    protected $description = 'Move uploads the old public fallback of the minio disk left in storage/app/public into private storage';

    /** Top-level folders written by the 'public' disk itself — these stay public. */
    private const PUBLIC_DISK_FOLDERS = ['site', 'admissions'];

    public function handle(Filesystem $files): int
    {
        $disk = config('filesystems.disks.minio');
        if (! is_array($disk) || ($disk['driver'] ?? null) !== 'local') {
            $this->info('The minio disk uses object storage (AWS_ENDPOINT is set) — nothing to move.');

            return self::SUCCESS;
        }

        $from = rtrim((string) config('filesystems.disks.public.root'), '/\\');
        $to = rtrim((string) ($disk['root'] ?? ''), '/\\');
        if ($to === '' || $from === $to) {
            $this->error('The minio disk still points at the public storage folder — update config/filesystems.php first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;

        foreach ($files->directories($from) as $folder) {
            $name = basename($folder);
            if (in_array($name, self::PUBLIC_DISK_FOLDERS, true)) {
                continue;
            }

            foreach ($files->allFiles($folder, true) as $file) {
                $relative = $name.'/'.str_replace('\\', '/', $file->getRelativePathname());
                $target = $to.'/'.$relative;

                if ($files->exists($target)) {
                    $this->warn("Skipped, already in private storage: {$relative}");
                    $skipped++;

                    continue;
                }
                if (! $dryRun) {
                    $files->ensureDirectoryExists(dirname($target));
                    $files->move($file->getPathname(), $target);
                }
                // A dry run lists every file; a real run only with -v.
                $this->line(($dryRun ? 'Would move: ' : 'Moved: ').$relative, verbosity: $dryRun ? null : 'v');
                $moved++;
            }

            if (! $dryRun && $files->allFiles($folder, true) === []) {
                $files->deleteDirectory($folder);
            }
        }

        $summary = ($dryRun ? 'Would move' : 'Moved')." {$moved} file(s) to {$to}";
        $this->info($skipped > 0 ? "{$summary}; skipped {$skipped} already there." : "{$summary}.");

        return self::SUCCESS;
    }
}
