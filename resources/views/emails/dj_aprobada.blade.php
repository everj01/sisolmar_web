<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobación DJ - Grupo Solmar</title>
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
                            <!-- Ícono Verde -->
                            <div style="display: inline-block; width: 70px; height: 70px; background-color: #00a877; border-radius: 50%; text-align: center; line-height: 70px; margin-bottom: 20px;">
                                <span style="color: #ffffff; font-size: 35px; font-weight: bold;">✓</span>
                            </div>
                            
                            <h2 style="margin: 0; color: #003366; font-size: 22px; font-weight: 900; letter-spacing: 0.5px;">ACTUALIZACIÓN EXITOSA</h2>
                            <p style="margin: 15px 0 0 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                Su <strong>Declaración Jurada (DJ)</strong> ha sido validada y procesada<br>
                                correctamente en nuestra base de datos. A continuación, los detalles<br>
                                de su registro.
                            </p>
                        </td>
                    </tr>

                    <!-- Sección Central: Caja de Detalles (El diseño de tu imagen) -->
                    <tr>
                        <td align="center" style="padding: 10px 40px 20px 40px;">
                            
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius: 6px; overflow: hidden; border: 1px solid #e0e4e8;">
                                <!-- Header Azul de la Caja -->
                                <tr>
                                    <td colspan="2" style="background-color: #004b87; color: #ffffff; padding: 12px 20px; font-size: 12px; font-weight: bold; letter-spacing: 1px;">
                                        DETALLES DEL COLABORADOR
                                    </td>
                                </tr>
                                
                                <!-- Cuerpo Gris de la Caja (2 Columnas) -->
                                <tr>
                                    <!-- Columna Izquierda (Datos) -->
                                    <td width="60%" style="background-color: #f4f6f9; padding: 25px 20px; border-right: 1px dashed #c0c8d0;" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 12px; color: #333333; line-height: 2.2;">
                                            <tr>
                                                <td width="35%" style="color: #666666; font-weight: bold;">NOMBRE:</td>
                                                <td width="65%" style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['nombre'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">DNI:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ $datosCorreo['dni'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">FECHA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ explode(' ', $datosCorreo['fecha'])[0] ?? $datosCorreo['fecha'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666666; font-weight: bold;">HORA:</td>
                                                <td style="font-weight: 900; color: #1a202c;">{{ explode(' ', $datosCorreo['fecha'])[1] ?? '' }} {{ explode(' ', $datosCorreo['fecha'])[2] ?? '' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                    
                                    <!-- Columna Derecha (Aprobado) -->
                                    <td width="40%" style="background-color: #f4f6f9; padding: 25px 20px; text-align: center;" valign="middle">
                                        <div style="color: #666666; font-size: 10px; font-weight: bold; margin-bottom: 10px; letter-spacing: 1px;">ESTADO DE DJ</div>
                                        <div style="border: 2px solid #00a877; color: #00a877; font-size: 16px; font-weight: 900; padding: 10px; letter-spacing: 1px; background-color: #ffffff;">
                                            APROBADO
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>

                    <!-- ¡ESTO ES LO NUEVO! Sección de Cambios Registrados -->
                    <tr>
                        <td style="padding: 0px 40px 40px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border: 1px solid #e0e4e8; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #003366; font-size: 13px; font-weight: bold; border-bottom: 1px solid #eeeeee; padding-bottom: 8px;">
                                            DATOS ACTUALIZADOS EN ESTA SESIÓN:
                                        </h3>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 13px; color: #4a4a4a; line-height: 1.8;">
                                            @if(isset($datosCorreo['cambios']) && count($datosCorreo['cambios']) > 0)
                                                @foreach($datosCorreo['cambios'] as $cambio)
                                                <tr>
                                                    <td width="20" valign="top" style="color: #00a877; font-weight: bold;">✓</td>
                                                    {{-- Usamos las llaves con exclamación para renderizar el tag strong que enviamos desde el controlador --}}
                                                    <td>{!! $cambio !!}</td>
                                                </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td width="20" valign="top" style="color: #00a877; font-weight: bold;">✓</td>
                                                    <td>Revisión y confirmación general de datos (No hubieron modificaciones).</td>
                                                </tr>
                                            @endif
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