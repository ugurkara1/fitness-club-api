<?php

// AuthController
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Role;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,user',
        ]);

        if ($validator->fails()) {
            Log::warning('Registration failed: Validation error for email: ' . $request->email);
            return response()->json([
                'message' => __('messages.validation_error'),
                'errors' => $validator->errors(),
            ], 400);
        }

        $role = Role::where('name', $request->role)->first();

        if (!$role) {
            Log::warning('Role not found: ' . $request->role);  // Role bulunamadığında log kaydediyoruz
            return response()->json(['message' => __('messages.role_not_found')], 404);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
        ]);

        Log::info('User registered: ' . $user->email);  // Kullanıcı kaydedildi

        // Eğer rol 'admin' ise, OTP doğrulama iste
        if ($role->name == 'admin') {
            $google2fa = new Google2FA();
            $otpSecret = $google2fa->generateSecretKey(); //benzersiz bir secretkey oluşturur
            $user->google2fa_secret = $otpSecret; //oluşturulan secret keyi kullanıcı modeline kaydeder
            $user->save();

            Log::info('Admin user registered with OTP setup: ' . $user->email);  // Admin kullanıcı için OTP ayarlandı

            return response()->json([
                'message' => __('messages.admin_registered'),
                'qr_code_url' => $google2fa->getQRCodeUrl('GymApp', $user->email, $otpSecret),
            ], 201);
        }

        // Admin değilse token oluştur
        $token = $user->createToken('GymApp')->plainTextToken;
        Log::info('Token created for regular user: ' . $user->email);  // Normal kullanıcı için token oluşturuldu

        return response()->json([
            'token' => $token,
            'message' => __('messages.user_registered'),
        ], 201);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            Log::warning('Login failed: Validation error for email: ' . $request->email);
            return response()->json([
                'message'=>__('messages.invalid_data')
                ,'errors'=>$validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Unauthorized login attempt for email: ' . $request->email);
            return response()->json(['message' => __('messages.unauthorized')], 401);
        }

        Log::info('Login successful for user: ' . $user->email);

        if ($user->role->name == 'admin') {
            Log::info('Admin user logged in, OTP verification required: ' . $user->email);

            // Her giriş için benzersiz bir nonce oluştur
            $nonce = Str::random(32);  // 32 karakterlik rastgele dize

            // Payload içine yalnızca ID ve nonce ekliyoruz (email çıkarıldı)
            $payload = [
                'id'    => $user->id,
                'nonce' => $nonce,
            ];
            $base64Token = base64_encode(json_encode($payload));

            return response()->json([
                'message'       => __('messages.otp_required'),
                'require_otp'   => true,
                'base64_token'  => $base64Token,  // Her girişte farklı!
            ], 200);
        }

        // Normal kullanıcı giriş yapıyorsa direkt token oluştur
        $token = $user->createToken('GymApp')->plainTextToken;
        return response()->json(['token' => $token], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp'          => 'required|numeric',
            'base64_token' => 'required|string',
        ]);

        // Base64 token'ı çöz
        $decodedToken = json_decode(base64_decode($request->base64_token), true);

        if (
            !$decodedToken ||
            !isset($decodedToken['id'])
        ) {
            Log::warning('OTP verification failed: Invalid token payload');
            return response()->json(['message' => __('messages.invalid_token')], 400);
        }

        // Token içerisindeki ID ile kullanıcıyı bul
        $user = User::find($decodedToken['id']);

        if (!$user) {
            Log::warning('OTP verification failed: User not found for id ' . $decodedToken['id']);
            return response()->json(['message' => __('messages.user_not_found')], 404);
        }

        // OTP doğrulaması
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // OTP başarılıysa kullanıcı için API token'ı oluşturuyoruz
            $token = $user->createToken('GymApp')->plainTextToken;
            Log::info('OTP verified successfully for user: ' . $user->email);
            return response()->json(['token' => $token], 200);
        } else {
            Log::warning('Invalid OTP entered for user: ' . $user->email);
            return response()->json(['message' => __('messages.invalid_otp')], 400);
        }
    }


    public function getUser(Request $request)
    {
        Log::info('User details requested for user: ' . $request->user()->email);  // Kullanıcı detayları isteniyor
        return response()->json([
            'message' => __('messages.user_details'), // Dil dosyasından mesaj al
            'user' => $request->user(), // Kullanıcı bilgileri
        ], 200);    }
    public function logout(Request $request){
        //get login user
        $user=$request->user();
        //token delete
        $user->currentAccessToken()->delete();
        Log::info('User logged out: ' . $user->email);
        return response()->json(['message' => __('messages.logout_successful')], 200);
    }
}