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

@php $sinCaratula = $sinCaratula ?? false; @endphp

@if(!$sinCaratula)
    {{-- ===== CON CARÁTULA (comportamiento actual) ===== --}}
    @foreach ($personas as $pers)
        <div class="container">
            <img src="{{ public_path('images/gruposolmar/caratula_legajo.jpg') }}" class="fondo-pdf" alt="Carátula">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2;">
                <div style="position: absolute; top: 48%; left: 31%; font-size: 22px; font-weight: bold; color: #000;">{{ $pers['persona'] }}</div>
                <div style="position: absolute; top: 56%; left: 31%; font-size: 22px; font-weight: bold; color: #000;">{{ $pers['cargo'] }}</div>
                <div style="position: absolute; top: 63%; left: 31%; font-size: 22px; font-weight: bold; color: #000;">{{ $pers['sucursal'] }}</div>
                <div style="position: absolute; top: 70.5%; left: 31%; font-size: 22px; font-weight: bold; color: #000;">FILE N° {{ $pers['codPersonal'] }}</div>
                <div style="position: absolute; top: 94.7%; left: 85%; font-size: 18px; font-weight: bold; color: #000;">
                    {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                </div>
            </div>
        </div>
        <div style="page-break-after: always;"></div>

        @php $documentosMostrados = []; @endphp

        @foreach ($items as $index => $item)
            @if ($item['codPersonal'] === $pers['codPersonal'])
                @if ($item['es_formato'] === '0')
                    @if (!in_array($item['documento'], $documentosMostrados))
                        <h4 style="text-align: center;">{{ $item['documento'] }}</h4>
                        @php $documentosMostrados[] = $item['documento']; @endphp
                    @endif
                    <div class="pagina-imagen">
                        <img src="{{ $item['ruta'] }}" alt="Imagen de {{ $item['documento'] }}"
                            style="max-width: {{ $item['ancho'] }}; max-height: 1250px; display: block; margin: 0 auto; object-fit: contain; margin-bottom: 10px;">
                    </div>
                    @php
                        $esUltimaImagen = true;
                        for ($i = $index + 1; $i < count($items); $i++) {
                            if ($items[$i]['codPersonal'] === $pers['codPersonal'] && $items[$i]['documento'] === $item['documento'] && $items[$i]['es_formato'] === '0') {
                                $esUltimaImagen = false;
                                break;
                            }
                        }
                    @endphp
                    @if ($esUltimaImagen)
                        <div style="page-break-after: always;"></div>
                    @endif
                @elseif ($item['es_formato'] === '1')
                    @php $vista = $item['nombre_vista']; @endphp
                    @if (view()->exists($vista))
                        @include($vista, ['datos' => $item['datos'], 'firma' => $item['firma'], 'huella' => $item['huella']])
                    @else
                        <p>Vista no encontrada para {{ $item['documento'] }}</p>
                    @endif
                    <div style="page-break-after: always;"></div>
                @endif
            @endif
        @endforeach

        <div style="page-break-after: always;"></div>
    @endforeach

@else
    {{-- ===== SIN CARÁTULA: 2 personas por página, solo nombre + imagen ===== --}}
    @php
        $personasArr = $personas;
        $totalPers    = count($personasArr);
    @endphp

    @for($pi = 0; $pi < $totalPers; $pi += 2)
        @php
            $p1 = $personasArr[$pi];
            $p2 = $personasArr[$pi + 1] ?? null;

            $imgs1 = array_values(array_filter($items, fn($x) =>
                $x['codPersonal'] === $p1['codPersonal'] &&
                ($x['es_formato'] ?? null) === '0' &&
                !empty($x['ruta'])
            ));
            $fmts1 = array_values(array_filter($items, fn($x) =>
                $x['codPersonal'] === $p1['codPersonal'] &&
                ($x['es_formato'] ?? null) === '1'
            ));

            $imgs2 = $p2 ? array_values(array_filter($items, fn($x) =>
                $x['codPersonal'] === $p2['codPersonal'] &&
                ($x['es_formato'] ?? null) === '0' &&
                !empty($x['ruta'])
            )) : [];
            $fmts2 = $p2 ? array_values(array_filter($items, fn($x) =>
                $x['codPersonal'] === $p2['codPersonal'] &&
                ($x['es_formato'] ?? null) === '1'
            )) : [];
        @endphp

        {{-- Fila con 1 o 2 personas --}}
        <div style="display: flex; gap: 16px; page-break-inside: avoid;">

            <div style="flex: 0 0 48%; text-align: center;">
                <p style="font-size: 12px; font-weight: bold; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; margin-bottom: 10px; color: #1e3a5f;">
                    {{ $p1['persona'] }}
                </p>
                @foreach($imgs1 as $img)
                    <img src="{{ $img['ruta'] }}" style="max-width: 100%; max-height: 380px; object-fit: contain; display: block; margin: 0 auto 6px;">
                @endforeach
            </div>

            @if($p2)
            <div style="flex: 0 0 48%; text-align: center;">
                <p style="font-size: 12px; font-weight: bold; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; margin-bottom: 10px; color: #1e3a5f;">
                    {{ $p2['persona'] }}
                </p>
                @foreach($imgs2 as $img)
                    <img src="{{ $img['ruta'] }}" style="max-width: 100%; max-height: 380px; object-fit: contain; display: block; margin: 0 auto 6px;">
                @endforeach
            </div>
            @endif

        </div>
        <div style="page-break-after: always;"></div>

        {{-- Formatos de p1 (cada uno página completa) --}}
        @foreach($fmts1 as $fmt)
            @php $vista = $fmt['nombre_vista'] ?? null; @endphp
            @if($vista && view()->exists($vista))
                @include($vista, ['datos' => $fmt['datos'], 'firma' => $fmt['firma'], 'huella' => $fmt['huella']])
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach

        {{-- Formatos de p2 (cada uno página completa) --}}
        @foreach($fmts2 as $fmt)
            @php $vista = $fmt['nombre_vista'] ?? null; @endphp
            @if($vista && view()->exists($vista))
                @include($vista, ['datos' => $fmt['datos'], 'firma' => $fmt['firma'], 'huella' => $fmt['huella']])
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach
    @endfor

@endif





</body>

</html>