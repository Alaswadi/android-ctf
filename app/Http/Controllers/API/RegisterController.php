<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            $validator = Validator::make($request->all(), [
                'name'  => 'required|string|min:3|max:255',
                'email' => 'required|email|unique:users,email',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag(),
                ]);
            }
            $otp = "" . rand(100000, 999999);

            if (isset($user) && $user){

                if ($user->verification_code_expire > 3 && Carbon::parse($user->updated_at)->addMinutes(10) >= Carbon::now()) {
                    $user->tokens()->where('name', 'MyApp')->delete();
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الحد المسموح لإعادة إرسال رمز التحقق، حاول مرة أخرى بعد 10 دقيقة',
                    ], 500);
                }

                if ($user->verification_code_expire > 3 && Carbon::parse($user->updated_at)->addMinutes(10) <= Carbon::now()) {
                    $user->verification_code_expire = 0;
                    $user->save();
                }

                $user->verification_code_expire += 1;
                $user->verification_code = Hash::make($otp);
                $user->verify_status = 0;
                $user->save();
            } else {
                $user = User::create([
                    'name' => $request->name,
                    'email'     => $request->email,
                    'verification_code'  => Hash::make($otp)
                ]);
            }

            $token = $user->createToken('MyApp')->plainTextToken;

            return response()->json([
                'status' => true,
                'email' => $request->email,
                'token' => $token,
                'otp' => $otp,
                'message' => 'تم إرسال رمز التحقق   ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'لقد حدث خطا في تسجيل الدخول',
            ]);
        }
    }
    public function checkOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'verification_code'       => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag(),
                ]);
            }
            $user = Auth::user();
            $user = User::where('id', $user->id)->first();
            $user->verification_code_expire++;
            $user->save();

            if (Hash::check($request->verification_code, $user->verification_code)) {
                $user->verification_code_expire = 0;
                $user->verify_status = 1;
                $user->save();
                return response()->json([
                    'status' => true,
                    'message' => 'كود التحقق صحيح',
                    'data' => $user
                ], 200);
            } else {
                $response = [
                    'status' => false,
                    'message' => 'كود التحقق غير صحيح',
                ];
                return response()->json($response, 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => ' هناك المشكلة ما الرجاء المحاولة مرة اخرى',
            ], 403);
        }
    }
}
