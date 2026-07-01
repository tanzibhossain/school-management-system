<?php

namespace App\Modules\User\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'user_id',
        'email',
        'ip_address',
        'device_name',
        'user_agent',
        'status',
        'failed_reason',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at'  => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    /** @return BelongsTo<User, LoginHistory> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
