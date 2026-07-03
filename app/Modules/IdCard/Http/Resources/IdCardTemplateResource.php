<?php

namespace App\Modules\IdCard\Http\Resources;

use App\Modules\IdCard\Models\IdCardTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin IdCardTemplate */
class IdCardTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'layout' => $this->layout,
            'background_color' => $this->background_color,
            'accent_color' => $this->accent_color,
            'logo_url' => $this->logo_path
                ? Storage::disk('minio')->temporaryUrl($this->logo_path, now()->addMinutes(30))
                : null,
            'font' => $this->font,
            'visible_fields' => $this->visible_fields ?? [],
            'is_default' => $this->is_default,
        ];
    }
}
