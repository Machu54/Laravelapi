<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Events\Evento;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/prueba', function () {

    return response()->json([
        'ok' => true
    ]);
});

Route::post('/usuarios', function (Request $request) {

    $id = DB::table('usuarios')->insertGetId([

        'id_persona' => $request->id_persona,

        'correo' => $request->correo,

        'pass' => Hash::make($request->pass),

        'admin' => $request->admin ?? false
    ]);

    return response()->json([
        'ok' => true,
        'mensaje' => 'Usuario agregado correctamente',
        'id' => $id
    ]);
});

Route::post('/login', function (Request $request) {

    $usuario = DB::table('usuarios')
        ->where('correo', $request->correo)
        ->first();

    if (!$usuario) {

        return response()->json([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado'
        ], 401);
    }

    if (!Hash::check($request->pass, $usuario->pass)) {

        return response()->json([
            'ok' => false,
            'mensaje' => 'Contraseña incorrecta'
        ], 401);
    }

    return response()->json([
        'ok' => true,
        'usuario' => $usuario
    ]);
});

Route::post('/mensaje', function (Request $request) {

    DB::table('mensajes')->insert([

        'remitente' => $request->remitente,

        'destinatario' => null,

        'mensaje' => $request->mensaje
    ]);

    event(new Evento([

        'remitente' => $request->remitente,

        'usuario' => $request->usuario,

        'mensaje' => $request->mensaje
    ]));

    return response()->json([
        'ok' => true
    ]);
});
