<?php

namespace App\Http\Controllers\Admin\Comms;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Services\SmsBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class SmsController extends Controller
{
    public function __construct(private readonly SmsBatchService $sms) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.comms.sms.index', [
            'batches'  => SmsBatch::where('school_id', $schoolId)->orderByDesc('id')->limit(200)->get(),
            'classes'  => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
        ]);
    }

    public function show(int $id): View
    {
        $batch = SmsBatch::where('school_id', app('current_school_id'))->with('logs')->findOrFail($id);

        return view('admin.comms.sms.show', compact('batch'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'scope'      => ['required', 'in:all,class'],
            'class_id'   => ['nullable', 'required_if:scope,class', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['nullable', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'body'       => ['required', 'string', 'max:1000'],
        ]);

        $filters = [
            'scope'      => $data['scope'],
            'class_id'   => $data['class_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
        ];

        try {
            $batch = $this->sms->requestManual($schoolId, $filters, $data['body'], $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.sms.show', $batch->id)
            ->with('status', "SMS batch queued for {$batch->total_count} recipient(s).");
    }
}
