<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function update()
    {
        \Midtrans\Config::$serverKey    = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        $notif = new \Midtrans\Notification();

        $transactionStatus = $notif->transaction_status;
        $type = $notif->payment_type;
        $transactionCode = $notif->order_id;
        $fraudStatus = $notif->fraud_status;

        DB::beginTransaction();
        try {
            $status = null;

            if ($transactionStatus == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraudStatus == 'accept') {
                        // TODO set payment status in merchant's database to 'Success'
                        $status = 'success';
                    }
                } else {
                    if ($fraudStatus == 'accept') {
                        // TODO set payment status in merchant's database to 'Success'
                        $status = 'success';
                    } else if ($fraudStatus == 'challenge') {
                        $status = 'challenge';
                    }
                }
            } else if ($transactionStatus == 'settlement') {
                // TODO set payment status in merchant's database to 'Settlement'
                $status = 'success';
            } else if ($transactionStatus == 'pending') {
                // TODO set payment status in merchant's database to 'Pending'
                $status = 'pending';
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                // TODO set payment status in merchant's database to 'Denied'
                $status = 'failed';
            }

            $transaction = Transaction::where('transaction_code', $transactionCode)->first();

            if ($transaction->status != 'success') {
                $trasactionAmount = $transaction->amount;
                $userId = $transaction->user_id;

                if ($status == 'success') {
                    Wallet::where('user_id', $userId)->increment('balance', $trasactionAmount);
                }
            }

            DB::commit();
            return response()->json();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["messages" => $th->getMessage()]);
        }
    }
}
