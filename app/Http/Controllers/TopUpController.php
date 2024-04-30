<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        return true;
    }
}
