<?php

return [
    'index' => [
        'title' => 'الدفعات',
        'subtitle' => ':project · :unit · :customer',
        'buttons' => [
            'installments' => '← الأقساط',
            'plan' => '⚙️ عرض خطة الأقساط',
            'new' => '➕ دفعة جديدة',
            'print_all' => '🖨️ جميع الدفعات',
            'delete_booking' => '🗑️ حذف الحجز',
        ],
        'confirm_booking' => [
            'title' => 'حذف الحجز',
            'message' => 'سيتم حذف الحجز وكل الدفعات والأقساط المرتبطة به بشكل نهائي. هل تريد المتابعة؟',
        ],
        'alerts' => [
            'total' => 'القيمة الإجمالية',
            'paid' => 'المدفوع',
            'remaining' => 'المتبقي',
        ],
        'table' => [
            'headers' => [
                'index' => '#',
                'invoice' => 'رقم الفاتورة',
                'type' => 'النوع',
                'amount' => 'المبلغ',
                'method' => 'طريقة الدفع',
                'reference' => 'المرجع',
                'paid_at' => 'تاريخ السداد',
                'receipt' => 'الإيصال',
                'actions' => 'الإجراءات',
            ],
            'receipt_view' => '📎 عرض',
            'empty' => 'لا توجد دفعات مسجلة بعد.',
        ],
        'actions' => [
            'view' => 'عرض',
            'invoice' => 'فتح الفاتورة',
            'print' => 'ملف PDF',
            'edit' => 'تعديل',
            'delete' => 'حذف',
        ],
        'confirm_payment' => [
            'title' => 'حذف الدفعة',
            'message' => 'هل أنت متأكد من حذف هذه الدفعة؟ هذا الإجراء لا يمكن التراجع عنه.',
        ],
        'type_label' => [
            'installment' => 'القسط رقم :number',
            'advance' => 'دفعة مقدمة / عامة',
        ],
        'misc' => [
            'currency' => 'ر.ع',
            'no_bank' => 'بدون بنك',
        ],
    ],
];
