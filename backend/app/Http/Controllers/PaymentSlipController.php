<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentSlip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSlipController extends Controller
{
    public function create(Order $order): View
    {
        $this->authorizeCustomerOrder($order);
        $this->ensureOrderCanReceiveSlip($order);

        $paymentMethods = PaymentMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $latestSlip = $order->paymentSlips()
            ->with('paymentMethod')
            ->latest()
            ->first();

        return view('orders.payment', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
            'latestSlip' => $latestSlip,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeCustomerOrder($order);
        $this->ensureOrderCanReceiveSlip($order);

        abort_if(
            $order->paymentSlips()->where('status', PaymentSlip::STATUS_PENDING)->exists(),
            422,
            'A payment slip is already waiting for admin review.'
        );

        $validated = $request->validate([
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'transferred_at' => ['nullable', 'date'],
            'slip_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('slip_image')->store('payment-slips', 'public');

        DB::transaction(function () use ($order, $validated, $path): void {
            PaymentSlip::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'payment_method_id' => $validated['payment_method_id'],
                'slip_image' => $path,
                'amount' => $validated['amount'],
                'sender_name' => $validated['sender_name'] ?? null,
                'sender_phone' => $validated['sender_phone'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'transferred_at' => $validated['transferred_at'] ?? null,
                'status' => PaymentSlip::STATUS_PENDING,
            ]);

            $order->forceFill([
                'payment_status' => Order::PAYMENT_PENDING_REVIEW,
                'order_status' => 'pending',
            ])->save();
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Payment slip uploaded. Please wait for admin review.');
    }

    private function authorizeCustomerOrder(Order $order): void
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);
    }

    private function ensureOrderCanReceiveSlip(Order $order): void
    {
        abort_if($order->payment_status === Order::PAYMENT_PAID, 422, 'This order has already been paid.');
    }
}
