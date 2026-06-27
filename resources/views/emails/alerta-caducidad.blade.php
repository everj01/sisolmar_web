<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de documentos por vencer</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
    <tr>
        <td align="center">
            <table width="650" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border-radius:8px; overflow:hidden;">

                <!-- 🔹 BANNER -->
                <tr>
                    <td>
                        <img src="{{ $message->embed(public_path('images/banners/banner_folios_pendientes.jpeg')) }}"
                             width="100%"
                             style="display:block;">
                    </td>
                </tr>

                <!-- CONTENIDO -->
                <tr>
                    <td style="padding:25px;">

                        <h2 style="margin-top:0; color:#1f2937;">
                            📋 Reporte de Documentos Pendientes
                        </h2>

                        <p style="color:#374151; font-size:14px; line-height:1.6;">
                            Estimado equipo de <strong>Recursos Humanos</strong>,
                        </p>

                        <p style="color:#374151; font-size:14px; line-height:1.6;">
                            Se adjunta el reporte actualizado de los colaboradores que
                            cuentan con folios pendientes.
                        </p>

                        <p style="color:#374151; font-size:14px; line-height:1.6;">
                            Por favor revisar el archivo adjunto y gestionar las
                            acciones correspondientes dentro de los plazos establecidos.
                        </p>

                        <p style="margin-top:20px; font-size:13px; color:#6b7280;">
                            Este es un mensaje automático generado por el sistema.
                        </p>

                        <p style="margin-top:25px; font-size:13px; color:#374151;">
                            Atentamente,<br>
                            <strong>Sistema de Gestión</strong><br>
                            SISOLMAR
                        </p>

                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
