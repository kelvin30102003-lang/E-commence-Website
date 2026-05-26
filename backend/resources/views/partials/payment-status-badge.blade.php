@php
    $badgeClass = match ($status) {
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-yellow-100 text-yellow-800',
    };
@endphp
<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $badgeClass }}">
    {{ $status }}
</span>
