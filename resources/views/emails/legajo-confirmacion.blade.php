<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Legajo Añadido</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                
                <!-- 🔹 HEADER / LOGO AREA -->
                <tr>
                    <td style="padding:40px 40px 20px 40px; text-align: center;">
                        <h1 style="margin:0; color:#1e293b; font-size:24px; font-weight:700; letter-spacing:-0.025em;">
                            {{ $nombre_empresa }}
                        </h1>
                        <div style="height:4px; width:40px; background:#3b82f6; margin:16px auto 0; border-radius:2px;"></div>
                    </td>
                </tr>

                <!-- 🔹 CONTENT AREA -->
                <tr>
                    <td style="padding:20px 40px 40px 40px;">
                        <p style="color:#475569; font-size:16px; line-height:1.6; margin-bottom:24px;">
                            Hola <strong>Personal de {{ $nombre_empresa }}</strong>,
                        </p>

                        <p style="color:#475569; font-size:16px; line-height:1.6; margin-bottom:24px;">
                            Se ha añadido un nuevo legajo al personal del cargo <strong>{{ $nombre_cargo }}</strong>.
                        </p>

                        <!-- 🔹 LEGAJO INFO BOX -->
                        <div style="background:#f1f5f9; border-radius:12px; padding:24px; border-left:4px solid #3b82f6;">
                            <p style="margin:0; font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600; letter-spacing:0.05em;">
                                El nombre del folio es:
                            </p>
                            <p style="margin:8px 0 0; font-size:18px; color:#0f172a; font-weight:700;">
                                {{ $nombre_folio }}
                            </p>
                        </div>

                        <p style="color:#475569; font-size:16px; line-height:1.6; margin-top:32px;">
                            Este nuevo legajo ya se encuentra disponible para su consulta y gestión en el panel correspondiente.
                        </p>

                        <p style="margin-top:40px; font-size:14px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:24px;">
                            Este es un mensaje automático, por favor no respondas a este correo.
                        </p>

                        <p style="margin-top:24px; font-size:14px; color:#475569;">
                            Atentamente,<br>
                            <strong>Sistema de Gestión</strong><br>
                            {{ $nombre_empresa }}
                        </p>
                    </td>
                </tr>

                <!-- 🔹 FOOTER -->
                <tr>
                    <td style="background:#f8fafc; padding:24px; text-align:center;">
                        <p style="margin:0; font-size:12px; color:#94a3b8;">
                            &copy; {{ date('Y') }} {{ $nombre_empresa }}. Todos los derechos reservados.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
