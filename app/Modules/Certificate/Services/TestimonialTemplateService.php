<?php

namespace App\Modules\Certificate\Services;

use App\Modules\Certificate\Repositories\TestimonialTemplateRepository;
use App\Services\BaseService;

class TestimonialTemplateService extends BaseService
{
    public function __construct(TestimonialTemplateRepository $repository)
    {
        parent::__construct($repository);
    }
}
