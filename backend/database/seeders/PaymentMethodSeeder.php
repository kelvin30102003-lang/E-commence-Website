<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'KBZPay',
                'code' => 'kbzpay',
                'account_name' => 'LuvShop Myanmar',
                'account_phone' => '09 400 000 001',
                'account_number' => null,
                'qr_image' => 'payment-qr/kbzpay.png',
                'instructions' => 'Transfer the exact order total to this KBZPay account and upload the slip.',
                'sort_order' => 10,
            ],
            [
                'name' => 'WavePay',
                'code' => 'wavepay',
                'account_name' => 'LuvShop Myanmar',
                'account_phone' => '09 400 000 002',
                'account_number' => null,
                'qr_image' => 'payment-qr/wavepay.png',
                'instructions' => 'Transfer the exact order total to this WavePay account and upload the slip.',
                'sort_order' => 20,
            ],
            [
                'name' => 'AYA Pay',
                'code' => 'ayapay',
                'account_name' => 'LuvShop Myanmar',
                'account_phone' => '09 400 000 003',
                'account_number' => 'AYA-000000003',
                'qr_image' => 'payment-qr/ayapay.png',
                'instructions' => 'Transfer the exact order total to this AYA Pay account and upload the slip.',
                'sort_order' => 30,
            ],
            [
                'name' => 'CB Pay',
                'code' => 'cbpay',
                'account_name' => 'LuvShop Myanmar',
                'account_phone' => '09 400 000 004',
                'account_number' => 'CB-000000004',
                'qr_image' => 'payment-qr/cbpay.png',
                'instructions' => 'Transfer the exact order total to this CB Pay account and upload the slip.',
                'sort_order' => 40,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method + ['is_active' => true]
            );
        }
    }
}
