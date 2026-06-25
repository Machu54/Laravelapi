<?php
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use App\Mail\Correos;
use App\Mail\RecuperarPassword;

use App\Events\Evento;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::post('/verificar-codigo', function (Request $request) {

    $usuario = DB::table('usuarios')
    ->where(
        'correo',
        strtolower($request->correo)
    )
    ->first();

    if(!$usuario){

        return response()->json([

            'ok' => false,

            'mensaje' => 'Usuario no encontrado.'
        ]);
    }

    if(
        $usuario->codigo_expira
        &&
        now()->greaterThan(
            $usuario->codigo_expira
        )
    ){

        DB::table('usuarios')
        ->where('id', $usuario->id)
        ->update([

            'codigo_recuperacion' => null,

            'codigo_expira' => null
        ]);

        return response()->json([

            'ok' => false,

            'mensaje' => 'El código ha expirado.'
        ]);
    }

    if(
        $usuario->codigo_recuperacion
        !=
        $request->codigo
    ){

        return response()->json([

            'ok' => false,

            'mensaje' => 'Código incorrecto.'
        ]);
    }

    return response()->json([

        'ok' => true
    ]);
});


Route::post('/nueva-password', function (Request $request) {

    $usuario = DB::table('usuarios')
    ->where(
        'correo',
        strtolower($request->correo)
    )
    ->first();

    if(!$usuario){

        return response()->json([

            'ok' => false,

            'mensaje' => 'Usuario no encontrado.'
        ], 404);
    }

    DB::table('usuarios')
    ->where('id', $usuario->id)
    ->update([

        'pass' => Hash::make(
            $request->pass
        ),

        'codigo_recuperacion' => null,

        'codigo_expira' => null,

        'updated_at' => now()
    ]);

    return response()->json([

        'ok' => true,

        'mensaje' => 'Contraseña actualizada correctamente.'
    ]);
});


Route::post('/recuperar', function (Request $request) {

    $usuario = DB::table('usuarios')
    ->where(
        'correo',
        strtolower($request->correo)
    )
    ->first();

    if (!$usuario) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Correo no encontrado'
        ], 404);
    }

    $codigo = rand(100000, 999999);

    DB::table('usuarios')
    ->where('id', $usuario->id)
    ->update([

        'codigo_recuperacion' => $codigo,

        'codigo_expira' => now()->addMinutes(10),

        'updated_at' => now()
    ]);

    Mail::to($usuario->correo)
    ->send(
         new RecuperarPassword($codigo)
    );

    return response()->json([

        'ok' => true,

        'mensaje' => 'Código enviado al correo.'
    ]);
});


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
Route::middleware('auth:sanctum')
->post('/usuarios', function (Request $request) {

    DB::beginTransaction();

    try {

        $codigo = rand(100000,999999);

        $idPersona = DB::table('personas')->insertGetId([

            'nombre' => $request->nombre,

            'apellido_p' => $request->apellido_p,

            'apellido_m' => $request->apellido_m,

            'celular' => $request->celular,

            'activo' => true,

            'created_at' => now(),

            'updated_at' => now()
        ]);

        $idUsuario = DB::table('usuarios')->insertGetId([

            'id_persona' => $idPersona,

            'correo' => strtolower($request->correo),

            'pass' => Hash::make($request->pass),

            'admin' => $request->admin ?? false,

            'correo_verificado' => false,

            'codigo_verificacion' => $codigo,

            'created_at' => now(),

            'updated_at' => now()
        ]);

        Mail::to($request->correo)
            ->send(new Correos($codigo));

        DB::commit();

        return response()->json([

            'ok' => true,

            'mensaje' => 'Usuario creado correctamente. Revisa tu correo.',

            'id' => $idUsuario
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([

            'ok' => false,

            'mensaje' => $e->getMessage()
        ], 500);
    }
});

Route::middleware('auth:sanctum')
->get('/usuarios', function () {

    return DB::table('usuarios')
    ->join(
        'personas',
        'usuarios.id_persona',
        '=',
        'personas.id'
    )
    ->select(
        'usuarios.*',
        'personas.nombre',
        'personas.apellido_p',
        'personas.apellido_m',
        'personas.celular'
    )
    ->orderBy('usuarios.id', 'desc')
    ->get();

});


Route::middleware('auth:sanctum')
->put('/usuariosput/{id}', function (
    Request $request,
    $id
) {

    try {

        $usuario = DB::table('usuarios')
        ->where('id', $id)
        ->first();

        if(!$usuario){

            return response()->json([
                'ok' => false,
                'mensaje' => 'Usuario no encontrado'
            ],404);
        }

        DB::table('personas')
        ->where('id', $usuario->id_persona)
        ->update([

            'nombre' => $request->nombre,

            'apellido_p' => $request->apellido_p,

            'apellido_m' => $request->apellido_m,

            'celular' => $request->celular,

            'updated_at' => now()
        ]);

        $datosUsuario = [

            'correo' => $request->correo,

            'admin' => $request->admin,

            'updated_at' => now()

        ];

        if(!empty($request->pass)){

    $datosUsuario['pass'] =
    Hash::make($request->pass);

    DB::table('personal_access_tokens')
        ->where('tokenable_id', $id)
        ->delete();

    event(new Evento(

        [
            'tipo' => 'logout'
        ],

        'logout.' . $id

    ));
\Illuminate\Support\Facades\Log::info(
    'Evento logout enviado para usuario ' . $id
);
}

        DB::table('usuarios')
        ->where('id', $id)
        ->update($datosUsuario);

        return response()->json([

            'ok' => true,

            'mensaje' => 'Usuario actualizado'
        ]);

    } catch (\Exception $e) {

        return response()->json([

            'ok' => false,

            'error' => $e->getMessage(),

            'linea' => $e->getLine(),

            'archivo' => $e->getFile()

        ], 500);
    }
});


Route::middleware('auth:sanctum')
->delete('/usuarios/{id}', function ($id) {

    $usuario = DB::table('usuarios')
    ->where('id', $id)
    ->first();

    if(!$usuario){

        return response()->json([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado'
        ],404);
    }

    DB::table('usuarios')
    ->where('id', $id)
    ->delete();

    DB::table('personas')
    ->where('id', $usuario->id_persona)
    ->delete();

    return response()->json([

        'ok' => true,

        'mensaje' => 'Usuario eliminado'
    ]);

});


/* =========================================
   LOGIN
========================================= */

Route::post('/login', function (Request $request) {

    $usuario = Usuario::where(
        'correo',
        strtolower($request->correo)
    )->first();

    if (!$usuario) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Usuario no encontrado'
        ], 401);
    }

    if (!Hash::check(
        $request->pass,
        $usuario->pass
    )) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Contraseña incorrecta'
        ], 401);
    }

    if (!$usuario->correo_verificado) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Debes verificar tu correo antes de iniciar sesión'
        ], 403);
    }

    $token = $usuario
    ->createToken(
        $request->dispositivo ?? 'web'
    )
    ->plainTextToken;

    return response()->json([

        'ok' => true,

        'token' => $token,

        'usuario' => $usuario
    ]);
});

