<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InstallmentNotificationController extends Controller
{
    public function index(Request $request)
    {
        $year      = (int)($request->input('year', now()->year));
        $month     = (int)($request->input('month', now()->month));
        $todayOnly = $request->boolean('today_only', false);

        $base = Installment::query()
            ->with([
                'booking.customer:id,name',
                'booking.unit:id,unit_code,floor_id',
                'booking.unit.floor.project:id,name'
            ])
            ->whereIn('status', ['unpaid','partial']);

        if ($todayOnly) {
            $base->whereRaw('DATE(DATE_SUB(due_date, INTERVAL 3 DAY)) = ?', [now()->toDateString()]);
        } else {
            $base->whereRaw('YEAR(DATE_SUB(due_date, INTERVAL 3 DAY)) = ?', [$year])
                 ->whereRaw('MONTH(DATE_SUB(due_date, INTERVAL 3 DAY)) = ?', [$month]);
        }

        $installments = $base->orderBy('due_date')->get()->map(function ($inst) {
            $amount = round($inst->amount ?? 0, 2);
            $vat    = round($inst->vat ?? 0, 2);
            $total  = $amount + $vat;
            $paid   = round($inst->paid ?? 0, 2);

            $inst->total          = round($total, 2);
            $inst->notify_date    = Carbon::parse($inst->due_date)->subDays(3);
            $inst->days_to_due    = now()->diffInDays(Carbon::parse($inst->due_date), false);
            $inst->remaining_this = max(0, round($total - $paid, 2));

            return $inst;
        });

        // انت قلت تريد الملف هنا:
        return view('notifications.index', compact('installments','year','month','todayOnly'));
    }
}
