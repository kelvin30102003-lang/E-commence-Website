<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order {{ $order->order_number ?? ('#'.$order->id) }} | LuvShop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fbf9f8] text-slate-900">
<main class="mx-auto max-w-4xl px-4 py-8">
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#78555e]">Order {{ $order->order_number ?? ('#'.$order->id) }}</h1>
                <p class="text-slate-600">Total: {{ number_format((float) $order->total_amount, 2) }} MMK</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold">{{ str_replace('_', ' ', $order->order_status ?? 'pending') }}</span>
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-semibold text-yellow-800">{{ str_replace('_', ' ', $order->payment_status ?? 'unpaid') }}</span>
            </div>
        </div>

        @if ($order->payment_status !== \App\Models\Order::PAYMENT_PAID)
            <a href="{{ route('orders.payment.create', $order) }}" class="mt-6 inline-flex rounded-full bg-[#78555e] px-5 py-3 font-bold text-white hover:opacity-90">Pay / Upload Slip</a>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold">Payment Slips</h2>
        <div class="space-y-3">
            @forelse ($order->paymentSlips as $slip)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-semibold">{{ $slip->paymentMethod?->name }} - {{ number_format((float) $slip->amount, 2) }} MMK</div>
                            <div class="text-sm text-slate-500">Uploaded {{ $slip->created_at?->format('M d, Y H:i') }}</div>
                        </div>
                        @include('partials.payment-status-badge', ['status' => $slip->status])
                    </div>
                    @if ($slip->reject_reason)
                        <p class="mt-2 text-sm text-red-700">Reject reason: {{ $slip->reject_reason }}</p>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No payment slips uploaded yet.</p>
            @endforelse
        </div>
    </section>
</main>
</body>
</html>
