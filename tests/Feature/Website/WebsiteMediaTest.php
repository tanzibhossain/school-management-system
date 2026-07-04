<?php

namespace Tests\Feature\Website;

use App\Modules\Website\Models\WebsiteMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WebsiteMediaTest extends WebsiteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    public function test_admin_can_upload_an_image_and_dimensions_are_extracted(): void
    {
        // .png, not .jpg — the test container's GD build has no JPEG encoder
        // (imagejpeg undefined), confirmed by the delete test below passing fine
        // with a .png fake image. PNG exercises the same getimagesize() dimension
        // extraction path in WebsiteMediaService::upload().
        $file = UploadedFile::fake()->image('hero.png', 400, 300);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/website/media', ['file' => $file, 'alt_text' => 'Hero banner'])
            ->assertCreated()
            ->assertJsonFragment(['width_px' => 400, 'height_px' => 300, 'alt_text' => 'Hero banner']);

        $path = WebsiteMedia::findOrFail($response->json('data.id'))->path;
        Storage::disk('minio')->assertExists($path);
    }

    public function test_admin_can_delete_media_and_the_file_is_removed(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 50, 50);

        $created = $this->withToken($this->adminToken())
            ->post('/api/v2/website/media', ['file' => $file])
            ->assertCreated();
        $id = $created->json('data.id');
        $path = WebsiteMedia::findOrFail($id)->path;

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v2/website/media/{$id}")
            ->assertNoContent();

        Storage::disk('minio')->assertMissing($path);
        $this->assertDatabaseMissing('website_media', ['id' => $id]);
    }

    public function test_index_returns_uploaded_media(): void
    {
        $this->withToken($this->adminToken())
            ->post('/api/v2/website/media', ['file' => UploadedFile::fake()->create('doc.pdf', 10)])
            ->assertCreated();

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/website/media')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
