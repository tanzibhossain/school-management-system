<?php

namespace App\Modules\IdCard\Services;

use App\Modules\IdCard\Repositories\IdCardTemplateRepository;
use App\Services\BaseService;

class IdCardTemplateService extends BaseService
{
    public function __construct(IdCardTemplateRepository $repository)
    {
        parent::__construct($repository);
    }
}
