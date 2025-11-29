<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'invoice_number',
        'installment_id', // null للعربون/دفعات عامة
        'amount',
        'payment_method',
        'bank_name',
        'reference_no',
        'receipt',
        'paid_at',
    ];

    protected $casts = [
        'paid_at'        => 'datetime',
        'booking_id'     => 'integer',
        'installment_id' => 'integer',
        'amount'         => 'float',
    ];

    public function booking()     { return $this->belongsTo(Booking::class); }
    public function installment() { return $this->belongsTo(Installment::class); }
}
