<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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
        try {
            $profilePicture = null;
            $ktp = null;
            if ($request->profile_picture) {
                $this->uploadBase64Image($request->profile_picture);
            }
            if ($request->ktp) {
                $this->uploadBase64Image($request->ktp);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

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
