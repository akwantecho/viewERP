<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Installment extends Model
{
    use HasFactory;

      protected $fillable = [
        'booking_id',
        'installment_number',
        'due_date',
        'amount',
        'vat',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // 🔗 العلاقة مع الحجز
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

     public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // 🧠 Accessor: إجمالي المبلغ المدفوع مع الضريبة
    public function getPaidWithVatAttribute()
    {
        return round($this->paid + $this->vat, 2);
    }

    // 📊 Accessor: المبلغ المتبقي لهذا القسط
    public function getOutstandingAmountAttribute()
    {
        return round($this->total_amount - $this->paid, 2);
    }

    // ✅ هل القسط مدفوع؟
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    // 🔍 Scope: أقساط غير مدفوعة
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    // 🔍 Scope: المستحقة قبل تاريخ معين
    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    // 🖼️ رابط الإيصال
    public function getReceiptUrlAttribute()
    {
        return $this->receipt ? asset('storage/' . $this->receipt) : null;
    }
}
