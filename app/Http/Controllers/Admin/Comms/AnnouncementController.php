<?php

namespace App\Http\Controllers\Admin\Comms;

use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $announcements) {}

    public function index(): View
    {
        $items = Announcement::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->get();

        return view('admin.comms.announcements.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $publishNow = $request->boolean('publish_now');

        $announcement = $this->announcements->make(app('current_school_id'), $request->user(), [
            'title'      => $data['title'],
            'body'       => $data['body'],
            'type'       => $data['type'],
            'audience'   => $data['audience'],
            'priority'   => $data['priority'],
            'is_pinned'  => $request->boolean('is_pinned'),
            'publish_at' => $publishNow ? now() : ($data['publish_at'] ?? null),
            'expire_at'  => $data['expire_at'] ?? null,
        ]);

        return back()->with('status', 'Announcement ' . ($publishNow ? 'published.' : 'saved as draft.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $announcement = $this->find($id);
        $data = $this->validated($request);

        $announcement->update([
            'title'     => $data['title'],
            'body'      => $data['body'],
            'type'      => $data['type'],
            'audience'  => $data['audience'],
            'priority'  => $data['priority'],
            'is_pinned' => $request->boolean('is_pinned'),
            'expire_at' => $data['expire_at'] ?? null,
        ]); // AnnouncementObserver flushes cache

        return back()->with('status', 'Announcement updated.');
    }

    public function publish(int $id): RedirectResponse
    {
        $this->announcements->publish($this->find($id));

        return back()->with('status', 'Announcement published.');
    }

    public function expire(int $id): RedirectResponse
    {
        $this->announcements->expire($this->find($id));

        return back()->with('status', 'Announcement expired.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->announcements->trash($this->find($id));

        return back()->with('status', 'Announcement deleted.');
    }

    private function find(int $id): Announcement
    {
        return Announcement::where('school_id', app('current_school_id'))->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title'      => ['required', 'string', 'max:200'],
            'body'       => ['required', 'string'],
            'type'       => ['required', 'in:general,event,holiday,exam,fee,other'],
            'audience'   => ['required', 'in:all,teachers,students,parents'],
            'priority'   => ['required', 'in:normal,important,urgent'],
            'publish_at' => ['nullable', 'date'],
            'expire_at'  => ['nullable', 'date', 'after:publish_at'],
        ]);
    }
}
