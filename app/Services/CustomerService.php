<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function createInvoiceCharge(Invoice $invoice, ?float $amount = null): void
    {
        if (!$invoice->customer_id) {
            return;
        }

        $chargeAmount = $amount ?? $invoice->final_total;

        DB::transaction(function () use ($invoice, $chargeAmount) {
            $customer   = Customer::lockForUpdate()->findOrFail($invoice->customer_id);
            $newBalance = $customer->balance + $chargeAmount;

            if ($customer->credit_limit > 0 && $newBalance > $customer->credit_limit) {
                throw new \Exception(
                    __('pos.credit_limit_exceeded', [
                        'limit' => $customer->credit_limit,
                        'new'   => $newBalance,
                    ])
                );
            }

            CustomerAccount::create([
                'customer_id'    => $customer->id,
                'type'           => 'invoice',
                'debit'          => $chargeAmount,
                'balance_after'  => $newBalance,
                'reference_type' => 'invoice',
                'reference_id'   => $invoice->id,
                'created_by'     => Auth::id(),
            ]);

            $customer->balance = $newBalance;
            $customer->save();
        });
    }

    public function recordPayment(Customer $customer, float $amount, string $method): void
    {
        DB::transaction(function () use ($customer, $amount, $method) {
            $locked     = Customer::lockForUpdate()->findOrFail($customer->id);
            $newBalance = $locked->balance - $amount;

            CustomerAccount::create([
                'customer_id'   => $locked->id,
                'type'          => 'payment',
                'credit'        => $amount,
                'balance_after' => $newBalance,
                'notes'         => "Payment via {$method}",
                'created_by'    => Auth::id(),
            ]);

            $locked->balance = $newBalance;
            $locked->save();
        });
    }

    public function addLoyaltyPoints(Customer $customer, float $invoiceTotal): void
    {
        $rate = (int) setting('loyalty_earn_rate', 10);
        if ($rate <= 0) {
            return;
        }

        $points = (int) floor($invoiceTotal / $rate);
        if ($points > 0) {
            $customer->increment('loyalty_points', $points);
        }
    }

    public function redeemLoyaltyPoints(Customer $customer, int $points): float
    {
        $value = (float) setting('loyalty_redeem_value', 0.5);
        $min   = (int) setting('loyalty_min_redeem', 100);

        if ($points < $min || $customer->loyalty_points < $points) {
            throw new \Exception(__('pos.insufficient_loyalty_points', ['min' => $min]));
        }

        $discount = $points * $value;
        $customer->decrement('loyalty_points', $points);

        return $discount;
    }

    public function nextCode(): string
    {
        $last = Customer::withTrashed()->orderByDesc('id')->value('code');
        $num  = $last ? ((int) substr($last, 5)) + 1 : 1;

        return 'CUST-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
