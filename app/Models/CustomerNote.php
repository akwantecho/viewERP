<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'title',
        'html',
        'text',
        'tags',
        'color',
        'is_pinned',
        'visibility',
        'pinned_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CustomerNoteRevision::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(CustomerNoteFile::class);
    }
}

