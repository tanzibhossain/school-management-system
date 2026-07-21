<?php

namespace App\Modules\Library\Http\Resources;

use App\Modules\Library\Models\BorrowRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BorrowRecord */
class BorrowRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'library_member_id' => $this->library_member_id,
            'book_id' => $this->book_id,
            'borrowed_at' => $this->borrowed_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'status' => $this->status,
            'notes' => $this->notes,
            'book' => $this->whenLoaded('book', fn () => [
                'id' => $this->book->id,
                'title' => $this->book->title,
            ]),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'member_type' => $this->member->member_type,
                'user' => [
                    'id' => $this->member->user->id,
                    'name' => $this->member->user->name,
                ],
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
