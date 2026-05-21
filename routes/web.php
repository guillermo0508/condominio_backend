<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/auth/register', function () {
    $data = request()->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:6',
    ]);
    
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);
    
    return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
});

Route::post('/auth/login', function () {
    $data = request()->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);
    
    $user = User::where('email', $data['email'])->first();
    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
    
    $token = $user->createToken('auth')->plainTextToken;
    return ['token' => $token, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]];
});

Route::middleware('auth:sanctum')->post('/auth/logout', function () {
    request()->user()->currentAccessToken()->delete();
    return ['message' => 'Logged out'];
});

Route::middleware('auth:sanctum')->post('/chat/send', function () {
    $data = request()->validate(['message' => 'required|string']);
    
    $user = request()->user();
    
    $chatMessage = ChatMessage::create([
        'user_id' => $user->id,
        'message' => $data['message'],
    ]);

    event(new \App\Events\DepartmentChatMessage(
        $data['message'],
        $user->id,
        $user->name
    ));
    
    return ['ok' => true, 'message' => $chatMessage];
});

Route::middleware('auth:sanctum')->get('/chat/messages', function () {
    $messages = ChatMessage::with('user:id,name')
        ->orderBy('created_at', 'desc')
        ->take(50)
        ->get()
        ->reverse()
        ->values();
        
    $formattedMessages = $messages->map(function ($msg) {
        return [
            'user_id' => $msg->user_id,
            'user_name' => $msg->user ? $msg->user->name : 'Unknown User',
            'text' => $msg->message,
            'id' => $msg->id,
            'created_at' => $msg->created_at
        ];
    });

    return $formattedMessages;
});
