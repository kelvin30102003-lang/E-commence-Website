@php
    $activeMethod = old('payment_method_id', $paymentMethods->first()?->id);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay Order {{ $order->order_number ?? ('#'.$order->id) }} | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-[#fbf9f8] text-slate-900">
<main class="mx-auto max-w-6xl px-4 py-8 md:py-12">
    <div class="mb-6">
        <a href="{{ route('orders.show', $order) }}" class="text-sm font-semibold text-[#78555e] hover:underline">Back to order</a>
        <h1 class="mt-3 text-3xl font-bold text-[#78555e]">Manual QR Payment</h1>
        <p class="text-slate-600">Transfer manually, then upload your payment slip. Your order will wait for admin review.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($latestSlip)
        <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
            Latest slip status: <strong>{{ str_replace('_', ' ', ucfirst($latestSlip->status)) }}</strong>
            @if ($latestSlip->reject_reason)
                <div class="mt-1">Reject reason: {{ $latestSlip->reject_reason }}</div>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Order</div>
                    <div class="font-bold">{{ $order->order_number ?? ('#'.$order->id) }}</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Total</div>
                    <div class="font-bold">{{ number_format((float) $order->total_amount, 2) }} MMK</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Status</div>
                    <div class="font-bold">{{ str_replace('_', ' ', $order->payment_status ?? 'unpaid') }}</div>
                </div>
            </div>

            <h2 class="mb-3 text-lg font-bold">Choose Payment Method</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($paymentMethods as $method)
                    <label class="payment-method-card cursor-pointer rounded-lg border border-slate-200 p-4 transition hover:border-[#78555e]" data-method-card="{{ $method->id }}">
                        <input type="radio" name="method_preview" value="{{ $method->id }}" class="sr-only" @checked((int) $activeMethod === $method->id)>
                        <div class="font-bold">{{ $method->name }}</div>
                        <div class="text-sm text-slate-600">{{ $method->account_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $method->account_phone ?: $method->account_number }}</div>
                    </label>
                @endforeach
            </div>

            @foreach ($paymentMethods as $method)
                <div class="method-detail mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4" data-method-detail="{{ $method->id }}" @if ((int) $activeMethod !== $method->id) hidden @endif>
                    <div class="grid gap-4 sm:grid-cols-[180px_1fr]">
                        <div class="rounded-lg bg-white p-3">
                            @if ($method->qr_image)
                                <img src="{{ asset('storage/'.$method->qr_image) }}" alt="{{ $method->name }} QR" class="aspect-square w-full rounded-md object-contain">
                            @else
                                <div class="flex aspect-square items-center justify-center rounded-md border border-dashed border-slate-300 text-center text-sm text-slate-500">QR image not uploaded</div>
                            @endif
                        </div>
                        <div class="space-y-2 text-sm">
                            <div><span class="font-semibold">Account name:</span> {{ $method->account_name }}</div>
                            @if ($method->account_phone)
                                <div><span class="font-semibold">Phone:</span> {{ $method->account_phone }}</div>
                            @endif
                            @if ($method->account_number)
                                <div><span class="font-semibold">Account number:</span> {{ $method->account_number }}</div>
                            @endif
                            @if ($method->instructions)
                                <div class="rounded-md bg-white p-3 text-slate-700">{{ $method->instructions }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-bold">Upload Payment Slip</h2>
            <form action="{{ route('orders.payment-slips.store', $order) }}" method="post" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="payment_method_id">Payment method</label>
                    <select id="payment_method_id" name="payment_method_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->id }}" @selected((int) old('payment_method_id', $activeMethod) === $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="amount">Transferred amount</label>
                    <input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount', $order->total_amount) }}" class="w-full rounded-lg border-slate-300" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold" for="sender_name">Sender name</label>
                        <input id="sender_name" name="sender_name" value="{{ old('sender_name') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold" for="sender_phone">Sender phone</label>
                        <input id="sender_phone" name="sender_phone" value="{{ old('sender_phone') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="transaction_id">Transaction ID</label>
                    <input id="transaction_id" name="transaction_id" value="{{ old('transaction_id') }}" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="transferred_at">Transferred at</label>
                    <input id="transferred_at" name="transferred_at" type="datetime-local" value="{{ old('transferred_at') }}" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="slip_image">Slip image</label>
                    <input id="slip_image" name="slip_image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-300 p-2" required>
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WebP. Max 4MB.</p>
                </div>
                <button class="w-full rounded-full bg-[#78555e] px-5 py-3 font-bold text-white hover:opacity-90">Submit for Review</button>
            </form>
        </section>
    </div>
</main>

<script>
    document.querySelectorAll('[data-method-card]').forEach((card) => {
        card.addEventListener('click', () => {
            const id = card.dataset.methodCard;
            document.querySelectorAll('[data-method-card]').forEach((item) => item.classList.remove('border-[#78555e]', 'bg-[#fbf9f8]'));
            card.classList.add('border-[#78555e]', 'bg-[#fbf9f8]');
            document.querySelectorAll('[data-method-detail]').forEach((detail) => detail.hidden = detail.dataset.methodDetail !== id);
            const select = document.getElementById('payment_method_id');
            if (select) select.value = id;
        });
    });
</script>
</body>
</html>
