<?php

namespace App\Modules\Messaging\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * The who-can-talk-to-whom guardrails. In a school you never want an open DM
 * graph: non-staff (student/parent) may only share a thread WITH staff, never
 * with each other. Every thread must always retain at least one staff member.
 */
class MessagingPolicyService
{
    public const STAFF_ROLES = ['super_admin', 'admin', 'teacher', 'accountant', 'librarian', 'receptionist'];

    public function isStaff(User $user): bool
    {
        return $user->hasAnyRole(self::STAFF_ROLES);
    }

    /**
     * Validate a proposed participant set (which includes the initiator).
     *
     * @param  Collection<int, User>  $participants
     */
    public function assertValidParticipants(User $initiator, Collection $participants, int $schoolId): void
    {
        if ($participants->count() < 2) {
            throw new UnprocessableEntityHttpException('A thread needs at least two participants.');
        }

        // Same school for everyone.
        if ($participants->contains(fn (User $u) => $u->school_id !== $schoolId)) {
            throw new UnprocessableEntityHttpException('All participants must belong to the same school.');
        }

        // At least one staff member in the thread.
        if (! $participants->contains(fn (User $u) => $this->isStaff($u))) {
            throw new UnprocessableEntityHttpException('A conversation must include at least one staff member.');
        }

        // A non-staff initiator may only message staff.
        if (! $this->isStaff($initiator)) {
            $nonStaffOthers = $participants
                ->reject(fn (User $u) => $u->id === $initiator->id)
                ->filter(fn (User $u) => ! $this->isStaff($u));

            if ($nonStaffOthers->isNotEmpty()) {
                throw new UnprocessableEntityHttpException('You can only start a conversation with staff members.');
            }
        }
    }

    /** Only staff may add participants, and never to a direct thread. */
    public function assertCanAddParticipant(User $adder, User $newUser, int $schoolId): void
    {
        if (! $this->isStaff($adder)) {
            throw new UnprocessableEntityHttpException('Only staff can add participants to a conversation.');
        }

        if ($newUser->school_id !== $schoolId) {
            throw new UnprocessableEntityHttpException('That user belongs to a different school.');
        }
    }
}
