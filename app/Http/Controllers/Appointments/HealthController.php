<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    public function sigh(): JsonResponse
    {
        try {
            $row = DB::connection('sigh')->selectOne('SELECT DB_NAME() AS base_actual, @@SERVERNAME AS servidor, GETDATE() AS fecha_servidor');
            return response()->json(['ok' => true, 'base' => 'SIGH', 'conectado' => true, 'data' => [$row]]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'base' => 'SIGH', 'conectado' => false, 'detalle' => config('app.debug') ? $e->getMessage() : null], 503);
        }
    }

    public function appointments(): JsonResponse
    {
        try {
            $row = DB::connection('sqlsrv')->selectOne('SELECT DB_NAME() AS base_actual, @@SERVERNAME AS servidor, GETDATE() AS fecha_servidor');
            return response()->json(['ok' => true, 'base' => 'CITAS SQL Server', 'conectado' => true, 'data' => [$row]]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'base' => 'CITAS SQL Server', 'conectado' => false, 'detalle' => config('app.debug') ? $e->getMessage() : null], 503);
        }
    }

    public function reconnectSigh(): JsonResponse
    {
        DB::purge('sigh');
        return $this->sigh();
    }

    public function reconnectAppointments(): JsonResponse
    {
        DB::purge('sqlsrv');
        return $this->appointments();
    }
}
