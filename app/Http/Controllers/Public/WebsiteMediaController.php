<?php

namespace App\Http\Controllers\Public;

use App\Modules\Website\Models\WebsiteMedia;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a Website media file (image/video uploaded via the page editor's
 * Media Library) from the private "minio" bucket to public visitors — the
 * bucket is never exposed directly (see docker-compose.yml's minio-init
 * comment), so this proxy is the only way a page's uploaded media reaches a
 * browser. Deliberately NOT school-scoped: this app is single-school
 * per-deployment (see CLAUDE.md), and the id alone is already
 * unguessable-in-practice/non-enumerable-of-value the same way any other
 * public asset URL is.
 *
 * Long, immutable cache headers are safe: the URL is content-addressed by
 * the media row's own id, which never changes for a given upload — editing
 * a block to use a different image points it at a DIFFERENT id/URL, it
 * never mutates this one in place.
 */
class WebsiteMediaController extends Controller
{
    public function show(int $id): StreamedResponse
    {
        $media = WebsiteMedia::findOrFail($id);
        abort_unless(Storage::disk('minio')->exists($media->path), 404);

        return Storage::disk('minio')->response($media->path, $media->filename, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
