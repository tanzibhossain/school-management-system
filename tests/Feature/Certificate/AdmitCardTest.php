<?php

namespace Tests\Feature\Certificate;

use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamSeating;
use Illuminate\Support\Facades\Storage;

class AdmitCardTest extends CertificateTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    public function test_generates_admit_card_with_placeholder_when_seating_not_assigned(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertCreated()
            ->assertJsonPath('data.exam.title', 'Annual Exam 2026');

        $path = AdmitCard::first()->file_path;
        $this->assertNotNull($path);
        Storage::disk('minio')->assertExists($path);

        // A real PDF was rendered, not just an empty/placeholder file
        $bytes = Storage::disk('minio')->get($path);
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    public function test_regenerating_updates_the_existing_row_without_duplicating(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)
            ->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertOk();

        $this->assertDatabaseCount('admit_cards', 1);
    }

    public function test_uses_assigned_seating_when_available(): void
    {
        $hall = ExamHall::create([
            'school_id' => $this->school->id,
            'name' => 'Hall A',
            'layout_config' => ['rows' => 1, 'sides' => [['label' => 'L', 'seats_per_row' => 1, 'blocked_rows' => []]]],
        ]);

        $seat = ExamHallSeat::create([
            'hall_id' => $hall->id,
            'row' => 1,
            'side' => 'L',
            'position' => 1,
            'label' => 'R01-L1',
        ]);

        ExamSeating::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'hall_seat_id' => $seat->id,
            'exam_roll' => '001',
        ]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertCreated();

        $path = AdmitCard::first()->file_path;
        Storage::disk('minio')->assertExists($path);
    }

    public function test_non_teacher_non_admin_forbidden(): void
    {
        $this->withToken($this->staffToken())
            ->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson("/api/v2/certificates/admit-cards/{$this->student->id}", ['exam_id' => $this->exam->id])
            ->assertUnauthorized();
    }
}
