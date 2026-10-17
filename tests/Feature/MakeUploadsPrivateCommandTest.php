<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * storage:make-uploads-private — the one-time move for installs whose minio
 * disk is the local fallback (no AWS_ENDPOINT). Both roots point at a scratch
 * folder, never the real storage directories.
 */
class MakeUploadsPrivateCommandTest extends TestCase
{
    private string $public;

    private string $private;

    protected function setUp(): void
    {
        parent::setUp();

        $base = storage_path('framework/testing/uploads-'.uniqid());
        $this->public = $base.'/public';
        $this->private = $base.'/private/files';

        config([
            'filesystems.disks.public.root' => $this->public,
            'filesystems.disks.minio' => ['driver' => 'local', 'root' => $this->private, 'visibility' => 'private'],
        ]);

        $this->writeFile($this->public.'/students/1/photos/p.jpg', 'photo');
        $this->writeFile($this->public.'/1/payroll/2026/1/7.pdf', 'payslip');
        $this->writeFile($this->public.'/site/logo.png', 'logo');
        $this->writeFile($this->public.'/admissions/old.jpg', 'admission');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory(dirname($this->public));

        parent::tearDown();
    }

    private function writeFile(string $path, string $contents): void
    {
        (new Filesystem)->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $contents);
    }

    public function test_it_moves_minio_uploads_and_leaves_the_public_disks_own_folders(): void
    {
        $this->artisan('storage:make-uploads-private')->assertSuccessful();

        $this->assertSame('photo', file_get_contents($this->private.'/students/1/photos/p.jpg'));
        $this->assertSame('payslip', file_get_contents($this->private.'/1/payroll/2026/1/7.pdf'));
        $this->assertDirectoryDoesNotExist($this->public.'/students');
        $this->assertDirectoryDoesNotExist($this->public.'/1');

        $this->assertFileExists($this->public.'/site/logo.png');
        $this->assertFileExists($this->public.'/admissions/old.jpg');
        $this->assertFileDoesNotExist($this->private.'/site/logo.png');
    }

    public function test_dry_run_moves_nothing(): void
    {
        $this->artisan('storage:make-uploads-private', ['--dry-run' => true])
            ->expectsOutputToContain('Would move: students/1/photos/p.jpg')
            ->assertSuccessful();

        $this->assertFileExists($this->public.'/students/1/photos/p.jpg');
        $this->assertDirectoryDoesNotExist($this->private);
    }

    public function test_an_existing_private_file_is_never_overwritten(): void
    {
        $this->writeFile($this->private.'/students/1/photos/p.jpg', 'newer');

        $this->artisan('storage:make-uploads-private')->assertSuccessful();

        $this->assertSame('newer', file_get_contents($this->private.'/students/1/photos/p.jpg'));
        $this->assertFileExists($this->public.'/students/1/photos/p.jpg'); // left for a human to sort out
        $this->assertSame('payslip', file_get_contents($this->private.'/1/payroll/2026/1/7.pdf'));
    }

    public function test_nothing_happens_when_the_minio_disk_is_object_storage(): void
    {
        config(['filesystems.disks.minio' => ['driver' => 's3']]);

        $this->artisan('storage:make-uploads-private')
            ->expectsOutputToContain('nothing to move')
            ->assertSuccessful();

        $this->assertFileExists($this->public.'/students/1/photos/p.jpg');
    }
}
