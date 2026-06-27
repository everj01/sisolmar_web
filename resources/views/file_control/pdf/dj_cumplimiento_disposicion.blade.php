<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Declaración Jurada de Cumplimiento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 40px;
            line-height: 1.5;
        }
        .header-table, .firma-table {
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
            font-size: 14px;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .firma-table td {
            text-align: center;
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
            <td style="width: 22%;">CÓDIGO: FAS-04-003</td>
            <td style="width: 25%;">PRIMERA EDICIÓN</td>
        </tr>
        <tr>
            <td>Formato: Declaración Jurada de Cumplimiento de Disposiciones<br>Área de Influencia: Jefatura de Recursos Humanos</td>
            <td>FECHA APROBACIÓN:<br>02 Marzo 2010</td>
            <td>FECHA DE REVISIÓN:<br>N/A<br>Pág. 1 de 1</td>
        </tr>
    </table>

    <div class="title">DECLARACIÓN JURADA DE CUMPLIMIENTO DE DISPOSICIONES</div>

    <p>
        Yo, <strong>JORGE ARTURO ALVA CAYETANO</strong>, identificado con DNI N° <strong>32868199</strong> trabajador de la Empresa Solmar Security S.A.C., desempeñando el cargo de <strong>AGENTE DE PROTECCIÓN</strong>, declaro bajo juramento mi compromiso de CUMPLIR TODAS LAS DISPOSICIONES Y REQUERIMIENTOS DE LA GERENCIA, que para los efectos de mantener los estándares de seguridad BASC, deba realizar en el momento que sea requerido como:
    </p>

    <ul>
        <li>Capacitación</li>
        <li>Examen Médico</li>
        <li>Análisis Toxicológicos de sangre, orina u otros</li>
        <li>Pruebas poligráficas</li>
        <li>Exámenes Psicológicos</li>
        <li>Verificación de mis antecedentes</li>
        <li>Verificación de mi domicilio</li>
        <li>Otros que permitan mantener los estándares de seguridad de la empresa y sus clientes</li>
    </ul>

    <p>
        En caso de incumplimiento, manifiesto mi conocimiento y conformidad de ser removido del puesto que se me haya asignado hasta cumplir con el presente compromiso.
    </p>

    <p class="footer-text">Para mayor constancia firmo la presente en Chimbote a los 2 días del mes de Abril del 2025.</p>

    <table class="firma-table">
        <tr>
            <td>
                <img src="{{ public_path('images/huella_digital.png') }}" height="100"><br>
                Huella Digital
            </td>
            <td>
                <img src="{{ public_path('images/firma_jorge.png') }}" height="60"><br>
                <strong>JORGE ARTURO ALVA CAYETANO</strong><br>
                DNI: 32868199<br>
                AGENTE DE PROTECCIÓN
            </td>
        </tr>
    </table>

</body>
</html>
