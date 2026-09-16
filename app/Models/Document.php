<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'penjoki_id',
        'original_filename',
        'original_path',
        'result_filename',
        'result_path',
        'customer_note',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penjoki(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjoki_id');
    }
}
