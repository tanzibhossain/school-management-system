<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Resources\StudentDocumentResource;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function index(int $studentId): AnonymousResourceCollection
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($studentId);

        return StudentDocumentResource::collection($student->documents()->orderBy('document_type')->get());
    }

    public function store(Request $request, int $studentId): StudentDocumentResource
    {
        $request->validate([
            'document_type' => 'required|in:birth_certificate,previous_tc,nid,photo,other',
            'file'          => 'required|file|max:5120',
        ]);

        $student = Student::where('school_id', app('current_school_id'))->findOrFail($studentId);
        $path    = $request->file('file')->store(
            "students/{$student->school_id}/documents", 'minio'
        );

        $document = StudentDocument::create([
            'school_id'     => $student->school_id,
            'student_id'    => $student->id,
            'document_type' => $request->document_type,
            'file_path'     => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'uploaded_by'   => $request->user()->id,
        ]);

        return new StudentDocumentResource($document);
    }

    public function destroy(int $studentId, int $documentId): JsonResponse
    {
        $student  = Student::where('school_id', app('current_school_id'))->findOrFail($studentId);
        $document = StudentDocument::where('student_id', $student->id)->findOrFail($documentId);

        Storage::disk('minio')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}
