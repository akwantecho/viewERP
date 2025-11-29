@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-[#1f2937]">Activities</h2>
        <span class="text-sm text-gray-500">Super Admin</span>
    </div>

    @if(session('warning'))
        <div class="p-3 rounded border border-yellow-300 bg-yellow-50 text-yellow-900">{{ session('warning') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm border">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border px-3 py-2 text-left">Time</th>
                    <th class="border px-3 py-2 text-left">User</th>
                    <th class="border px-3 py-2 text-left">Action</th>
                    <th class="border px-3 py-2 text-left">Details</th>
                    <th class="border px-3 py-2 text-left">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($paginator ?? collect()) as $a)
                    @php
                        // Friendly action labels (EN)
                        $map = [
                            'auth.login'       => 'Logged In',
                            'auth.logout'      => 'Logged Out',
                            'payment.create'   => 'Created Payment',
                            'payment.update'   => 'Updated Payment',
                            'payment.delete'   => 'Deleted Payment',
                            'booking.create'   => 'Created Booking',
                            'booking.delete'   => 'Deleted Booking',
                            'project.create'   => 'Created Project',
                            'project.update'   => 'Updated Project',
                        ];
                        $actionText = $map[$a->action] ?? $a->action;

                        // Build concise details
                        $details = '';
                        $type = $a->entity_type ? class_basename($a->entity_type) : null;
                        $meta = is_array($a->meta) ? $a->meta : [];

                        if (str_starts_with($a->action, 'payment.')) {
                            $details = 'Payment #'.($a->entity_id ?? '—');
                            if (isset($meta['amount'])) $details .= ' — Amount: '.number_format((float)$meta['amount'], 2).' OMR';
                            if (!empty($meta['method'])) $details .= ' — Method: '.$meta['method'];
                            if (!empty($meta['booking_id'])) $details .= ' — Booking: #'.$meta['booking_id'];
                        } elseif ($a->action === 'booking.create') {
                            $details = 'Booking #'.($a->entity_id ?? '—');
                            if (!empty($meta['unit_id'])) $details .= ' — Unit: #'.$meta['unit_id'];
                            if (isset($meta['total_price'])) $details .= ' — Total: '.number_format((float)$meta['total_price'], 2).' OMR';
                            if (isset($meta['advance'])) $details .= ' — Advance: '.number_format((float)$meta['advance'], 2).' OMR';
                        } elseif ($a->action === 'booking.delete') {
                            $details = 'Deleted Booking #'.($a->entity_id ?? '—');
                        } elseif ($a->action === 'project.create') {
                            $details = 'Project #'.($a->entity_id ?? '—');
                            if (isset($meta['floors'])) $details .= ' — Floors: '.$meta['floors'];
                        } elseif ($a->action === 'project.update') {
                            $details = 'Updated Project #'.($a->entity_id ?? '—');
                        } elseif ($a->action === 'auth.login' || $a->action === 'auth.logout') {
                            $details = $actionText;
                            if (!empty($meta['email'])) $details .= ' — '.$meta['email'];
                        } else {
                            $details = ($type ? ($type.' #'.($a->entity_id ?? '—')) : '—');
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2 whitespace-nowrap">{{ $a->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="border px-3 py-2">{{ $a->user?->name ?? '—' }}</td>
                        <td class="border px-3 py-2">{{ $actionText }}</td>
                        <td class="border px-3 py-2">{{ $details }}</td>
                        <td class="border px-3 py-2">{{ $a->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="border px-3 py-6 text-center text-gray-500">No activities yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ ($paginator ?? collect())->links() }}
    </div>
</div>
@endsection
