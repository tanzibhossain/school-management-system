<?php

namespace App\Modules\School\Http\Controllers;

use App\Modules\School\Http\Requests\UpdateModuleSettingRequest;
use App\Modules\School\Http\Resources\ModuleSettingResource;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Services\ModuleSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class ModuleSettingController extends Controller
{
    public function __construct(
        private readonly ModuleSettingService $service,
    ) {}

    /** GET /v2/school/modules — every optional module + its current on/off state. */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->allForSchool(app('current_school_id'))->values(),
        ]);
    }

    /** PUT /v2/school/modules/{module} — admin only. */
    public function update(UpdateModuleSettingRequest $request, string $module): JsonResponse
    {
        if (! in_array($module, ModuleSetting::MODULES, true)) {
            throw ValidationException::withMessages(['module' => 'Unknown module.']);
        }

        $setting = $this->service->setEnabled(
            app('current_school_id'),
            $module,
            (bool) $request->validated('is_enabled'),
        );

        // This is an idempotent toggle (PUT), not a "create a new resource"
        // action — force 200 even the first time a school's row for this
        // module is actually inserted, rather than letting the fresh-model
        // 201 behavior kick in (same gotcha noted elsewhere in this codebase).
        return (new ModuleSettingResource($setting))->response()->setStatusCode(200);
    }
}
