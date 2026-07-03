<?php

namespace Tests\Feature\OnlineAdmission;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Student\Models\Student;

class AdmissionApplicationTest extends AdmissionApplicationTestCase
{
    public function test_public_can_submit_an_application_without_auth(): void
    {
        $response = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())
            ->assertCreated()
            ->assertJsonFragment(['status' => 'submitted', 'applicant_name' => 'Alice Applicant']);

        $this->assertNotNull($response->json('data.reference_number'));
        $this->assertStringStartsWith('APP-', $response->json('data.reference_number'));
    }

    public function test_public_status_check_requires_matching_phone(): void
    {
        $submitted = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())->assertCreated();
        $reference = $submitted->json('data.reference_number');

        $this->getJson('/api/v2/admission-applications/status?reference='.$reference.'&guardian_phone=%2B8801700000001')
            ->assertOk()
            ->assertJsonFragment(['status' => 'submitted']);

        $this->getJson('/api/v2/admission-applications/status?reference='.$reference.'&guardian_phone=%2B8801799999999')
            ->assertNotFound();
    }

    public function test_admin_can_approve_and_a_student_is_created(): void
    {
        $submitted = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())->assertCreated();
        $id = $submitted->json('data.id');

        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/admission-applications/{$id}/approve", [
                'admission_number' => 'ADM-APP-1',
                'section_id' => $this->section->id,
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        $studentId = $response->json('data.created_student_id');
        $this->assertNotNull($studentId);

        $student = Student::findOrFail($studentId);
        $this->assertSame('Alice Applicant', $student->name);
        $this->assertSame('ADM-APP-1', $student->admission_number);
        $this->assertSame($this->class->id, $student->currentAcademic->class_id);
        $this->assertSame($this->section->id, $student->currentAcademic->section_id);
        $this->assertCount(1, $student->guardians);
        $this->assertSame('Bob Guardian', $student->guardians->first()->name);
    }

    public function test_admin_can_reject_with_a_reason(): void
    {
        $submitted = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())->assertCreated();
        $id = $submitted->json('data.id');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/admission-applications/{$id}/reject", ['reason' => 'No seats available.'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'rejected', 'decision_reason' => 'No seats available.']);

        $this->assertNull(AdmissionApplication::findOrFail($id)->created_student_id);
    }

    public function test_an_already_decided_application_cannot_be_decided_again(): void
    {
        $submitted = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())->assertCreated();
        $id = $submitted->json('data.id');
        $token = $this->adminToken();

        $this->withToken($token)
            ->postJson("/api/v2/admission-applications/{$id}/reject", ['reason' => 'Duplicate.'])
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v2/admission-applications/{$id}/approve", [
                'admission_number' => 'ADM-APP-2',
                'section_id' => $this->section->id,
            ])
            ->assertUnprocessable();
    }

    public function test_teacher_cannot_review_applications(): void
    {
        $submitted = $this->postJson('/api/v2/admission-applications', $this->applicationPayload())->assertCreated();
        $id = $submitted->json('data.id');

        $this->withToken($this->teacherToken())->getJson('/api/v2/admission-applications')->assertForbidden();
        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/admission-applications/{$id}/approve", [
                'admission_number' => 'ADM-APP-3',
                'section_id' => $this->section->id,
            ])->assertForbidden();
    }

    public function test_index_and_show_require_auth(): void
    {
        $this->getJson('/api/v2/admission-applications')->assertUnauthorized();
        $this->getJson('/api/v2/admission-applications/1')->assertUnauthorized();
    }
}
