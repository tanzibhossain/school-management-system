<?php

namespace App\Http\Controllers\Admin\Modules\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Services\BorrowRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use InvalidArgumentException;

class BorrowController extends Controller
{
    public function __construct(private readonly BorrowRecordService $borrows) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $records = BorrowRecord::where('school_id', $schoolId)
            ->with(['book:id,title', 'member:id,membership_number'])
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderBy('due_at')
            ->limit(500)
            ->get();

        return view('admin.modules.library.borrow.index', [
            'records' => $records,
            'books' => Book::where('school_id', $schoolId)->where('is_trash', false)->where('available_copies', '>', 0)->orderBy('title')->get(['id', 'title', 'available_copies']),
            'members' => LibraryMember::where('school_id', $schoolId)->where('is_trash', false)->where('is_active', true)->orderBy('membership_number')->get(['id', 'membership_number', 'member_type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'library_member_id' => ['required', 'integer', "exists:library_members,id,school_id,{$schoolId}"],
            'book_id' => ['required', 'integer', "exists:books,id,school_id,{$schoolId}"],
            'due_at' => ['required', 'date', 'after:today'],
        ], [], ['library_member_id' => 'member']);

        try {
            $this->borrows->borrow($schoolId, [
                'library_member_id' => $data['library_member_id'],
                'book_id' => $data['book_id'],
                'borrowed_at' => now(),
                'due_at' => $data['due_at'],
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Book Issued.'));
    }

    public function markReturned(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $this->borrows->markReturned($schoolId, $id);

        return back()->with('status', __('Book Returned.'));
    }
}
