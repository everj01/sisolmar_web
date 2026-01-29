<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Compromiso</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header-table, .content-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0 10px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 10px;
        }
        .firma-table {
            width: 100%;
            margin-top: 30px;
        }
        .firma-table td {
            text-align: center;
            vertical-align: top;
            padding-top: 30px;
        }
        .footer-text {
            text-align: right;
            margin-top: 20px;
        }
        .logo {
            width: 100px;
        }
        ul {
            margin-top: 0;
            margin-bottom: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td rowspan="2" style="text-align:center; width: 18%;">
                <img src="{{ public_path('images/grupo_solmar.png') }}" class="logo">
            </td>
            <td style="width: 35%;">SOLMAR SECURITY S.A.C.</td>
            <td style="width: 22%;">CÓDIGO: FAS-04-002</td>
            <td style="width: 25%;">PRIMERA EDICIÓN</td>
        </tr>
        <tr>
            <td>Formato: Acta de Compromiso<br>Área de Influencia: Jefatura de Recursos Humanos</td>
            <td>FECHA APROBACIÓN:<br>02 Marzo 2010</td>
            <td>FECHA DE REVISIÓN:<br>23 Enero 2013<br>Pág. 1 de 1</td>
        </tr>
    </table>

    <div class="title">ACTA DE COMPROMISO</div>

    <p>
        Yo, <strong>{{ $datos['persona'] }}</strong>, identificado con DNI N° <strong>{{ $datos['nroDoc'] }}</strong>, trabajador de la Empresa Solmar Security S.A.C., desempeñando el cargo de <strong>{{ $datos['cargo'] }}</strong>, por el presente documento declaro conocer lo siguiente:
    </p>

    <p class="section-title">1. DISPONIBILIDAD PARA EL TRABAJO</p>
    <p>Como {{ $datos['cargo'] }} estaré disponible para acudir a cumplir labores en cualquiera de los puestos al que la empresa me destaque, para lo cual será necesario únicamente recibir la comunicación por cualquier medio del supervisor y/o representante de la empresa a cargo.</p>

    <p class="section-title">2. RECOJO DE BOLETAS DE PAGO</p>
    <p>
        Me comprometo a recoger mis boletas de pago mensualmente, a más tardar el día 10 del mes siguiente al pago de haberes.
        En caso de no recogerla, acepto se me imponga una sanción de acuerdo a las normas internas de la empresa (RIT).
        <br><br>
        Acepto que mis Boletas de Haberes sean impresas por el sistema informático de mi empleador y que la firma del Representante Legal sea digitalizada.
    </p>

    <p class="section-title">3. NOTIFICACIONES</p>
    <p>
        Acepto que se me notifique al correo electrónico registrado en mi Declaración Jurada N° 00334-2025 , cumpliendo con el Régimen de Notificaciones establecido en la Ley N° 27444, artículo 20, numeral 20.1.2 de fecha 21 de marzo del 2001. Recibiendo por este medio documentos, imágenes y cualquier otro archivo electrónico que la empresa estime conveniente para una mejor relación laboral.
    </p>

    <p class="section-title">4. DISCIPLINA</p>
    <p>Acepto conocer el Reglamento Interno de Trabajo (RIT), así como las sanciones y/o penalidades de acuerdo al régimen disciplinario de la empresa.</p>

    <p class="section-title">5. DESCUENTOS</p>
    <ul>
        <li>Acepto que la Empresa me descuente por todos los gastos que genere la obtención del Carné SUCAMEC, Licencia de Armas, Exámenes Médicos y otros documentos que fueran necesarios para el desarrollo de mis labores como {{ $datos['cargo'] }} .</li>
        <li>Acepto que la Empresa me descuente los gastos que se originen por la REPOSICIÓN del Carné SUCAMEC, Licencia de Armas, Fotochek y otros documentos cuya pérdida haya ocurrido bajo mi responsabilidad.</li>
        <li>Acepto que la Empresa me descuente cada año por la asignación de 01 par de Borceguíes y las prendas y/o accesorios adicionales que yo solicite.</li>
        <li>Acepto que la Empresa me descuente en caso de pérdida, el vestuario y accesorios que se me asignados; según la tabla de precios vigente de la Empresa.</li>
        <li>La Empresa me descontará por la pérdida de armamento, equipos de telecomunicación y/o accesorios de seguridad, según el grado de responsabilidad que se determine y de acuerdo a la tabla de descuentos de la Empresa.</li>
        <li>Acepto que la Empresa me descuente por la incorrecta prestación del servicio de vigilancia que se me encarga y por el que se me paga, de acuerdo a norma interna.</li>
        <li>Acepto que la Empresa me descuente al término del vínculo laboral lo que corresponda a vestuario, accesorios y/o equipos que se me haya asignado; siempre que no los devuelva en buenas condiciones, condición que incluye el correcto lavado de las prendas que se devuelven y sus partes componentes.</li>
    </ul>

    <p class="footer-text">Chimbote, {{ \Carbon\Carbon::now()->translatedFormat('j \d\e F \d\e\l Y') }}</p>

    <table class="firma-table">
        <tr>
            <td>
                <img src="{{ $huella }}" height="100"><br>
                Huella Digital
            </td>
            <td>
                <img src="{{ $firma }}" height="60"><br>
                <strong>{{ $datos['persona'] }}</strong><br>
                DNI: {{ $datos['nroDoc'] }}<br>
                {{ $datos['cargo'] }}
            </td>
        </tr>
    </table>

</body>
</html>
