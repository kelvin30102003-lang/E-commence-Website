<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Slips | Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Payment Slip Reviews</h1>
        <p class="text-slate-600">Review manual wallet and bank transfers.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif

    <form method="get" class="mb-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <select name="status" class="rounded-lg border-slate-300">
            <option value="">All statuses</option>
            @foreach (['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="payment_method_id" class="rounded-lg border-slate-300">
            <option value="">All methods</option>
            @foreach ($paymentMethods as $method)
                <option value="{{ $method->id }}" @selected((string) ($filters['payment_method_id'] ?? '') === (string) $method->id)>{{ $method->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="rounded-lg border-slate-300">
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Filter</button>
    </form>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Method</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Uploaded</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($paymentSlips as $slip)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $slip->order?->order_number ?? ('#'.$slip->order_id) }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $slip->user?->name ?? 'Customer' }}</div>
                            <div class="text-xs text-slate-500">{{ $slip->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $slip->paymentMethod?->name }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $slip->amount, 2) }} MMK</td>
                        <td class="px-4 py-3">@include('partials.payment-status-badge', ['status' => $slip->status])</td>
                        <td class="px-4 py-3">{{ $slip->created_at?->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.payment-slips.show', $slip) }}" class="font-semibold text-[#78555e] hover:underline">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No payment slips found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">
            {{ $paymentSlips->links() }}
        </div>
    </section>
</main>
</body>
</html>
