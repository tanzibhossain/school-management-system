<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Resources\StudentCreditResource;
use App\Modules\Payment\Models\CreditTransaction;
use App\Modules\Payment\Models\StudentCredit;
use App\Modules\Student\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CreditController extends Controller
{
    public function balance(int $studentId): StudentCreditResource
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($studentId);

        $credit = StudentCredit::firstOrCreate(
            ['school_id' => app('current_school_id'), 'student_id' => $student->id],
            ['balance' => 0],
        );

        return new StudentCreditResource($credit);
    }

    public function transactions(Request $request, int $studentId): JsonResponse
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($studentId);

        $transactions = CreditTransaction::where('school_id', app('current_school_id'))
            ->where('student_id', $student->id)
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'data' => $transactions->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'note' => $t->note,
                'created_at' => $t->created_at->toIso8601String(),
            ]),
            'meta' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }
}
