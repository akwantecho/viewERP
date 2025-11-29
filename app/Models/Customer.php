<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

   protected $fillable = [
    'name',
    'phone',
    'email',
    'civil_number',
    'coming_from',
    'id_type',
    'id_file',
];
public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * للحصول على جميع الوحدات المحجوزة من قبل هذا العميل
     */
    public function units()
    {
        return $this->hasManyThrough(Unit::class, Booking::class, 'customer_id', 'id', 'id', 'unit_id');
    }

    /**
     * للحصول على رابط هوية العميل
     */
    public function getIdFileUrlAttribute()
    {
        return $this->id_file ? asset('storage/' . $this->id_file) : null;
    }
    public function documents()
{
    return $this->morphMany(Document::class, 'documentable');
}

    public function notes()
    {
        return $this->hasMany(CustomerNote::class);
    }

}
