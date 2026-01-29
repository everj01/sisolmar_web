<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte PDF</title>

    <style>
        body { font-family: Arial, sans-serif; text-align: justify; text-justify: inter-word;}
        .caratula {
            text-align: center;
            margin-top: 150px;
        }
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
        .tabla th, .tabla td {
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
        #title_caratula{
            font-size: 55px;
        }
        .container{
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
        .wrapper-aviso{
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


        .info_personal{
            padding: 15px;
            background: #BCBCBC;
            color: #000;
        }
        h3.fecha_emision {
            text-align: right;
            width: 100%;
            margin-top: 10px; /* Opcional */
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
        @php
            $documentosMostrados = [];
        @endphp

        <div class="container">
            <img src="{{ public_path('images/gruposolmar/fondo_caratula.png') }}" class="fondo-pdf" alt="Fondo">

            <div class="contenido_caratula ">
                <img class="logo_solmar" src="{{ public_path('images/gruposolmar/banner_security.png') }}" alt="LogoSolmar">

                <div class="datos_personal">
                    <h1 id="title_caratula">FILE CONTROL</h1>
                    <h2>MODULO DE LEGAJOS ELECTRONICOS</h2>
                    <h3>SISOLMAR - SISTEMA INTEGRADO DE SOLMAR</h3>

                    <img src="{{ public_path('images/gruposolmar/logo_5_normas.png') }}" alt="LogoSolmar" style="width: 105px;">

                    <h2 class="info_personal"><i class="fa fa-user" aria-hidden="true"></i>{{ $pers['persona'] }}</h2>
                    <h2 class="info_personal">SOLMAR {{ $pers['sucursal'] }}</h2>
                    <h2 class="info_personal">{{ $pers['cargo'] }}</h2>
                    <h2 class="info_personal">FILE ELECTRONICO N° {{ $pers['codPersonal'] }}</h2>
                </div>

                <div class="wrapper-aviso">
                    <div class="aviso">
                        <p>Este File Electrónico pertenece a la base de datos del SISOLMAR siendo la Jefe de RRHH la responsable de su custodia y actualización.</p>
                        <p>No será impreso, salvo excepciones previamente autorizadas por la Jefe de RRHH</p>
                        <p>Los datos personales de este file electrónico, están protegidos según lo estipulado en la Ley N° 29733 - Ley de Portección de Datos Personales</p>
                    </div>
                </div>
                <h3 class="fecha_emision">FECHA DE EMISION: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</h3>
            </div>
        </div>

        <div style="page-break-after: always;"></div>



        @php
            $documentosMostrados = [];
        @endphp

        @foreach ($items as $index => $item)

            @if ($item['codPersonal'] === $pers['codPersonal'])

                {{-- Si es imagen --}}
                @if ($item['es_formato'] === '0')
                    {{-- Mostrar título del documento solo una vez --}}
                    @if (!in_array($item['documento'], $documentosMostrados))
                        <h4 style="text-align: center;">{{ $item['documento'] }}</h4>
                        @php
                            $documentosMostrados[] = $item['documento'];
                        @endphp
                    @endif

                    <div class="pagina-imagen">
                        <img src="{{ $item['ruta'] }}" alt="Imagen de {{ $item['documento'] }}"
                        style="max-width: {{ $item['ancho'] }}; max-height: 1250px; display: block; margin: 0 auto; object-fit: contain; margin-bottom: 10px;">
                    </div>

                    {{-- Verificar si es la última imagen del documento para aplicar salto de página --}}
                    @php
                        $esUltimaImagen = true;
                        for ($i = $index + 1; $i < count($items); $i++) {
                            if (
                                $items[$i]['codPersonal'] === $pers['codPersonal'] &&
                                $items[$i]['documento'] === $item['documento'] &&
                                $items[$i]['es_formato'] === '0'
                            ) {
                                $esUltimaImagen = false;
                                break;
                            }
                        }
                    @endphp

                    @if ($esUltimaImagen)
                        <div style="page-break-after: always;"></div>
                    @endif

                {{-- Si es formato --}}
                @elseif ($item['es_formato'] === '1')
                    @php
                        $vista = $item['nombre_vista'];
                    @endphp

                    @if (view()->exists($vista))
                        @include($vista, ['datos' => $item['datos'], 'firma' => $item['firma'], 'huella' => $item['huella']])
                    @else
                        <p>Vista no encontrada para {{ $item['documento'] }}</p>
                    @endif

                    {{-- Como solo hay un formato por documento, aplicamos salto de página directamente --}}
                    <div style="page-break-after: always;"></div>
                @endif

            @endif

        @endforeach




        <div style="page-break-after: always;"></div>

    @endforeach





</body>
</html>
