<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order): View
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        $order->load(['paymentSlips.paymentMethod', 'payments']);

        return view('orders.show', [
            'order' => $order,
        ]);
    }
}
