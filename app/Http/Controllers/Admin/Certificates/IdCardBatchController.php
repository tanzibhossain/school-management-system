<?php

namespace App\Http\Controllers\Admin\Certificates;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardBatchFile;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\IdCard\Services\IdCardBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IdCardBatchController extends Controller
{
    public function __construct(private readonly IdCardBatchService $batches) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.certificates.id-cards.index', [
            'batches' => IdCardBatch::where('school_id', $schoolId)->with('template:id,name')->withCount('files')->orderByDesc('id')->limit(500)->get(),
            'templates' => IdCardTemplate::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name', 'type']),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'type' => ['required', 'in:student,staff'],
            'template_id' => ['required', 'integer', "exists:id_card_templates,id,school_id,{$schoolId}"],
            'scope' => ['required', 'in:class,all'],
            'class_id' => ['nullable', 'required_if:scope,class', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['nullable', 'integer', "exists:sections,id,school_id,{$schoolId}"],
        ]);

        $batch = $this->batches->request(
            $schoolId, $data['type'], $data['template_id'], $data['scope'],
            ['class_id' => $data['class_id'] ?? null, 'section_id' => $data['section_id'] ?? null],
            $request->user(),
        );

        return redirect()->route('admin.id-cards.show', $batch->id)->with('status', "Batch queued for {$batch->total_count} card(s).");
    }

    public function show(int $id): View
    {
        $batch = IdCardBatch::where('school_id', app('current_school_id'))
            ->with(['template:id,name', 'files' => fn ($q) => $q->orderBy('file_index')])
            ->findOrFail($id);

        return view('admin.certificates.id-cards.show', compact('batch'));
    }

    public function download(int $id, int $fileId): Response
    {
        $schoolId = app('current_school_id');
        IdCardBatch::where('school_id', $schoolId)->findOrFail($id);
        $file = IdCardBatchFile::where('school_id', $schoolId)->where('batch_id', $id)->findOrFail($fileId);

        abort_unless($file->file_path && Storage::disk('minio')->exists($file->file_path), 404);

        return response(Storage::disk('minio')->get($file->file_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="id-cards-'.$id.'-'.$file->file_index.'.pdf"',
        ]);
    }
}
