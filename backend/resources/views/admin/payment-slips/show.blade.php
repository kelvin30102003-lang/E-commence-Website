<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Payment Slip #{{ $paymentSlip->id }} | Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.payment-slips.index') }}" class="text-sm font-semibold text-[#78555e] hover:underline">Back to list</a>
            <h1 class="mt-2 text-3xl font-bold">Payment Slip #{{ $paymentSlip->id }}</h1>
        </div>
        @include('partials.payment-status-badge', ['status' => $paymentSlip->status])
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_420px]">
        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold">Slip Image</h2>
            <a href="{{ asset('storage/'.$paymentSlip->slip_image) }}" target="_blank" rel="noopener">
                <img src="{{ asset('storage/'.$paymentSlip->slip_image) }}" alt="Payment slip" class="max-h-[760px] w-full rounded-lg object-contain">
            </a>
        </section>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-bold">Payment Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Order</dt><dd class="font-semibold">{{ $paymentSlip->order?->order_number ?? ('#'.$paymentSlip->order_id) }}</dd></div>
                    <div><dt class="text-slate-500">Customer</dt><dd class="font-semibold">{{ $paymentSlip->user?->name ?? 'Customer' }}</dd><dd class="text-slate-600">{{ $paymentSlip->user?->email }}</dd></div>
                    <div><dt class="text-slate-500">Order total</dt><dd class="font-semibold">{{ number_format((float) $paymentSlip->order?->total_amount, 2) }} MMK</dd></div>
                    <div><dt class="text-slate-500">Uploaded amount</dt><dd class="font-semibold">{{ number_format((float) $paymentSlip->amount, 2) }} MMK</dd></div>
                    <div><dt class="text-slate-500">Payment method</dt><dd class="font-semibold">{{ $paymentSlip->paymentMethod?->name }}</dd></div>
                    <div><dt class="text-slate-500">Transaction ID</dt><dd class="font-semibold">{{ $paymentSlip->transaction_id ?: '-' }}</dd></div>
                    <div><dt class="text-slate-500">Transferred at</dt><dd class="font-semibold">{{ $paymentSlip->transferred_at?->format('M d, Y H:i') ?: '-' }}</dd></div>
                    <div><dt class="text-slate-500">Sender</dt><dd class="font-semibold">{{ $paymentSlip->sender_name ?: '-' }}</dd><dd class="text-slate-600">{{ $paymentSlip->sender_phone }}</dd></div>
                </dl>
            </section>

            @if ($paymentSlip->status === 'pending')
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold">Approve</h2>
                    <form action="{{ route('admin.payment-slips.approve', $paymentSlip) }}" method="post" class="space-y-3">
                        @csrf
                        <textarea name="admin_note" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Admin note (optional)">{{ old('admin_note') }}</textarea>
                        <button class="w-full rounded-lg bg-green-600 px-4 py-3 font-bold text-white hover:bg-green-700">Approve Payment</button>
                    </form>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold">Reject</h2>
                    <form action="{{ route('admin.payment-slips.reject', $paymentSlip) }}" method="post" class="space-y-3">
                        @csrf
                        <textarea name="reject_reason" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Reject reason" required>{{ old('reject_reason') }}</textarea>
                        <textarea name="admin_note" rows="2" class="w-full rounded-lg border-slate-300" placeholder="Admin note (optional)">{{ old('admin_note') }}</textarea>
                        <button class="w-full rounded-lg bg-red-600 px-4 py-3 font-bold text-white hover:bg-red-700">Reject Slip</button>
                    </form>
                </section>
            @else
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold">Review Result</h2>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-slate-500">Reviewed by</dt><dd class="font-semibold">{{ $paymentSlip->reviewer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Reviewed at</dt><dd class="font-semibold">{{ $paymentSlip->reviewed_at?->format('M d, Y H:i') ?: '-' }}</dd></div>
                        @if ($paymentSlip->reject_reason)
                            <div><dt class="text-slate-500">Reject reason</dt><dd class="font-semibold text-red-700">{{ $paymentSlip->reject_reason }}</dd></div>
                        @endif
                        @if ($paymentSlip->admin_note)
                            <div><dt class="text-slate-500">Admin note</dt><dd>{{ $paymentSlip->admin_note }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</main>
</body>
</html>
