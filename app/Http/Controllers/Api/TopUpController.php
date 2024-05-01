<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TopUpController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'payment_method_code');
        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'payment_method_code' => 'required|in:bri_va,bca_va,bni_va',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->messages()
            ]);
        }
        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json([
                'messages' => 'Your PIN is wrong'
            ]);
        }
        $transactionType = TransactionType::where('code', 'top_up')->first();
        $paymentMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id'             => auth()->user()->id,
                'payment_method_id'   => $paymentMethod->id,
                'transaction_type_id' => $transactionType->id,
                'amount'              => $request->amount,
                'transaction_code'    => strtoupper(Str::random(10)),
                'description'         => 'Top up via ' . $paymentMethod->name,
                'status'              => $paymentMethod->id,
            ]);

            $params = $this->buildMidtransParameters([
                'transaction_code' => $transaction->transaction_code,
                'amount'           => $transaction->amount,
                'payment_method'   => $paymentMethod->code,
            ]);

            $midtrans = $this->callMidtrans($params);
            DB::commit();

            return response()->json($midtrans);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'messages' => $th->getMessage()
            ], 500);
        }
    }

    private function callMidtrans(array $params)
    {
        \Midtrans\Config::$serverKey    = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized  = (bool) env('MIDTRANS_IS_SANITIZED');
        \Midtrans\Config::$is3ds        = (bool) env('MIDTRANS_IS_3DS');

        $createTransaction = \Midtrans\Snap::createTransaction($params);

        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token'        => $createTransaction->token,
        ];
    }

    private function buildMidtransParameters(array $params)
    {
        $transactionDetails = [
            'order_id'     => $params['transaction_code'],
            'gross_amount' => $params['amount'],
        ];

        $user = auth()->user();
        $splitName = $this->splitName($user->name);
        $customerDetails = [
            'first_name' => $splitName['first_name'],
            'last_name' => $splitName['last_name'],
            'email' => $user->email,
        ];
        $enabledPayments = [
            $params['payment_method']
        ];

        return [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'enabled_payments' => $enabledPayments,
        ];
    }

    private function splitName(string $fullname)
    {
        $name = explode(' ', $fullname);
        $lastName = count($name) > 1 ? array_pop($name) : $fullname;
        $firstName = implode(' ', $name);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }
}
