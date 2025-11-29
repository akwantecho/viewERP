<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNoteRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_note_id',
        'customer_id',
        'user_id',
        'title',
        'html',
        'text',
        'tags',
        'color',
        'visibility',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(CustomerNote::class, 'customer_note_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

