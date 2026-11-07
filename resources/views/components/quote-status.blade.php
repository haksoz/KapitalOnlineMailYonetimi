@props(['status'])
<span @class([
    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap',
    'border-blue-200 bg-blue-100 text-blue-800' => $status === 'sent',
    'border-green-200 bg-green-100 text-green-800' => $status === 'approved',
    'border-violet-200 bg-violet-100 text-violet-800' => $status === 'partially_approved',
    'border-red-200 bg-red-100 text-red-800' => $status === 'rejected',
    'border-amber-200 bg-amber-100 text-amber-800' => $status === 'expired',
    'border-indigo-200 bg-indigo-100 text-indigo-800' => $status === 'converted',
    'border-gray-200 bg-gray-100 text-gray-700' => in_array($status, ['draft', 'cancelled']),
])>{{ \App\Models\Quote::statusLabel($status) }}</span>
