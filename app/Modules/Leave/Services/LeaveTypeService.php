<?php

namespace App\Modules\Leave\Services;

use App\Modules\Leave\Repositories\LeaveTypeRepository;
use App\Services\BaseService;

class LeaveTypeService extends BaseService
{
    public function __construct(LeaveTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
