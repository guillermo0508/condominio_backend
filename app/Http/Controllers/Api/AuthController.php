<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordRecoveryMail;
use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private function deviceTokenName(Request $request): string
    {
        $deviceId = $request->header('X-Device-Id') ?: $request->header('User-Agent', 'default_device');

        return 'device:' . hash('sha256', $deviceId);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'is_admin' => false,
            'status' => 'pending',
        ]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addHours(24),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($verification, $user->name));

        return response()->json([
            'message' => 'User registered successfully. Please check your email for the verification code.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $verification = EmailVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$verification) {
            return response()->json([
                'message' => 'Código de verificación inválido',
            ], 400);
        }

        if (!$verification->isValid()) {
            return response()->json([
                'message' => 'El código de verificación ha expirado',
            ], 400);
        }

        $user = $verification->user;

        $needsPassword = $user->status === 'pending' && !$user->email_verified_at;

        $user->update([
            'email_verified_at' => now(),
            'status'            => 'active',
        ]);

        $verification->markAsVerified();

        if ($needsPassword) {
            $setupToken = $user->createToken('password_setup', ['password-setup'], now()->addMinutes(30))->plainTextToken;
            return response()->json([
                'message'       => 'Email verificado. Por favor establece tu contraseña.',
                'needs_password' => true,
                'setup_token'   => $setupToken,
                'user'          => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Email verificado exitosamente',
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'status' => $user->status,
            ],
        ], 200);
    }

    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password'              => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->update(['password' => Hash::make($request->password)]);

        $user->tokens()->delete();

        $deviceName = $this->deviceTokenName($request);
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message'      => '¡Contraseña establecida! Ya puedes usar la aplicación.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'is_admin' => $user->is_admin,
                'status'   => $user->status,
            ],
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Account is not verified. Please verify your email first.',
            ], 403);
        }

        $deviceName = $this->deviceTokenName($request);

        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
                'status' => $user->status,
            ],
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'is_admin' => $request->user()->is_admin,
                'status' => $request->user()->status,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }

    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'Email is already verified',
            ], 400);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addHours(24),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($verification, $user->name));

        return response()->json([
            'message' => 'Verification code sent to your email',
        ], 200);
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Return success even if user not found to prevent email enumeration
            return response()->json([
                'message' => 'Si el correo electrónico existe, se ha enviado un código de recuperación.',
            ], 200);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old codes for this email
        PasswordResetCode::where('email', $user->email)->delete();

        $resetCode = PasswordResetCode::create([
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($user->email)->send(new PasswordRecoveryMail($resetCode, $user->name));

        return response()->json([
            'message' => 'Si el correo electrónico existe, se ha enviado un código de recuperación.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resetCode = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$resetCode) {
            return response()->json([
                'message' => 'Código de recuperación inválido',
            ], 400);
        }

        if (!$resetCode->isValid()) {
            return response()->json([
                'message' => 'El código de recuperación ha expirado',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Revoke all tokens so they have to log in again
        $user->tokens()->delete();

        // Delete the used reset code
        $resetCode->delete();

        return response()->json([
            'message' => 'Tu contraseña ha sido restablecida exitosamente.',
        ], 200);
    }
}
