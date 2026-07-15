<?php

namespace App\Http\Controllers\Admin\Comms;

use App\Modules\Website\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/** Admin inbox for public contact-form enquiries. */
class ContactMessageController extends Controller
{
    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.comms.contact.index', [
            'messages' => ContactMessage::forSchool($schoolId)->orderByDesc('created_at')->get(),
            'unread'   => ContactMessage::forSchool($schoolId)->where('is_read', false)->count(),
        ]);
    }

    public function markRead(int $id): RedirectResponse
    {
        $message = ContactMessage::forSchool(app('current_school_id'))->findOrFail($id);
        $message->update(['is_read' => ! $message->is_read]);

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        ContactMessage::forSchool(app('current_school_id'))->findOrFail($id)->delete();

        return back()->with('status', 'Message deleted.');
    }
}
