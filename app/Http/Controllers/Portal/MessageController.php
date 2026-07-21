<?php

namespace App\Http\Controllers\Portal;

use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageService;
use App\Modules\Messaging\Services\MessagingPolicyService;
use App\Modules\Messaging\Services\ThreadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Family portal messaging. Students and guardians may only converse with staff
 * (enforced by the Messaging policy); the recipient list is staff-only.
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly ThreadService $threads,
        private readonly MessageService $messages,
    ) {}

    public function index(): View
    {
        $sid = app('current_school_id');
        $threads = $this->threads->inbox($sid, auth()->id());

        return view('portal.messages.index', [
            'threads' => $threads,
            'userMap' => $this->userMap($threads),
            'staffUsers' => $this->staffUsers($sid),
            'isGuardian' => auth()->user()->hasRole('parent'),
        ]);
    }

    public function show(int $id): View
    {
        $sid = app('current_school_id');
        $thread = MessageThread::forSchool($sid)->with('participants')->findOrFail($id);

        $isParticipant = $thread->participants
            ->firstWhere(fn ($p) => $p->user_id === auth()->id() && $p->left_at === null) !== null;
        abort_unless($isParticipant, 403);

        $messages = Message::forSchool($sid)->where('thread_id', $thread->id)
            ->with('attachments')->orderBy('id')->get();

        $this->messages->markRead($sid, $thread, auth()->id());

        $ids = $thread->participants->pluck('user_id')->merge($messages->pluck('sender_id'))->unique();

        return view('portal.messages.show', [
            'thread' => $thread,
            'messages' => $messages,
            'userMap' => User::whereIn('id', $ids)->pluck('name', 'id'),
            'isGuardian' => auth()->user()->hasRole('parent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sid = app('current_school_id');
        $data = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        try {
            $thread = $this->threads->create(
                $sid, auth()->user(), $data['participant_ids'], $data['subject'] ?? null, $data['body'],
            );
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.messages.show', $thread->id)->with('status', 'Message sent.');
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $sid = app('current_school_id');
        $thread = MessageThread::forSchool($sid)->findOrFail($id);
        $data = $request->validate(['body' => ['required', 'string']]);

        try {
            $this->messages->send($sid, $thread, auth()->user(), $data['body']);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Reply sent.');
    }

    private function userMap($threads): Collection
    {
        $ids = collect($threads)->flatMap(fn ($t) => $t->participants->pluck('user_id'))->unique();

        return User::whereIn('id', $ids)->pluck('name', 'id');
    }

    /** Staff a family member is allowed to message. */
    private function staffUsers(int $schoolId): Collection
    {
        return User::where('school_id', $schoolId)->where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->role(MessagingPolicyService::STAFF_ROLES)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'label' => $u->name.' — '.($u->getRoleNames()->first() ?? 'staff')]);
    }
}
