<?php

namespace Tests\Feature\IdCard;

use App\Modules\IdCard\Models\IdCardBatch;
use Illuminate\Support\Facades\Storage;

class IdCardBatchTest extends IdCardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    public function test_admin_can_generate_a_student_batch_for_a_class(): void
    {
        $template = $this->studentTemplate();
        $this->makeStudent();
        $this->makeStudent();
        $this->makeStudent();

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'student',
                'template_id' => $template->id,
                'scope' => 'class',
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_count' => 3])
            ->assertJsonCount(1, 'data.files');

        $batch = IdCardBatch::findOrFail($response->json('data.id'));
        $this->assertSame(3, $batch->files()->first()->card_count);

        $path = $batch->files()->first()->file_path;
        Storage::disk('minio')->assertExists($path);
        $this->assertStringStartsWith('%PDF', Storage::disk('minio')->get($path));
    }

    public function test_batch_splits_into_multiple_files_past_two_hundred_cards(): void
    {
        $template = $this->studentTemplate();

        for ($i = 0; $i < 205; $i++) {
            $this->makeStudent();
        }

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'student',
                'template_id' => $template->id,
                'scope' => 'all',
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_count' => 205])
            ->assertJsonCount(2, 'data.files');

        $batch = IdCardBatch::findOrFail($response->json('data.id'));
        $counts = $batch->files()->orderBy('file_index')->pluck('card_count')->all();
        $this->assertSame([200, 5], $counts);
    }

    public function test_single_scope_targets_explicit_ids(): void
    {
        $template = $this->studentTemplate();
        $one = $this->makeStudent();
        $this->makeStudent();

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'student',
                'template_id' => $template->id,
                'scope' => 'single',
                'target_ids' => [$one->id],
            ])
            ->assertCreated()
            ->assertJsonFragment(['total_count' => 1]);

        $batch = IdCardBatch::findOrFail($response->json('data.id'));
        $this->assertSame(1, $batch->files()->first()->card_count);
    }

    public function test_admin_can_generate_a_staff_batch(): void
    {
        $template = $this->staffTemplate();
        $staff = $this->makeStaff();

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'staff',
                'template_id' => $template->id,
                'scope' => 'single',
                'target_ids' => [$staff->id],
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_count' => 1]);
    }

    public function test_class_scope_is_rejected_for_staff_type(): void
    {
        $template = $this->staffTemplate();

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'staff',
                'template_id' => $template->id,
                'scope' => 'class',
                'class_id' => $this->class->id,
            ])
            ->assertUnprocessable();
    }

    public function test_teacher_can_request_student_batch_but_not_staff_batch(): void
    {
        $studentTemplate = $this->studentTemplate();
        $staffTemplate = $this->staffTemplate();
        $staff = $this->makeStaff();
        $this->makeStudent();

        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'student',
                'template_id' => $studentTemplate->id,
                'scope' => 'all',
            ])
            ->assertCreated();

        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'staff',
                'template_id' => $staffTemplate->id,
                'scope' => 'single',
                'target_ids' => [$staff->id],
            ])
            ->assertForbidden();
    }

    public function test_index_and_show_return_batch_history(): void
    {
        $template = $this->studentTemplate();
        $this->makeStudent();
        $token = $this->adminToken();

        $created = $this->withToken($token)
            ->postJson('/api/v2/id-cards/batches', [
                'type' => 'student',
                'template_id' => $template->id,
                'scope' => 'all',
            ])
            ->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($token)->getJson('/api/v2/id-cards/batches')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v2/id-cards/batches/{$id}")->assertOk()
            ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/id-cards/batches', ['type' => 'student'])->assertUnauthorized();
    }
}
