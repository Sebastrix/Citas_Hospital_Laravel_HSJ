<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

final class RemoteQueryController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(app()->isLocal() && config('services.remote_query.enabled', false), 404);

        $sql = (string) $request->input('sql', $this->initialQuery());
        $limit = min(max((int) $request->input('limite_visual', 200), 1), 1000);
        $columns = [];
        $rows = [];
        $error = '';
        $elapsed = 0.0;

        if ($request->isMethod('post')) {
            try {
                $this->validateReadOnly($sql);
                $start = microtime(true);
                $pdo = DB::connection('sigh')->getPdo();
                $stmt = $pdo->query($sql);
                if (! $stmt) throw new RuntimeException('No se pudo ejecutar la consulta.');

                do {
                    if ($stmt->columnCount() > 0) break;
                } while ($stmt->nextRowset());

                for ($i = 0; $i < $stmt->columnCount(); $i++) {
                    $meta = $stmt->getColumnMeta($i);
                    $columns[] = isset($meta['name']) ? (string) $meta['name'] : 'Columna_'.($i + 1);
                }

                while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false && count($rows) < $limit) {
                    $rows[] = $row;
                }
                $elapsed = microtime(true) - $start;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('tools.remote-query', compact('sql', 'limit', 'columns', 'rows', 'error', 'elapsed'));
    }

    private function validateReadOnly(string $sql): void
    {
        $clean = preg_replace('/\/\*.*?\*\//s', ' ', $sql) ?? $sql;
        $clean = preg_replace('/--[^\r\n]*/', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') throw new RuntimeException('Escribe una consulta SQL para ejecutar.');
        if (! preg_match('/^(select|with|declare)\b/i', $clean)) throw new RuntimeException('Solo se permiten consultas que comiencen con SELECT, WITH o DECLARE.');
        if (preg_match('/\b(insert|update|delete|drop|alter|create|truncate|merge|exec|execute|grant|revoke|deny|backup|restore|kill|use|into)\b/i', $clean)) {
            throw new RuntimeException('Consulta bloqueada. Esta página solo permite lectura.');
        }
        if (preg_match('/\b(xp_|sp_)\w+/i', $clean)) throw new RuntimeException('No se permite ejecutar procedimientos almacenados.');
    }

    private function initialQuery(): string
    {
        return "SELECT TOP 50\n    TABLE_SCHEMA,\n    TABLE_NAME,\n    TABLE_TYPE\nFROM INFORMATION_SCHEMA.TABLES\nWHERE TABLE_TYPE = 'BASE TABLE'\nORDER BY TABLE_SCHEMA, TABLE_NAME";
    }
}
