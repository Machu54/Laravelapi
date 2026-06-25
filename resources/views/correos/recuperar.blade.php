<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
</head>

<body style="
    font-family: Arial;
    background:#f4f6f9;
    padding:40px;
">

    <div style="
        max-width:500px;
        margin:auto;
        background:white;
        padding:40px;
        border-radius:20px;
        text-align:center;
        box-shadow:0 5px 20px rgba(0,0,0,.15);
    ">

        <h1 style="color:#6a84e6;">
            NovaHabitat
        </h1>

        <p>
            Has solicitado recuperar tu contraseña.
        </p>

        <h1 style="
            font-size:45px;
            color:#98C8E9;
            letter-spacing:8px;
        ">
            {{ $codigo }}
        </h1>

        <p>
            Este código expirará en 10 minutos.
        </p>

    </div>

</body>

</html>
