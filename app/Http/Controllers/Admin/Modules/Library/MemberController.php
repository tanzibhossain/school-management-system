<?php

namespace App\Http\Controllers\Admin\Modules\Library;

use App\Models\User;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Services\LibraryMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private readonly LibraryMemberService $members) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $members = LibraryMember::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get();

        $users = User::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.modules.library.members.index', compact('members', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->members->make(app('current_school_id'), $this->validated($request, null));

        return back()->with('status', 'Member added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $member = LibraryMember::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->members->modify($member, $this->validated($request, $id));

        return back()->with('status', 'Member updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $member = LibraryMember::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->members->deactivate($member);

        return back()->with('status', 'Member deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id): array
    {
        $schoolId = app('current_school_id');
        $ignore = $id ?? 'NULL';

        return $request->validate([
            'user_id'           => ['required', 'integer', "exists:users,id,school_id,{$schoolId}"],
            'membership_number' => ['required', 'string', 'max:50', "unique:library_members,membership_number,{$ignore},id,school_id,{$schoolId}"],
            'member_type'       => ['required', 'in:student,staff'],
            'joined_at'         => ['nullable', 'date'],
        ], [], ['user_id' => 'user', 'membership_number' => 'membership number', 'member_type' => 'member type']);
    }
}
