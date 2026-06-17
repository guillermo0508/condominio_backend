<?php

use Illuminate\Support\Facades\Route;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

function auth_user_payload(User $user): array
{
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'is_admin' => $user->role === 'admin',
    ];
}

function device_token_name(): string
{
    $deviceId = request()->header('X-Device-Id') ?: request()->header('User-Agent', 'default_device');

    return 'device:' . hash('sha256', $deviceId);
}

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/admin-status', function () {
    $admin = User::where('email', '!=', 'admin@condominio.com')
        ->where('role', 'admin')
        ->first(['id', 'name', 'email']);

    return [
        'has_resident_admin' => $admin !== null,
        'admin' => $admin,
    ];
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
        'role' => 'resident',
        'is_admin' => false,
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

    $deviceName = device_token_name();
    $user->tokens()->where('name', $deviceName)->delete();

    $token = $user->createToken($deviceName)->plainTextToken;
    return ['token' => $token, 'user' => auth_user_payload($user)];
});

Route::post('/auth/admin-master-login', function () {
    $data = request()->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if ($data['username'] !== 'admin' || $data['password'] !== 'admin123') {
        return response()->json(['error' => 'Credenciales de administrador maestro incorrectas'], 401);
    }

    $user = User::updateOrCreate(
        ['email' => 'admin@condominio.com'],
        [
            'name' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_admin' => true,
        ]
    );

    $deviceName = device_token_name();
    $user->tokens()->where('name', $deviceName)->delete();

    $token = $user->createToken($deviceName)->plainTextToken;
    return ['token' => $token, 'user' => auth_user_payload($user)];
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

    try {
        event(new \App\Events\DepartmentChatMessage(
            $data['message'],
            $user->id,
            $user->name
        ));
    } catch (\Throwable $e) {
        Log::warning('No se pudo transmitir el mensaje por websocket.', [
            'error' => $e->getMessage(),
        ]);
    }

    $notification = new \App\Notifications\CondominioNotification(
        'mensaje',
        'Nuevo mensaje de ' . $user->name,
        $data['message'],
        []
    );

    $otherUsers = User::where('id', '!=', $user->id)->get();
    try {
        Notification::send($otherUsers, $notification);
    } catch (\Throwable $e) {
        Log::warning('No se pudo transmitir la notificación del chat.', [
            'error' => $e->getMessage(),
        ]);
        Notification::sendNow($otherUsers, $notification, ['database']);
    }

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

Route::middleware('auth:sanctum')->get('/notifications', function () {
    return request()->user()
        ->notifications()
        ->latest()
        ->take(50)
        ->get();
});

Route::middleware('auth:sanctum')->post('/notifications/{id}/mark-as-read', function ($id) {
    $notification = request()->user()->notifications()->where('id', $id)->first();
    if ($notification) {
        $notification->markAsRead();
    }
    return ['ok' => true];
});

Route::middleware('auth:sanctum')->post('/notifications/test', function () {
    $types = ['mensaje', 'multas', 'asambleas', 'pagos_atrasados'];
    $type = $types[array_rand($types)];

    $titles = [
        'mensaje' => 'Nuevo Mensaje de Administración',
        'multas' => 'Multa por ruido excesivo',
        'asambleas' => 'Asamblea Ordinaria Programada',
        'pagos_atrasados' => 'Recordatorio de Pago Atrasado'
    ];

    $messages = [
        'mensaje' => 'Por favor revisar el nuevo reglamento de la piscina.',
        'multas' => 'Se le ha asignado una multa por ruidos molestos el fin de semana pasado.',
        'asambleas' => 'La próxima asamblea será el día 15 del presente mes a las 20:00 hrs.',
        'pagos_atrasados' => 'Tiene un saldo pendiente de sus gastos comunes del mes anterior.'
    ];

    $notification = new \App\Notifications\CondominioNotification(
        $type,
        $titles[$type],
        $messages[$type],
                ['amount' => 5000, 'date' => now()->toDateTimeString()]
    );

    try {
        request()->user()->notify($notification);
    } catch (\Throwable $e) {
        Log::warning('No se pudo transmitir la notificación de prueba.', [
            'error' => $e->getMessage(),
        ]);
        request()->user()->notifyNow($notification, ['database']);
    }

    return ['ok' => true, 'notified' => $type];
});

Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->group(function () {

        Route::get('/users', function () {
            return User::where('email', '!=', 'admin@condominio.com')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'is_admin']);
        });

        Route::patch('/users/{id}/role', function ($id) {
            $data = request()->validate([
                'role' => 'required|string|in:admin,resident',
            ]);

            $user = User::findOrFail($id);

            if ($user->id === request()->user()->id && $data['role'] !== 'admin') {
                return response()->json(['error' => 'No puedes quitarte el rol de administrador a ti mismo.'], 403);
            }

            $user->role = $data['role'];
            $user->is_admin = $data['role'] === 'admin';
            $user->save();

            return ['ok' => true, 'user' => $user];
        });

        Route::post('/assign-administrator', function () {
            $data = request()->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $selectedUser = User::where('email', '!=', 'admin@condominio.com')
                ->findOrFail($data['user_id']);

            User::where('email', '!=', 'admin@condominio.com')->update([
                'role' => 'resident',
                'is_admin' => false,
            ]);

            $selectedUser->forceFill([
                'role' => 'admin',
                'is_admin' => true,
            ])->save();

            return ['ok' => true, 'user' => auth_user_payload($selectedUser)];
        });

        Route::post('/notify', function () {
            $data = request()->validate([
                'user_id' => 'required',
                'type' => 'required|string',
                'title' => 'required|string',
                'message' => 'required|string',
                'details' => 'nullable|array'
            ]);

            $notification = new \App\Notifications\CondominioNotification(
                $data['type'],
                $data['title'],
                $data['message'],
                $data['details'] ?? []
            );

            $targetUsers = ($data['user_id'] === 'all')
                ? User::where('email', '!=', 'admin@condominio.com')->get()
                : User::where('email', '!=', 'admin@condominio.com')
                    ->where('id', $data['user_id'])
                    ->get();

            if ($targetUsers->isEmpty()) {
                return response()->json(['error' => 'No se encontraron residentes para notificar.'], 422);
            }

            try {
                Notification::send($targetUsers, $notification);
            } catch (\Throwable $e) {
                Log::warning('No se pudo transmitir la notificación de admin.', [
                    'error' => $e->getMessage(),
                ]);
                Notification::sendNow($targetUsers, $notification, ['database']);
            }

            $recipientLabel = $data['user_id'] === 'all'
                ? 'Todos los residentes'
                : $targetUsers->first()->name . ' (' . $targetUsers->first()->email . ')';

            try {
                request()->user()->notify(new \App\Notifications\CondominioNotification(
                    $data['type'],
                    'Notificación enviada a: ' . $recipientLabel,
                    $data['message'],
                    [
                        'destinatario' => $recipientLabel,
                        'tipo' => $data['type'],
                        'titulo' => $data['title'],
                        'mensaje' => $data['message'],
                        'detalles' => $data['details'] ?? [],
                    ]
                ));
            } catch (\Throwable $e) {
                Log::warning('No se pudo transmitir la notificación de confirmación del admin.', [
                    'error' => $e->getMessage(),
                ]);
                request()->user()->notifyNow(new \App\Notifications\CondominioNotification(
                    $data['type'],
                    'Notificación enviada a: ' . $recipientLabel,
                    $data['message'],
                    [
                        'destinatario' => $recipientLabel,
                        'tipo' => $data['type'],
                        'titulo' => $data['title'],
                        'mensaje' => $data['message'],
                        'detalles' => $data['details'] ?? [],
                    ]
                ), ['database']);
            }

            return ['ok' => true, 'recipients' => $targetUsers->count()];
        });
    });
