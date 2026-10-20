<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 22px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 7px; }
        h1 { margin: 0 0 7px; color: #123b59; font-size: 15px; }
        .meta { margin-bottom: 12px; color: #53657c; font-size: 8px; }
        .stats { width: 100%; margin: 0 0 12px; border-collapse: collapse; table-layout: fixed; }
        .stats th, .stats td { width: auto; padding: 5px 4px; border: 1px solid #d1d5db; background: #e5e7eb; color: #374151; text-align: center; }
        .stats th { font-size: 7px; }
        .stats td { color: #111827; font-size: 9px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { padding: 5px 4px; background: #334155; color: #fff; text-align: left; font-size: 6px; }
        td { padding: 4px; border: 1px solid #d8e1eb; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: right; color: #64748b; font-size: 7px; }
        .footer:after { content: "Página " counter(page) " de " counter(pages); }
        .empty { padding: 18px; text-align: center; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    @if(isset($stats['foto_ok']))
        @php($totalEvaluado = max((int) $stats['personal'], 1))
        <table class="stats">
            <thead><tr><th>Generado</th><th>Personal evaluado</th><th>Foto OK</th><th>DNI completo</th><th>Incompletos</th></tr></thead>
            <tbody><tr>
                <td>{{ $stats['generado'] }}</td>
                <td>{{ number_format((int) $stats['personal'], 0, ',', '.') }}</td>
                <td>{{ number_format((int) $stats['foto_ok'], 0, ',', '.') }} ({{ number_format($stats['foto_ok'] / $totalEvaluado * 100, 1, ',', '.') }}%)</td>
                <td>{{ number_format((int) $stats['dni_ok'], 0, ',', '.') }} ({{ number_format($stats['dni_ok'] / $totalEvaluado * 100, 1, ',', '.') }}%)</td>
                <td>{{ number_format((int) $stats['faltan'], 0, ',', '.') }}</td>
            </tr></tbody>
        </table>
    @elseif(isset($stats['sin_dj']))
        <table class="stats">
            <thead><tr><th>Generado</th><th>Personal evaluado</th><th>Sin DJ escaneada</th></tr></thead>
            <tbody><tr>
                <td>{{ $stats['generado'] }}</td>
                <td>{{ number_format((int) $stats['personal'], 0, ',', '.') }}</td>
                <td>{{ number_format((int) $stats['sin_dj'], 0, ',', '.') }}</td>
            </tr></tbody>
        </table>
    @elseif(isset($stats['hijos_mayores']))
        <table class="stats">
            <thead><tr><th>Generado</th><th>Personal</th><th>Hijos mayores de edad</th></tr></thead>
            <tbody><tr>
                <td>{{ $stats['generado'] }}</td>
                <td>{{ number_format((int) $stats['personal'], 0, ',', '.') }}</td>
                <td>{{ number_format((int) $stats['hijos_mayores'], 0, ',', '.') }}</td>
            </tr></tbody>
        </table>
    @else
        @php($totalEvaluado = max((int) ($stats['evaluados'] ?? 0), 1))
        <table class="stats">
            <thead><tr><th>Generado</th><th>Personal evaluado</th><th>Sin faltantes</th><th>Con faltantes</th><th>Hijos menores de 18</th></tr></thead>
            <tbody><tr>
                <td>{{ $stats['generado'] }}</td>
                <td>{{ number_format((int) $stats['evaluados'], 0, ',', '.') }}</td>
                <td>{{ number_format((int) $stats['ok'], 0, ',', '.') }} ({{ number_format($stats['ok'] / $totalEvaluado * 100, 1, ',', '.') }}%)</td>
                <td>{{ number_format((int) $stats['faltan'], 0, ',', '.') }}</td>
                <td>{{ number_format((int) $stats['hijos'], 0, ',', '.') }}</td>
            </tr></tbody>
        </table>
    @endif

    @if($filas === [])
        <div class="empty">No se encontraron registros con los filtros seleccionados.</div>
    @else
        <table>
            <thead><tr>@foreach($columnas as $etiqueta => $clave)<th>{{ $etiqueta }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach($filas as $fila)
                    <tr>@foreach($columnas as $clave)<td>{{ $fila[$clave] ?? '—' }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <div class="footer">SISOL Web · </div>
</body>
</html>
