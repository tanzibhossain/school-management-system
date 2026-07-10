<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');

        return view('admin.dashboard', [
            'studentCount' => Student::where('school_id', $schoolId)->where('status', 'active')->count(),
            'staffCount'   => Staff::where('school_id', $schoolId)->where('status', 'active')->count(),
            'classCount'   => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->count(),
        ]);
    }
}
