<?php

namespace App\Http\Controllers\Public;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public contact-form submission (rendered by the Website `contact` block).
 * Stores the enquiry for admins to read in the contact inbox.
 */
class ContactController extends Controller
{
    public function submit(Request $request): RedirectResponse
    {
        $school = School::current();
        abort_unless($school, 404);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'email'   => ['nullable', 'email', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($data + ['school_id' => $school->id]);

        return back()->with('contact_sent', true);
    }
}
