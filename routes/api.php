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

/* =========================================
   PRUEBA
========================================= */

Route::get('/prueba', function () {

    return response()->json([
        'ok' => true
    ]);
});

/* =========================================
   USUARIOS
========================================= */

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

/* =========================================
   LOGIN
========================================= */

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

/* =========================================
   PERSONAS
========================================= */

Route::get('/personas', function () {

    return DB::table('personas')
    ->select(
        'id',
        'nombre',
        'apellido_p'
    )
    ->orderBy('nombre')
    ->get();
});

/* =========================================
   MENSAJES
========================================= */

Route::get('/mensajes', function () {

    return DB::table('mensajes')
    ->orderBy('id')
    ->get();
});

/* =========================================
   CHAT
========================================= */

Route::post('/mensaje', function (Request $request) {

    DB::table('mensajes')->insert([

        'remitente' => $request->remitente,

        'destinatario' => null,

        'mensaje' => $request->mensaje
    ]);

    event(new Evento(

        [

            'tipo' => 'mensaje',

            'remitente' => $request->remitente,

            'usuario' => $request->usuario,

            'mensaje' => $request->mensaje
        ],

        'chat'
    ));

    return response()->json([

        'ok' => true
    ]);
});

/* =========================================
   MULTAS
========================================= */

/* OBTENER TODAS */

Route::get('/multas', function () {

    return DB::table('multas')
    ->join(
        'personas',
        'multas.id_persona',
        '=',
        'personas.id'
    )
    ->select(
        'multas.*',
        'personas.nombre',
        'personas.apellido_p'
    )
    ->orderBy('multas.id', 'desc')
    ->get();
});

/* MULTAS POR USUARIO */

Route::get('/multas-usuario/{id}', function ($id) {

    return DB::table('multas')
    ->where('id_persona', $id)
    ->orderBy('id', 'desc')
    ->get();
});

/* CREAR MULTA */

Route::post('/multas', function (Request $request) {

    $id = DB::table('multas')->insertGetId([

        'id_persona' => $request->id_persona,

        'motivo' => $request->motivo,

        'monto' => $request->monto,

        'estado' => 'Pendiente'
    ]);

    /* NOTIFICACION */

    event(new Evento(

        [

            'tipo' => 'multa',

            'mensaje' => 'Tienes una nueva multa'
        ],

        'multas.' . $request->id_persona
    ));

    return response()->json([

        'ok' => true,

        'mensaje' => 'Multa creada correctamente',

        'id' => $id
    ]);
});

/* ACTUALIZAR MULTA */

Route::put('/multas/{id}', function (
    Request $request,
    $id
) {

    DB::table('multas')
    ->where('id', $id)
    ->update([

        'motivo' => $request->motivo,

        'monto' => $request->monto,

        'estado' => $request->estado
    ]);

    return response()->json([

        'ok' => true,

        'mensaje' => 'Multa actualizada'
    ]);
});

/* ELIMINAR MULTA */

Route::delete('/multas/{id}', function ($id) {

    DB::table('multas')
    ->where('id', $id)
    ->delete();

    return response()->json([

        'ok' => true,

        'mensaje' => 'Multa eliminada'
    ]);
});
