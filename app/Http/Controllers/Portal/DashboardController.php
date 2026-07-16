<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Family portal (student + guardian). Placeholder for Phase 1 — the full
 * dashboard and menu are built in the next phase.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('portal.dashboard');
    }
}
