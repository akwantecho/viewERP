<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Booking extends Model
{
    protected $fillable = [
        'unit_id',
        'customer_id',
        'project_id',
        'reservation_date',
        'contract_file',
        'status',
        'unit_price',
        'agreed_base',
        'agreed_vat',
        'agreed_price',
        'vat',
        'total_price',
        'advance_payment',
        'advance_amount',
        'remaining_amount',
        'installments_count',
        'installment_frequency',
        'monthly_due_day',
        'plan_type',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    // علاقات
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function unit()       { return $this->belongsTo(Unit::class); }
    public function project()    { return $this->belongsTo(Project::class); }
    public function installments(){ return $this->hasMany(Installment::class); }
    public function documents()  { return $this->morphMany(Document::class, 'documentable'); }

    // جميع المدفوعات المرتبطة بالحجز (عربون + أقساط)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // 🧠 إجمالي المبلغ المدفوع = مجموع payments.amount
    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    // 🧠 المتبقي على إجمالي الصفقة
    public function getRemainingAttribute(): float
    {
        return round(max(0, (float) $this->total_price - $this->total_paid), 2);
    }

    // 🧠 نسبة السداد %
    public function getPaymentProgressAttribute(): float
    {
        if ((float) $this->total_price <= 0) return 0.0;
        return round(($this->total_paid / (float) $this->total_price) * 100, 1);
    }

    // 🖼️ رابط ملف العقد (يدعم S3 إن كنت مستخدمه)
    public function getContractUrlAttribute(): ?string
    {
        if (!$this->contract_file) return null;

        if (Str::startsWith($this->contract_file, ['http://', 'https://'])) {
            return $this->contract_file;
        }

        // لو عقودك مرفوعة على s3
        if (config('filesystems.disks.s3')) {
            return Storage::disk('s3')->url($this->contract_file);
        }

        // التخزين المحلي (public)
        return Storage::disk('public')->url($this->contract_file);
    }

    // 🔍 حجوزات مؤكدة
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
