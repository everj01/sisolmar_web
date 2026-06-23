<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte PDF</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: justify;
            text-justify: inter-word;
        }

        .caratula {
            text-align: center;
            margin-top: 150px;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .anexos {
            page-break-before: always;
            text-align: center;
        }

        .anexos img {
            max-width: 100%;
            margin-bottom: 30px;
            page-break-after: always;
        }

        #title_caratula {
            font-size: 55px;
        }

        .container {
            position: relative;
            width: 100%;
            /*height: 297mm;  A4 vertical */
            height: 320mm;
            page-break-after: always;
            overflow: hidden;
        }

        .fondo-pdf {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .logo_solmar {
            width: 500px;
            display: block;
            margin: 50px auto;
        }

        .datos_personal {
            display: inline-block;
            border: 1px solid black;
            border-radius: 15px;
            padding: 15px;
            margin: 0 auto;
            text-align: center;
        }

        .contenido_caratula {
            position: relative;
            z-index: 2;
            padding: 40px;
            text-align: center;
            color: black;
        }

        .wrapper-aviso {
            margin-top: 15px;
            position: relative;
        }

        .aviso {
            box-sizing: border-box;
            float: right;
            width: 70%;
            padding: 0 30px;
            margin-left: auto;
            margin-right: auto;
            text-align: justify;
            background-color: #000;
            color: white;
        }

        .aviso p {
            margin-top: 16px;
            margin-bottom: 16px;
        }


        .info_personal {
            padding: 15px;
            background: #BCBCBC;
            color: #000;
        }

        h3.fecha_emision {
            text-align: right;
            width: 100%;
            margin-top: 10px;
            /* Opcional */
        }

        .wrapper-aviso::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
 <body>
      @foreach ($personas as $pers)

          <div class="container" style="page-break-after: auto;">
              <img src="{{ public_path('images/gruposolmar/caratula_legajo.jpg') }}" class="fondo-pdf"
  alt="Carátula">

      <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2;">
          <div style="position: absolute; top: 48%; left: 31%; font-size: 22px; font-weight: bold;
  color: #000;">{{ $pers['persona'] }}</div>
          <div style="position: absolute; top: 56%; left: 31%; font-size: 22px; font-weight: bold;
  color: #000;">{{ $pers['cargo'] }}</div>
          <div style="position: absolute; top: 63%; left: 31%; font-size: 22px; font-weight: bold;
  color: #000;">{{ $pers['sucursal'] }}</div>
          <div style="position: absolute; top: 70.5%; left: 31%; font-size: 22px; font-weight: bold;
  color: #000;">FILE N° {{ $pers['codPersonal'] }}</div>

           <div style="position: absolute; top: 94.7%; left: 85%; font-size: 18px; font-weight: bold;
  color: #000;">
                      {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                  </div>
              </div>
          </div>

          {{-- @if (!$loop->last)
              <div style="page-break-after: always;"></div>
          @endif --}}

      @endforeach
  </body>

</html>