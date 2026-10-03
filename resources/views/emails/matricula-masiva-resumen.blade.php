<html lang="es">

  <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">

      <table align="center" width="100%" cellpadding="0" cellspacing="0"
          style="background-color:#f4f4f4; padding:20px 0;">
          <tr>
              <td align="center">
                  <table width="600" cellpadding="0" cellspacing="0"
                      style="background-color:#ffffff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); overflow:hidden;">

                      <tr>
                          <td style="background-color:#004080; padding:20px; text-align:center;">
                              <h1 style="margin:0; font-size:20px; color:#ffffff;">Matrícula masiva finalizada</h1>
                          </td>
                      </tr>

                      <tr>
                          <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">
                              <p style="margin:0 0 15px;">El proceso de matrícula masiva del curso
                                  <strong>{{ $nombreCurso }}</strong> (programación {{ $programacion }}, origen: {{ $origen }}) ha terminado:
                              </p>

                              <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                                  <tr>
                                      <td width="6" style="background-color:#004080;"></td>
                                      <td style="background-color:#f8f9fa; padding:15px; padding-bottom:20px;">
                                          <p style="margin:0 0 8px;"><strong style="color:#004080;">Personal procesado:</strong>
                                              {{ $total }}
                                          </p>
                                          <p style="margin:0 0 8px;"><strong style="color:#004080;">Matriculados:</strong>
                                              {{ $enviados }}
                                          </p>
                                          <p style="margin:0;"><strong style="color:#004080;">Fallidos:</strong>
                                              {{ $fallidos }}
                                          </p>
                                      </td>
                                  </tr>
                              </table>

                              @if ($fallidos > 0)
                              <p style="margin:15px 0;">Se registraron fallos durante el proceso. Revise los logs del sistema para el detalle.</p>
                              @endif
                          </td>
                      </tr>

                      <tr>
                          <td style="background-color:#f0f0f0; padding:20px; text-align:center; font-size:12px; color:#555555;">
                              <p style="margin:0 0 8px;">Este es un correo automático, por favor no responder a este mensaje.</p>
                              &copy; {{ date('Y') }} - Sistema de Matrículas. Todos los derechos reservados.
                          </td>
                      </tr>
                  </table>
              </td>
          </tr>
      </table>

  </body>

  </html>
