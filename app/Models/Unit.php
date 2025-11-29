<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'unit_code',
        'status',
        'base_price',
        'area_sqm',
        'contract_file',
        'other_files',
        'customer_id',
    ];


    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
   public function installments()
{
    return $this->hasMany(Installment::class);
}

public function booking()
{
    return $this->hasOne(\App\Models\Booking::class)->latest();
}
public function documents()
{
    return $this->morphMany(Document::class, 'documentable');
}




}
