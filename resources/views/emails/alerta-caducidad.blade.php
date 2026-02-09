<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alerta de documentos por vencer</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border-radius:8px; padding:25px;">

                <!-- HEADER -->
                <tr>
                    <td style="padding-bottom:15px;">
                        <h2 style="margin:0; color:#1f2937;">
                            ⚠️ Alerta de vencimiento de documentos
                        </h2>
                    </td>
                </tr>

                <!-- SALUDO -->
                <tr>
                    <td style="color:#374151; font-size:15px; padding-bottom:10px;">
                        Hola <strong>{{ $nombre_personal }}</strong>,
                    </td>
                </tr>

                <tr>
                    <td style="color:#374151; font-size:14px; padding-bottom:15px;">
                        Te informamos que los siguientes documentos se encuentran próximos a vencer:
                    </td>
                </tr>

                <!-- TABLA DOCUMENTOS -->
                <tr>
                    <td>

                        <table width="100%" cellpadding="0" cellspacing="0" style="
                            border-collapse: collapse;
                            font-family: Arial, sans-serif;
                            font-size: 14px;
                            margin-top: 15px;
                        ">
                            <thead>
                                <tr>
                                    <th style="background-color:#0d6efd;color:#ffffff;padding:10px;border:1px solid #dddddd;">
                                        Documento
                                    </th>
                                    <th style="background-color:#0d6efd;color:#ffffff;padding:10px;border:1px solid #dddddd;">
                                        Fecha de caducidad
                                    </th>
                                    <th style="background-color:#0d6efd;color:#ffffff;padding:10px;border:1px solid #dddddd;">
                                        Días restantes
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documentos as $doc)
                                    <tr style="background-color:#f8f9fa;">
                                        <td style="padding:8px;border:1px solid #dddddd;">
                                            {{ $doc['nombre'] }}
                                        </td>
                                        <td style="
                                            padding:8px;
                                            border:1px solid #dddddd;
                                            color:#dc3545;
                                            font-weight:bold;
                                        ">
                                            {{ $doc['fecha_caducidad'] }}
                                        </td>
                                        <td style="
                                            padding:8px;
                                            border:1px solid #dddddd;
                                            font-weight:bold;
                                            color: {{ $doc['dias_restantes'] <= 5 ? '#dc3545' : '#198754' }};
                                        ">
                                            {{ $doc['dias_restantes'] }} días
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </td>
                </tr>

                <!-- MENSAJE FINAL -->
                <tr>
                    <td style="padding-top:20px; font-size:14px; color:#374151;">
                        Te recomendamos realizar la renovación correspondiente antes de la fecha indicada.
                    </td>
                </tr>

                <tr>
                    <td style="padding-top:10px; font-size:12px; color:#6b7280;">
                        Si alguno de estos documentos ya fue actualizado, puedes ignorar este mensaje.
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="padding-top:25px; font-size:13px; color:#374151;">
                        Atentamente,<br>
                        <strong>Área de Recursos Humanos</strong><br>
                        {{ $nombre_empresa }}
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
