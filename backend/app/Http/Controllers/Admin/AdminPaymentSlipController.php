<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentSlip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPaymentSlipController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'date' => ['nullable', 'date'],
        ]);

        $paymentSlips = PaymentSlip::query()
            ->with(['order', 'user', 'paymentMethod'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['payment_method_id'] ?? null, fn ($query, $id) => $query->where('payment_method_id', $id))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payment-slips.index', [
            'paymentSlips' => $paymentSlips,
            'paymentMethods' => PaymentMethod::orderBy('sort_order')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(PaymentSlip $paymentSlip): View
    {
        $paymentSlip->load(['order', 'user', 'paymentMethod', 'reviewer']);

        return view('admin.payment-slips.show', [
            'paymentSlip' => $paymentSlip,
        ]);
    }

    public function approve(Request $request, PaymentSlip $paymentSlip): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($paymentSlip, $validated): void {
            $slip = PaymentSlip::query()
                ->with('paymentMethod')
                ->lockForUpdate()
                ->findOrFail($paymentSlip->id);

            abort_unless($slip->isPending(), 409, 'This payment slip has already been reviewed.');

            $order = Order::query()->lockForUpdate()->findOrFail($slip->order_id);

            $slip->forceFill([
                'status' => PaymentSlip::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
            ])->save();

            $order->forceFill([
                'payment_status' => Order::PAYMENT_PAID,
                'order_status' => 'confirmed',
            ])->save();

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $slip->paymentMethod->code,
                'payment_provider' => $slip->paymentMethod->name,
                'transaction_id' => $slip->transaction_id,
                'amount' => $slip->amount,
                'currency' => 'MMK',
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.payment-slips.show', $paymentSlip)
            ->with('success', 'Payment approved and order marked as paid.');
    }

    public function reject(Request $request, PaymentSlip $paymentSlip): RedirectResponse
    {
        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($paymentSlip, $validated): void {
            $slip = PaymentSlip::query()
                ->lockForUpdate()
                ->findOrFail($paymentSlip->id);

            abort_unless($slip->isPending(), 409, 'This payment slip has already been reviewed.');

            $order = Order::query()->lockForUpdate()->findOrFail($slip->order_id);

            $slip->forceFill([
                'status' => PaymentSlip::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
                'reject_reason' => $validated['reject_reason'],
            ])->save();

            $order->forceFill([
                'payment_status' => Order::PAYMENT_REJECTED,
                'order_status' => 'pending',
            ])->save();
        });

        return redirect()
            ->route('admin.payment-slips.show', $paymentSlip)
            ->with('success', 'Payment slip rejected.');
    }
}
