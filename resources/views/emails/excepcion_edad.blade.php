<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excepción de Edad - Grupo Solmar</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f2f5; font-family: 'Segoe UI', Arial, sans-serif;">

    <!-- Cabecera Azul Oscuro Full Width -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #003366;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <h1 style="margin: 0; font-size: 24px; letter-spacing: 1px;">
                    <span style="color: #ffffff; font-weight: 900;">GRUPO</span> <span style="color: #ffb800; font-weight: 900;">SOLMAR</span>
                </h1>
            </td>
        </tr>
    </table>

    <!-- Contenedor Principal (Tarjeta Blanca) -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f0f2f5; padding-bottom: 40px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 0 0 12px 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 6px solid #ffb800;">

                    <!-- Sección Superior: Ícono y Título -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 20px 40px;">
                            <!-- Ícono Naranja Advertencia -->
                            <div style="display: inline-block; width: 70px; height: 70px; background-color: #f59e0b; border-radius: 50%; text-align: center; line-height: 70px; margin-bottom: 20px;">
                                <span style="color: #ffffff; font-size: 35px; font-weight: bold;">!</span>
                            </div>

                            <h2 style="margin: 0; color: #003366; font-size: 22px; font-weight: 900; letter-spacing: 0.5px;">EXCEPCIÓN DE EDAD REGISTRADA</h2>
                            <p style="margin: 15px 0 0 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                Se ha registrado una <strong>excepción de edad</strong> en la Declaración Jurada<br>
                                de un colaborador. A continuación, los detalles del evento.
                            </p>
                        </td>
                    </tr>

                    <!-- Sección Central: Caja de Detalles -->
                    <tr>
                        <td align="center" style="padding: 10px 40px 20px 40px;">

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius: 6px; overflow: hidden; border: 1px solid #e0e4e8;">
                                <!-- Header de la Caja -->
                                <tr>
                                    <td colspan="2" style="background-color: #004b87; color: #ffffff; padding: 12px 20px; font-size: 12px; font-weight: bold; letter-spacing: 1px;">
                                        DETALLES DE LA EXCEPCIÓN
                                    </td>
                                </tr>

                                <!-- Cuerpo de la Caja -->
                                <tr>
                                    <td width="60%" style="background-color: #f4f6f9; padding: 25px 20px; border-right: 1px dashed #c0c8d0;" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 12px; color: #333333; line-height: 2.2;">
                                            <tr>
                                                <td width="40%" style="color: #666666; font-weight: bold;">NOMBRE:</td>
                                                <td width="60%" style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['nombre'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">DNI:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['dni'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">FECHA NACIMIENTO:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['fecha_nacimiento'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">EDAD CALCULADA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['edad'] }} años</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">USUARIO REGISTRA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['usuario_registro'] }}</td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td width="40%" style="background-color: #f4f6f9; padding: 25px 20px;" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 12px; color: #333333; line-height: 2.2;">
                                            <tr>
                                                <td width="45%" style="color: #666666; font-weight: bold;">TIPO:</td>
                                                <td width="55%" style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['tipo_excepcion'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">EDAD MÍNIMA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['edad_minima'] }} años</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">EDAD MÁXIMA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['edad_maxima'] }} años</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">AUTORIZADO POR:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['usuario_autorizador'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">FECHA AUTORIZ.:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['fecha_autorizacion'] }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                </table>

                <!-- Footer Externo -->
                <table width="600" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" style="padding: 30px 20px;">
                            <div style="color: #b0b8c0; font-size: 10px; font-weight: bold; margin-bottom: 15px; letter-spacing: 1px;">GRUPO SOLMAR</div>
                            <p style="color: #88929c; font-size: 11px; margin: 0; line-height: 1.6;">
                                Este es un mensaje automático generado por el Sistema de Recursos Humanos.<br>
                                Por favor, no responda a esta dirección de correo electrónico.<br>
                                © {{ date('Y') }} GRUPO SOLMAR. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
