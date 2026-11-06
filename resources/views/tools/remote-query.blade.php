<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta remota SIGH</title>
    <style>
        body{font-family:Arial,sans-serif;margin:2rem;background:#f7f8fa;color:#1f2937}textarea{width:100%;font-family:Consolas,monospace}table{border-collapse:collapse;width:100%;background:white}th,td{border:1px solid #d1d5db;padding:.4rem;text-align:left}th{background:#eef2f7}.card{background:white;padding:1rem;border-radius:10px;margin-bottom:1rem}.error{color:#991b1b;background:#fee2e2;padding:1rem}.ok{color:#065f46}.note{background:#fff7ed;padding:1rem;border-left:4px solid #f59e0b}
    </style>
</head>
<body>
<h1>Consulta remota SQL Server SIGH</h1>
<p class="note">Herramienta independiente y habilitada solo en entorno local. No modifica CITAS ni la sala de espera. Solo admite SELECT, WITH o DECLARE.</p>
<form method="post" class="card">
    @csrf
    <label for="sql"><strong>Consulta SQL</strong></label><br>
    <textarea id="sql" name="sql" rows="16">{{ $sql }}</textarea>
    <p><label>Máximo de filas: <input type="number" name="limite_visual" min="1" max="1000" value="{{ $limit }}"></label></p>
    <button type="submit">Ejecutar consulta</button>
</form>

@if($error !== '')
<div class="error"><strong>Error:</strong> {{ $error }}</div>
@elseif(request()->isMethod('post'))
<div class="card">
    <p class="ok">Filas mostradas: {{ count($rows) }} | Tiempo: {{ number_format($elapsed, 4) }} segundos</p>
    @if(count($columns) === 0)
        <p>La consulta no devolvió columnas.</p>
    @elseif(count($rows) === 0)
        <p>La consulta no devolvió registros.</p>
    @else
        <div style="overflow:auto">
        <table><thead><tr>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
        <tbody>@foreach($rows as $row)<tr>@foreach($columns as $column)<td>{{ is_scalar($row[$column] ?? null) || ($row[$column] ?? null) === null ? ($row[$column] ?? 'NULL') : json_encode($row[$column], JSON_UNESCAPED_UNICODE) }}</td>@endforeach</tr>@endforeach</tbody></table>
        </div>
    @endif
</div>
@endif
</body>
</html>
