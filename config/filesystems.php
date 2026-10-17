<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'minio'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Every module in this app calls Storage::disk('minio') directly
        // (grep app/Modules for "disk('minio')" — Certificate, IdCard,
        // DataImport, LMS, Messaging, Student, Payroll, Website all do this),
        // never Storage::disk('s3') or the plain default-disk helpers. This
        // key MUST be named 'minio' to match, even though the driver is
        // still the S3-compatible driver (MinIO speaks the S3 API) — a disk
        // named 's3' here was a silent, repo-wide bug: every one of those
        // calls threw "Disk [minio] does not have a configured driver."
        // against a real MinIO server, and only ever "worked" under tests
        // because Storage::fake('minio') creates a fake disk under that name
        // regardless of whether config/filesystems.php defines one.
        //
        // On shared cPanel hosting there's no MinIO/S3 endpoint to point at,
        // and every module still only ever calls Storage::disk('minio') —
        // so rather than touch 26+ call sites, the 'minio' key itself falls
        // back to a plain local disk whenever AWS_ENDPOINT isn't configured.
        // Set AWS_ENDPOINT/AWS_BUCKET/etc. to point at a real MinIO server or
        // any S3-compatible provider (AWS S3, DigitalOcean Spaces, Backblaze
        // B2, Cloudflare R2, …) to use object storage instead — nothing else
        // in the app needs to change either way.
        //
        // The fallback is PRIVATE: these are student/staff photos, documents,
        // certificates, payslips, message attachments. Nothing here is
        // web-reachable by path; the app streams files through its own
        // role-checked routes, and temporaryUrl() hands out short-lived
        // signed links served at /files/{path} ('serve' registers that
        // route; it rejects any request without a valid signature). Before
        // 1.5.0 this fallback lived in storage/app/public and was reachable
        // by anyone with the path — existing installs move their files once
        // with `php artisan storage:make-uploads-private`.
        'minio' => env('AWS_ENDPOINT') ? [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/private/files'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/files',
            'serve' => true,
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
