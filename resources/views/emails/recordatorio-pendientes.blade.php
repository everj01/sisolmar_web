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
                              <h1 style="margin:0; font-size:20px; color:#ffffff;">Cursos Pendientes</h1>
                          </td>
                      </tr>

                      <tr>
                          <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">
                              <p style="margin:0 0 15px;">Estimado/a <strong>{{ $full_name }}</strong>,</p>

                              <p style="margin:0 0 15px;">
                                  Tiene los siguientes curso(s) pendiente(s) por iniciar en la plataforma:
                              </p>

                              <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                                  @foreach($cursos_pendientes as $curso)
                                  <tr>
                                      <td width="6" style="background-color:#004080;"></td>
                                      <td style="background-color:#f8f9fa; padding:12px 15px; border-bottom:1px solid #e9ecef;">
                                          <p style="margin:0; font-size:14px; color:#333;">
                                              <strong style="color:#004080;">{{ $loop->iteration }}.</strong>
                                              {{ $curso['nombre'] }}
                                          </p>
                                      </td>
                                  </tr>
                                  @endforeach
                              </table>

                              <p style="margin:15px 0;">Le informamos que se ha ampliado el plazo para completar la(s) capacitación(es) que mantiene pendiente(s). Le solicitamos finalizar sus cursos dentro del periodo otorgado para mantener sus capacitaciones al día.</p>
                              <p style="margin:0 0 15px;">Gracias por su compromiso. ¡Le deseamos mucho éxito!</p>
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