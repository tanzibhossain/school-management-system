<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\TransferCertificateTemplate */
class TransferCertificateTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'template_body'         => $this->template_body,
            'footer_text'           => $this->footer_text,
            'signatory_name'        => $this->signatory_name,
            'signatory_designation' => $this->signatory_designation,
            'is_default'            => $this->is_default,
            'updated_at'            => $this->updated_at,
        ];
    }
}