/* =========================================
   PERFIL
========================================= */

Route::middleware('auth:sanctum')
->get('/perfil', function (Request $request) {

    return response()->json([

        'usuario' => $request->user()
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





Route::get('/correo-prueba', function () {

    $codigo = rand(100000,999999);

    Mail::to(
        'machucac394@gmail.com'
    )->send(
        new Correos($codigo)
    );

    return response()->json([

        'ok' => true,

        'mensaje' => 'Correo enviado'
    ]);
});




Route::post('/verificar-correo', function (Request $request) {

    $usuario = DB::table('usuarios')
        ->where(
            'correo',
            strtolower($request->correo)
        )
        ->first();

    if (!$usuario) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Usuario no encontrado'
        ], 404);
    }

    if (
        $usuario->codigo_verificacion !=
        $request->codigo
    ) {

        return response()->json([

            'ok' => false,

            'mensaje' => 'Código incorrecto'
        ], 400);
    }

    DB::table('usuarios')
        ->where('id', $usuario->id)
        ->update([

            'correo_verificado' => true,

            'codigo_verificacion' => null,

            'updated_at' => now()
        ]);

    return response()->json([

        'ok' => true,

        'mensaje' => 'Correo verificado correctamente'
    ]);
});





Route::middleware('auth:sanctum')
->put('/perfil', function (Request $request) {

    $usuario = $request->user();

    DB::table('personas')
    ->where('id', $usuario->id_persona)
    ->update([

        'nombre' => $request->nombre,

        'apellido_p' => $request->apellido_p,

        'apellido_m' => $request->apellido_m,

        'celular' => $request->celular,

        'updated_at' => now()
    ]);

    $datosUsuario = [

        'correo' => strtolower($request->correo),

        'updated_at' => now()
    ];

    $cambioPassword = false;

    if(!empty($request->pass)){

        $datosUsuario['pass'] =
        Hash::make($request->pass);

        $cambioPassword = true;
    }

    DB::table('usuarios')
    ->where('id', $usuario->id)
    ->update($datosUsuario);

    if($cambioPassword){

        DB::table('personal_access_tokens')
        ->where('tokenable_id', $usuario->id)
        ->delete();
    }

    return response()->json([

        'ok' => true,

        'cerrarSesion' => $cambioPassword,

        'mensaje' => 'Perfil actualizado correctamente'
    ]);
});


/* =========================================
   PERFIL
========================================= */

Route::middleware('auth:sanctum')
->get('/perfil', function (Request $request) {

    $usuario = DB::table('usuarios')
    ->join(
        'personas',
        'usuarios.id_persona',
        '=',
        'personas.id'
    )
    ->where(
        'usuarios.id',
        $request->user()->id
    )
    ->select(

        'usuarios.id',

        'usuarios.correo',

        'usuarios.admin',

        'personas.nombre',

        'personas.apellido_p',

        'personas.apellido_m',

        'personas.celular'
    )
    ->first();

    return response()->json([

        'ok' => true,

        'usuario' => $usuario
    ]);
});
