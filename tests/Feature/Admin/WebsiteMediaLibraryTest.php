<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\WebsiteMedia;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin › Website media library (page editor's Media Library modal). Covers
 * the endpoints backing it — see docs/modules/28-elementor-block-editor-plan.md
 * §7h (upload/list/delete) and §7q (alt-text editing).
 */
class WebsiteMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_upload_list_and_delete(): void
    {
        $this->actingAs($this->admin);

        $this->getJson('/admin/media')->assertOk()->assertJson([]);

        $file = UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg');
        $upload = $this->postJson('/admin/media', ['file' => $file]);
        $upload->assertStatus(201)->assertJsonPath('filename', 'photo.jpg')->assertJsonPath('is_image', true);

        $media = WebsiteMedia::first();
        $this->assertNotNull($media);
        $this->assertSame($this->school->id, $media->school_id);
        Storage::disk('minio')->assertExists($media->path);

        $this->getJson('/admin/media')->assertOk()->assertJsonCount(1);

        $this->deleteJson("/admin/media/{$media->id}")->assertOk()->assertJson(['deleted' => true]);
        $this->assertDatabaseMissing('website_media', ['id' => $media->id]);
        Storage::disk('minio')->assertMissing($media->path);
    }

    public function test_update_sets_alt_text(): void
    {
        $this->actingAs($this->admin);

        $media = WebsiteMedia::create([
            'school_id' => $this->school->id,
            'filename' => 'logo.png',
            'path' => 'website/media/logo.png',
            'mime_type' => 'image/png',
            'size_bytes' => 123,
        ]);

        $this->putJson("/admin/media/{$media->id}", ['alt_text' => 'School logo'])
            ->assertOk()->assertJsonPath('alt_text', 'School logo');

        $this->assertDatabaseHas('website_media', ['id' => $media->id, 'alt_text' => 'School logo']);

        // Clearing it back out (blank) is a valid, supported state — not a validation error.
        $this->putJson("/admin/media/{$media->id}", ['alt_text' => ''])
            ->assertOk();
    }

    public function test_media_is_scoped_to_the_owning_school(): void
    {
        $this->actingAs($this->admin);

        $otherSchool = School::create([
            'name' => 'Other School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $theirs = WebsiteMedia::create([
            'school_id' => $otherSchool->id,
            'filename' => 'not-yours.jpg',
            'path' => 'website/media/not-yours.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 10,
        ]);

        $this->getJson('/admin/media')->assertOk()->assertJsonCount(0);
        $this->putJson("/admin/media/{$theirs->id}", ['alt_text' => 'x'])->assertNotFound();
        $this->deleteJson("/admin/media/{$theirs->id}")->assertNotFound();
    }
}
