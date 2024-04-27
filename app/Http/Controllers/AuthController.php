<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Auth Controller for create user
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name'     => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
            'pin'      => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['messages' => $validator->messages()], 400);
        }
        $user = User::where('email', $request->email)->exists();

        if ($user) {
            return response()->json(['messages' => 'email already taken'], 409);
        }

        DB::beginTransaction();
        
        try {
            $profilePicture = null;
            $ktp = null;
            if ($request->profile_picture) {
                $profilePicture =  $this->uploadBase64Image($request->profile_picture);
            }
            if ($request->ktp) {
                $ktp = $this->uploadBase64Image($request->ktp);
            }
            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'username'        => $request->email,
                'password'        => bcrypt($request->password),
                'profile_picture' => $profilePicture,
                'ktp'             => $ktp,
                'verified'        => $ktp ? true : false,
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $this->generateCardNumber(16),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['messages' => $th->getMessage()], 500);
        }
    }

    /**
     * generate card number
     *
     * @return $number
     */
    public function generateCardNumber($length)
    {
        $number = null;
        for ($i = 0; $i < $length; $i++) {
            $number .= mt_rand(0, 9);
        }

        $wallet = Wallet::where('card_number', $number)->exists();
        if ($wallet) {
            return $this->generateCardNumber($length);
        }
        return $number;
    }

    /**
     * Function for code base64 to db
     *
     * @param string $imgUpload
     * @return $image
     */
    private function uploadBase64Image($imgUpload)
    {
        $decoder = new Base64ImageDecoder($imgUpload, $allowedFormats = ['jpeg', 'png', 'gif']);
        $decodedContent = $decoder->getDecodedContent();
        $format = $decoder->getFormat();
        $image = Str::random(10) . '.' . $format;
        Storage::disk('public')->put($image, $decodedContent);
        return $image;
    }
}
