<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNoteFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_note_id',
        'user_id',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'size',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(CustomerNote::class, 'customer_note_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

