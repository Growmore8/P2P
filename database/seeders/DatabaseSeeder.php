<?php

namespace Database\Seeders;

use App\Models\DepositMethod;
use App\Models\Network;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account — set ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding in production.
        $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@srmfintech.local')]);
        if (! $admin->exists) {
            $admin->fill([
                'name' => 'SRM Admin',
                'phone' => env('ADMIN_PHONE', '+919000000000'),
                'country' => 'IN',
                'password' => env('ADMIN_PASSWORD', 'ChangeMe@123'),
            ]);
            $admin->forceFill(['is_admin' => true, 'kyc_status' => 'approved', 'email_verified_at' => now()])->save();
        }

        $networks = [
            ['code' => 'TRC20', 'name' => 'Tron (TRC20)', 'fee' => 1.00, 'address_regex' => '^T[1-9A-HJ-NP-Za-km-z]{33}$', 'sort' => 1],
            ['code' => 'BEP20', 'name' => 'BNB Chain (BEP20)', 'fee' => 0.50, 'address_regex' => '^0x[a-fA-F0-9]{40}$', 'sort' => 2],
            ['code' => 'ERC20', 'name' => 'Ethereum (ERC20)', 'fee' => 5.00, 'address_regex' => '^0x[a-fA-F0-9]{40}$', 'sort' => 3],
            ['code' => 'POLYGON', 'name' => 'Polygon', 'fee' => 0.50, 'address_regex' => '^0x[a-fA-F0-9]{40}$', 'sort' => 4],
        ];
        foreach ($networks as $n) {
            Network::firstOrCreate(['code' => $n['code']], $n);
        }

        if (! DepositMethod::exists()) {
            DepositMethod::create([
                'type' => 'upi', 'title' => 'UPI (GPay / PhonePe / Paytm)', 'upi_id' => 'yourname@okbank',
                'instructions' => "Pay the exact amount.\nDo not write crypto/USDT in the payment remark.\nPay only from your own verified account.",
                'max_amount' => 100000, 'is_active' => false, 'sort' => 1,
            ]);
            DepositMethod::create([
                'type' => 'bank', 'title' => 'IMPS / NEFT / RTGS', 'account_holder' => 'SRM FINTECH SOLUTIONS',
                'account_number' => '000000000000', 'ifsc' => 'SBIN0000000', 'bank_name' => 'Your Bank',
                'instructions' => "Transfer the exact amount via IMPS/NEFT/RTGS.\nPay only from your own verified account.",
                'is_active' => false, 'sort' => 2,
            ]);
        }
    }
}
