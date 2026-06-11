<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get all users (Admin only)
     */
    public function index(Request $request)
    {
        if (!$request->user()->is_admin && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $users = User::paginate(15);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    /**
     * Create a new user (Admin only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->is_admin && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Create user with a temporary random password — user will set their own after verification
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role'     => 'user',
            'is_admin' => false,
            'status'   => 'pending',
        ]);

        // Generate verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = \App\Models\EmailVerification::create([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'code'       => $code,
            'expires_at' => now()->addHours(24),
        ]);

        // Send verification email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerificationCodeMail($verification, $user->name));

        return response()->json([
            'message' => 'Usuario creado. Se envió un código de verificación a su correo para que pueda activar su cuenta y establecer su contraseña.',
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ],
        ], 201);
    }

    /**
     * Get a specific user
     */
    public function show(Request $request, User $user)
    {
        if (!$request->user()->is_admin && $request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
                'status' => $user->status,
                'email_verified_at' => $user->email_verified_at,
            ],
        ], 200);
    }

    /**
     * Update a user
     */
    public function update(Request $request, User $user)
    {
        if (!$request->user()->is_admin && $request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $rules = [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];

        if ($request->user()->is_admin || $request->user()->role === 'admin') {
            $rules['role'] = 'nullable|string|in:admin,user,manager';
            $rules['is_admin'] = 'nullable|boolean';
            $rules['status'] = 'nullable|string|in:pending,active,inactive';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->user()->is_admin || $request->user()->role === 'admin') {
            if ($request->has('role')) {
                $user->role = $request->role;
            }
            if ($request->has('is_admin')) {
                $user->is_admin = $request->is_admin;
            }
            if ($request->has('status')) {
                $user->status = $request->status;
            }
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
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

    /**
     * Delete a user
     */
    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->is_admin && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ], 200);
    }
}
