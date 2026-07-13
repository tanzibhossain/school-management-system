<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\DataImport\Models\ImportBatch;
use App\Modules\DataImport\Services\ImportBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DataImportController extends Controller
{
    public function __construct(private readonly ImportBatchService $imports) {}

    public function index(): View
    {
        $batches = ImportBatch::where('school_id', app('current_school_id'))
            ->orderByDesc('id')->limit(500)->get();

        return view('admin.people.data-import.index', compact('batches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:student,staff'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $batch = $this->imports->request(
            app('current_school_id'),
            $request->string('type'),
            $request->file('file'),
            $request->user(),
        );

        return redirect()->route('admin.data-import.show', $batch->id)
            ->with('status', "Import processed — {$batch->success_count} imported, {$batch->skipped_count} skipped.");
    }

    public function show(int $id): View
    {
        $batch = ImportBatch::where('school_id', app('current_school_id'))->findOrFail($id);

        return view('admin.people.data-import.show', compact('batch'));
    }
}
