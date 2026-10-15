<?php

namespace App\Http\Controllers\Admin;

use App\Services\AdminDashboardService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard) {}

    public function index(): View
    {
        return view('admin.dashboard', $this->dashboard->forSchool(app('current_school_id')));
    }
}
