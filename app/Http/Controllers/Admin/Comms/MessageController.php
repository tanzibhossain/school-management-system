<?php

namespace App\Http\Controllers\Admin\Comms;

use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageService;
use App\Modules\Messaging\Services\MessagingModerationService;
use App\Modules\Messaging\Services\ThreadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Admin Messaging — the admin acts as a real staff participant when composing/
 * replying, and additionally has read-only oversight (list every thread + lock)
 * via MessagingModerationService. Thin over the Messaging module Services.
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly ThreadService $threads,
        private readonly MessageService $messages,
        private readonly MessagingModerationService $moderation,
    ) {}

    /** My inbox — threads I participate in, with live unread counts. */
    public function index(): View
    {
        $schoolId = app('current_school_id');
        $threads = $this->threads->inbox($schoolId, auth()->id());

        return view('admin.comms.messages.index', [
            'threads' => $threads,
            'userMap' => $this->userMap($threads),
            'users' => $this->composableUsers($schoolId),
        ]);
    }

    /** Oversight — every thread in the school (read-only + lock toggle). */
    public function all(): View
    {
        $schoolId = app('current_school_id');
        $threads = $this->moderation->allThreads($schoolId);

        return view('admin.comms.messages.all', [
            'threads' => $threads,
            'userMap' => $this->userMap($threads),
        ]);
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->with('participants')->findOrFail($id);

        $messages = Message::forSchool($schoolId)->where('thread_id', $thread->id)
            ->with('attachments')->orderBy('id')->get();

        $isParticipant = $thread->participants
            ->firstWhere(fn ($p) => $p->user_id === auth()->id() && $p->left_at === null) !== null;

        // Mark read only when the viewer is actually a participant (admins viewing
        // via oversight are not, and markRead would firstOrFail).
        if ($isParticipant) {
            $this->messages->markRead($schoolId, $thread, auth()->id());
        }

        $ids = $thread->participants->pluck('user_id')
            ->merge($messages->pluck('sender_id'))->unique();

        return view('admin.comms.messages.show', [
            'thread' => $thread,
            'messages' => $messages,
            'userMap' => User::whereIn('id', $ids)->pluck('name', 'id'),
            'isParticipant' => $isParticipant,
        ]);
    }

    /** Compose a new thread (I'm added as a participant automatically). */
    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        try {
            $thread = $this->threads->create(
                $schoolId, auth()->user(),
                $data['participant_ids'], $data['subject'] ?? null, $data['body'],
            );
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.messages.show', $thread->id)->with('status', 'Message sent.');
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->findOrFail($id);
        $data = $request->validate(['body' => ['required', 'string']]);

        try {
            $this->messages->send($schoolId, $thread, auth()->user(), $data['body']);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Reply sent.');
    }

    public function lock(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->findOrFail($id);
        $this->moderation->setLocked($schoolId, $thread->id, ! $thread->is_locked);

        return back()->with('status', $thread->is_locked ? 'Conversation unlocked.' : 'Conversation locked.');
    }

    /** Names for every participant across a set of threads. */
    private function userMap($threads): Collection
    {
        $ids = collect($threads)->flatMap(fn ($t) => $t->participants->pluck('user_id'))->unique();

        return User::whereIn('id', $ids)->pluck('name', 'id');
    }

    /** School users the admin may add to a conversation (active, excluding self). */
    private function composableUsers(int $schoolId): Collection
    {
        return User::where('school_id', $schoolId)->where('is_active', true)
            ->where('id', '!=', auth()->id())->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'label' => $u->name.' — '.$u->getRoleNames()->first()]);
    }
}
