<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tonysm\RichTextLaravel\Models\Traits\HasRichText;

class CustomerNote extends Model
{
    use HasFactory, HasRichText;

    protected $fillable = [
        'customer_id',
        'user_id',
        'title',
        'html', // Keep for backward compatibility
        'text',
        'tags',
        'color',
        'is_pinned',
        'visibility',
        'pinned_at',
    ];

    protected $richTextAttributes = [
        'content', // New rich text field
    ];

    protected $casts = [
        'tags' => 'array',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Clean up rich text content when note is deleted
        static::deleting(function (CustomerNote $note) {
            // The HasRichText trait should handle this, but ensure it's cleaned up
            if ($note->content) {
                $note->content()->delete();
            }
        });
    }

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

