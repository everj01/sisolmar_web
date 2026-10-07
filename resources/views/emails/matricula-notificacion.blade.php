<html lang="es">
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">

    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); overflow:hidden;">
                    
                    <tr>
                        <td style="background-color:#004080; padding:20px; text-align:center;">
                            <h1 style="margin:0; font-size:20px; color:#ffffff;">Notificación de Matrícula</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">
                            <p style="margin:0 0 15px;">Estimado/a <strong>{{ $personal->NOMB_1 }} {{ $personal->APEL_1 }} {{ $personal->APEL_2 }}</strong>,</p>

                            <p style="margin:0 0 15px;">
                                Nos complace informarle que ha sido matriculado/a exitosamente en el siguiente curso:
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                                <tr>
                                    <td width="6" style="background-color:#004080;"></td>
                                    <td style="background-color:#f8f9fa; padding:15px; padding-bottom:20px;">
                                        <p style="margin:0 0 8px;"><strong style="color:#004080;">Curso:</strong> {{ $curso->nombre }}</p>
                                        <p style="margin:0 0 8px;"><strong style="color:#004080;">Código:</strong> {{ $curso->codigo_curso }}</p>
                                        <p style="margin:0;"><strong style="color:#004080;">Fecha de matrícula:</strong> {{ \Carbon\Carbon::now()->setTimezone('America/Lima')->format('d/m/Y H:i') }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:15px 0;">Su matrícula ha sido registrada correctamente. Pronto recibirá más información sobre las fechas de inicio y el material del curso.</p>
                            <p style="margin:0 0 15px;">¡Le deseamos mucho éxito en su capacitación!</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f0f0f0; padding:20px; text-align:center; font-size:12px; color:#555555;">
                            <p style="margin:0 0 8px;">Este es un correo automático, por favor no responder a este mensaje.</p>
                            © {{ date('Y') }} - Sistema de Matrículas. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>