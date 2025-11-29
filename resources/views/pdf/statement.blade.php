<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Financial Statement - {{ $project->name }} ({{ $project->code }})</title>
    <style>
        /* حجم وهوامش الورقة */
        @page {
            size: A4;
            margin: 24mm 18mm 20mm; /* أعلى/يمين/أسفل/يسار */
        }

        body {
            margin: 0; /* الهوامش من @page فقط */
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;  /* أصغر قليلًا لزيادة قابلية الاحتواء */
            color: #222;
        }

        /* الخلفية تغطي كامل A4 */
        .letterhead-bg {
            position: fixed;
            top: 0; left: 0;
            width: 210mm; height: 297mm;
            z-index: -1;
        }

        h1, h2 { margin: 0 0 6px; }
        .muted { color: #666; font-size: 10.5px; }

        /* جدول مدمج ومحكوم العرض */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;        /* مهم لاحترام عرض الأعمدة */
            word-wrap: break-word;      /* كسر الكلمات الطويلة داخل الخلايا */
        }

        /* أعمدة بعرض محدد بالـ mm لتناسب عرض محتوى A4 (174mm داخل الهوامش) */
        colgroup col.col-code   { width: 20mm; }
        colgroup col.col-floor  { width: 18mm; }
        colgroup col.col-unit   { width: 22mm; }
        colgroup col.col-paid   { width: 26mm; }
        colgroup col.col-price  { width: 26mm; }
        colgroup col.col-prog   { width: 16mm; }
        colgroup col.col-status { width: 20mm; }
        /* المجموع = 148mm تقريبًا + حدود وحشوات مريحة ضمن 174mm */

        thead th {
            background: #f5f5f5;
            border: 1px solid #999;
            padding: 5px;               /* حشوة أقل */
            text-align: center;
            font-weight: 700;
        }

        tbody td, tfoot td {
            border: 1px solid #999;
            padding: 5px;               /* حشوة أقل */
            text-align: center;
        }

        /* تكرار الهيدر والفوتر على كل صفحة */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        /* صفوف مخططة لسهولة القراءة */
        tbody tr:nth-child(odd)  { background: #fcfcfc; }
        tbody tr:nth-child(even) { background: #ffffff; }

        /* محاذاة الأرقام يمينًا لتكون مقروءة في التقارير */
        .num { text-align: right; }
        .green { color: #0a7a44; font-weight: 700; }
        .right { text-align: right; }

        /* منع تقطيع الصف عبر الصفحات قدر الإمكان */
        tr { page-break-inside: avoid; }

        /* تذييل الصفحة بأرقام الصفحات (اختياري) */
        .footer {
            position: fixed;
            bottom: 8mm; left: 0; right: 0;
            text-align: center;
            font-size: 10.5px;
            color: #666;
        }
        .pagenum:before { content: counter(page); }
        .pagetotal:before { content: counter(pages); }
    </style>
</head>
<body>
    {{-- خلفية الورقة الرسمية --}}

    {{-- العنوان --}}
    <h1>Financial Statement</h1>
    <h2>{{ $project->name }} ({{ $project->code }})</h2>
    <p class="muted">Generated at: {{ now()->format('Y-m-d H:i') }}</p>

    @php
        $totalPaid = 0;
        $totalPrice = 0;
    @endphp

    <table>
        <colgroup>
            <col class="col-code">
            <col class="col-floor">
            <col class="col-unit">
            <col class="col-paid">
            <col class="col-price">
            <col class="col-prog">
            <col class="col-status">
        </colgroup>

        <thead>
            <tr>
                <th>Project Code</th>
                <th>Floor</th>
                <th>Unit</th>
                <th>Paid Amount (OMR)</th>
                <th>Sale Price (OMR)</th>
                <th>Progress %</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        @foreach ($units as $unit)
            @php
                // Sum all payments recorded for this unit's booking (advance + installments)
                $paid  = optional(optional($unit->booking)->payments)->sum('amount') ?? 0;
                $price = optional($unit->booking)->total_price ?? 0;
                $prog  = $price > 0 ? round(($paid / $price) * 100, 2) : 0;

                $totalPaid  += $paid;
                $totalPrice += $price;
            @endphp
            <tr>
                <td>{{ optional($unit->floor->project)->code ?? '-' }}</td>
                <td>{{ optional($unit->floor)->name ?? '-' }}</td>
                <td>{{ $unit->unit_code }}</td>
                <td class="num green">{{ number_format($paid, 2) }}</td>
                <td class="num">{{ number_format($price, 2) }}</td>
                <td class="num">{{ $prog }}%</td>
                <td>{{ ucfirst($unit->status ?? '-') }}</td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td class="right" colspan="3">Total</td>
                <td class="num green">{{ number_format($totalPaid, 2) }}</td>
                <td class="num">{{ number_format($totalPrice, 2) }}</td>
                <td class="num">{{ $totalPrice > 0 ? round(($totalPaid / $totalPrice) * 100, 2) : 0 }}%</td>
                <td>—</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:8px;">
        Notes: This statement summarizes paid amounts vs. total sale prices for all units under the project.
    </p>

    {{-- تذييل الصفحة --}}
    <div class="footer">
        Page <span class="pagenum"></span> of <span class="pagetotal"></span>
    </div>
</body>
</html>
