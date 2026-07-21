<?php

namespace App\Modules\Library\Http\Resources;

use App\Modules\Library\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'category' => $this->category,
            'publisher' => $this->publisher,
            'edition' => $this->edition,
            'published_at' => $this->published_at?->toDateString(),
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
