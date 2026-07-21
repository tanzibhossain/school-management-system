<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Modules\Library\Models\LibraryMember;

class LibraryMemberTest extends LibraryTestCase
{
    public function test_admin_can_create_library_member()
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/library/members', [
            'user_id' => $user->id,
            'member_type' => 'student',
            'membership_number' => 'LIB-1001',
        ], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['membership_number' => 'LIB-1001']);
        $this->assertDatabaseHas('library_members', [
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'member_type' => 'student',
            'membership_number' => 'LIB-1001',
        ]);
    }

    public function test_admin_can_deactivate_library_member()
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $member = LibraryMember::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'member_type' => 'student',
            'membership_number' => 'LIB-1002',
            'joined_at' => now(),
        ]);

        $response = $this->postJson("/api/v2/library/members/{$member->id}/deactivate", [], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Member deactivated.']);
        $this->assertDatabaseHas('library_members', [
            'id' => $member->id,
            'is_active' => false,
        ]);
    }
}
