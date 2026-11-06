<?php

namespace App\Services\Appointments;

use Illuminate\Support\Facades\DB;

final class SighAppointmentService
{
    public function availability(array $filters): array
    {
        $sql = <<<'SQL'
            SELECT *
            FROM (
                SELECT
                    pm.IdProgramacion,
                    CAST(pm.Fecha AS date) AS Fecha,
                    pm.IdDepartamento,
                    dh.Nombre AS Departamento,
                    pm.IdEspecialidad,
                    e.Nombre AS Especialidad,
                    pm.IdServicio,
                    s.Nombre AS Servicio,
                    pm.IdMedico,
                    UPPER(LTRIM(RTRIM(
                        ISNULL(emp.ApellidoPaterno, '') + ' ' +
                        ISNULL(emp.ApellidoMaterno, '') + ' ' +
                        ISNULL(emp.Nombres, '')
                    ))) AS Medico,
                    pm.HoraInicio,
                    pm.HoraFin,
                    pm.IdTurno,
                    t.Descripcion AS Turno,
                    pm.TiempoPromedioAtencion,
                    DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) AS MinutosProgramados,
                    CASE
                        WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0 THEN
                            DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion
                        ELSE 0
                    END AS CuposProgramados,
                    COUNT(DISTINCT c.IdCita) AS CitasOtorgadas,
                    COUNT(DISTINCT cb.IdCitaBloqueada) AS CitasBloqueadas,
                    CASE
                        WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0 THEN
                            (DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion)
                            - COUNT(DISTINCT c.IdCita)
                            - COUNT(DISTINCT cb.IdCitaBloqueada)
                        ELSE 0
                    END AS CuposDisponibles
                FROM ProgramacionMedica pm
                LEFT JOIN Servicios s ON s.IdServicio = pm.IdServicio
                LEFT JOIN Especialidades e ON e.IdEspecialidad = pm.IdEspecialidad
                LEFT JOIN DepartamentosHospital dh ON dh.IdDepartamento = pm.IdDepartamento
                LEFT JOIN Turnos t ON t.IdTurno = pm.IdTurno
                LEFT JOIN Medicos m ON m.IdMedico = pm.IdMedico
                LEFT JOIN Empleados emp ON emp.IdEmpleado = m.IdEmpleado
                LEFT JOIN Citas c ON c.IdProgramacion = pm.IdProgramacion
                LEFT JOIN CitasBloqueadas cb
                    ON cb.IdMedico = pm.IdMedico
                    AND CAST(cb.Fecha AS date) = CAST(pm.Fecha AS date)
                    AND cb.HoraInicio >= pm.HoraInicio
                    AND cb.HoraFin <= pm.HoraFin
                WHERE CAST(pm.Fecha AS date) = ?
                  AND (? IS NULL OR pm.IdDepartamento = ?)
                  AND (? IS NULL OR pm.IdEspecialidad = ?)
                  AND (? IS NULL OR pm.IdServicio = ?)
                  AND (? IS NULL OR pm.IdMedico = ?)
                GROUP BY
                    pm.IdProgramacion, CAST(pm.Fecha AS date), pm.IdDepartamento, dh.Nombre,
                    pm.IdEspecialidad, e.Nombre, pm.IdServicio, s.Nombre, pm.IdMedico,
                    emp.ApellidoPaterno, emp.ApellidoMaterno, emp.Nombres,
                    pm.HoraInicio, pm.HoraFin, pm.IdTurno, t.Descripcion, pm.TiempoPromedioAtencion
            ) x
            ORDER BY x.Fecha, x.Servicio, x.HoraInicio
            SQL;

        $date = $filters['fechaInicio'] ?? now()->toDateString();
        $department = $filters['idDepartamento'] ?? null;
        $specialty = $filters['idEspecialidad'] ?? null;
        $service = $filters['idServicio'] ?? null;
        $doctor = $filters['idMedico'] ?? null;

        return DB::connection('sigh')->select($sql, [
            $date,
            $department, $department,
            $specialty, $specialty,
            $service, $service,
            $doctor, $doctor,
        ]);
    }

    public function specialties(): array
    {
        return DB::connection('sigh')->select(<<<'SQL'
            SELECT
                'dep:' + CAST(dh.IdDepartamento AS varchar(20)) AS ValorFiltro,
                dh.IdDepartamento,
                CAST(NULL AS int) AS IdEspecialidad,
                LTRIM(RTRIM(dh.Nombre)) AS Nombre,
                'DEPARTAMENTO' AS TipoCatalogo
            FROM DepartamentosHospital dh
            WHERE dh.Nombre IS NOT NULL AND LTRIM(RTRIM(dh.Nombre)) <> ''

            UNION ALL

            SELECT
                'esp:' + CAST(e.IdEspecialidad AS varchar(20)) AS ValorFiltro,
                e.IdDepartamento,
                e.IdEspecialidad,
                LTRIM(RTRIM(e.Nombre)) AS Nombre,
                'ESPECIALIDAD' AS TipoCatalogo
            FROM Especialidades e
            WHERE e.Nombre IS NOT NULL AND LTRIM(RTRIM(e.Nombre)) <> ''
            ORDER BY Nombre
            SQL);
    }

    public function availableSpecialties(?string $from, ?string $to): array
    {
        $from ??= now()->toDateString();

        return DB::connection('sigh')->select(<<<'SQL'
            SELECT DISTINCT
                'esp:' + CAST(e.IdEspecialidad AS varchar(20)) AS ValorFiltro,
                e.IdEspecialidad,
                e.IdDepartamento,
                LTRIM(RTRIM(e.Nombre)) AS Especialidad,
                LTRIM(RTRIM(e.Nombre)) AS Nombre
            FROM ProgramacionMedica pm
            INNER JOIN Especialidades e ON e.IdEspecialidad = pm.IdEspecialidad
            WHERE CAST(pm.Fecha AS date) >= ?
              AND (? IS NULL OR CAST(pm.Fecha AS date) <= ?)
              AND e.Nombre IS NOT NULL
              AND LTRIM(RTRIM(e.Nombre)) <> ''
            ORDER BY Especialidad
            SQL, [$from, $to, $to]);
    }

    public function services(?int $specialtyId, ?int $departmentId): array
    {
        return DB::connection('sigh')->select(<<<'SQL'
            SELECT s.IdServicio, s.Nombre, s.IdEspecialidad, s.IdTipoServicio, s.IdProducto, s.idEstado
            FROM Servicios s
            LEFT JOIN Especialidades e ON e.IdEspecialidad = s.IdEspecialidad
            WHERE s.idEstado = 1
              AND (? IS NULL OR s.IdEspecialidad = ?)
              AND (? IS NULL OR e.IdDepartamento = ?)
            ORDER BY s.Nombre
            SQL, [$specialtyId, $specialtyId, $departmentId, $departmentId]);
    }

    public function doctors(?int $specialtyId, ?int $serviceId, ?int $departmentId): array
    {
        return DB::connection('sigh')->select(<<<'SQL'
            SELECT DISTINCT
                m.IdMedico,
                UPPER(LTRIM(RTRIM(
                    ISNULL(emp.ApellidoPaterno, '') + ' ' +
                    ISNULL(emp.ApellidoMaterno, '') + ' ' +
                    ISNULL(emp.Nombres, '')
                ))) AS Nombre
            FROM ProgramacionMedica pm
            INNER JOIN Medicos m ON m.IdMedico = pm.IdMedico
            INNER JOIN Empleados emp ON emp.IdEmpleado = m.IdEmpleado
            WHERE (? IS NULL OR pm.IdEspecialidad = ?)
              AND (? IS NULL OR pm.IdServicio = ?)
              AND (? IS NULL OR pm.IdDepartamento = ?)
              AND ISNULL(pm.TiempoPromedioAtencion, 0) > 0
              AND (DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time))
                   / NULLIF(pm.TiempoPromedioAtencion, 0)) > 0
            ORDER BY Nombre
            SQL, [
                $specialtyId, $specialtyId,
                $serviceId, $serviceId,
                $departmentId, $departmentId,
            ]);
    }

    public function shifts(): array
    {
        return DB::connection('sigh')->select('SELECT IdTurno, Codigo, Descripcion, HoraInicio, HoraFin, IdTipoServicio, IdEspecialidad FROM Turnos ORDER BY Descripcion');
    }

    public function appointmentStates(): array
    {
        return DB::connection('sigh')->select('SELECT IdEstadoCita, Descripcion FROM TiposEstadosCita ORDER BY IdEstadoCita');
    }

    public function findPatient(?string $clinicalHistory, ?string $dni): ?array
    {
        $clinicalHistory = trim((string) $clinicalHistory);
        $dni = trim((string) $dni);

        if ($clinicalHistory === '' && $dni === '') {
            return null;
        }

        $column = $dni !== '' ? 'NroDocumento' : 'NroHistoriaClinica';
        $value = $dni !== '' ? $dni : $clinicalHistory;
        $row = DB::connection('sigh')->selectOne(<<<SQL
            SELECT TOP 1
                IdPaciente, NroHistoriaClinica, NroDocumento,
                ApellidoPaterno, ApellidoMaterno,
                PrimerNombre, SegundoNombre, TercerNombre,
                FechaNacimiento, IdTipoSexo
            FROM dbo.Pacientes
            WHERE LTRIM(RTRIM({$column})) = ?
            SQL, [$value]);

        if (! $row) {
            return null;
        }

        $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $row->PrimerNombre ?? '', $row->SegundoNombre ?? '', $row->TercerNombre ?? '',
        ]))) ?? '');
        $surname = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $row->ApellidoPaterno ?? '', $row->ApellidoMaterno ?? '',
        ]))) ?? '');
        $document = strtoupper(trim((string) ($row->NroDocumento ?? '')));
        if (preg_match('/^1\d{8}$/', $document) || preg_match('/^2[A-Z0-9]{4,20}$/', $document)) {
            $document = substr($document, 1);
        }

        return [
            'idPaciente' => $row->IdPaciente ?? null,
            'historiaClinica' => trim((string) ($row->NroHistoriaClinica ?? '')),
            'documento' => $document,
            'nombre' => mb_strtoupper($name, 'UTF-8'),
            'apellido' => mb_strtoupper($surname, 'UTF-8'),
            'sexo' => match ((int) ($row->IdTipoSexo ?? 0)) { 1 => 'MASCULINO', 2 => 'FEMENINO', default => '' },
            'fechaNacimiento' => $row->FechaNacimiento ?? null,
        ];
    }

    public function chatbotPrograms(string $search, string $date): array
    {
        $terms = $this->searchTerms($search);
        $sql = <<<'SQL'
            SELECT TOP 6
                CAST(pm.Fecha AS date) AS Fecha,
                CONVERT(varchar(10), CAST(pm.Fecha AS date), 23) AS FechaTexto,
                e.Nombre AS Especialidad,
                s.Nombre AS Servicio,
                UPPER(LTRIM(RTRIM(
                    ISNULL(emp.ApellidoPaterno, '') + ' ' +
                    ISNULL(emp.ApellidoMaterno, '') + ' ' +
                    ISNULL(emp.Nombres, '')
                ))) AS Medico,
                t.Descripcion AS Turno,
                CONVERT(varchar(5), CAST(pm.HoraInicio AS time), 108) AS HoraInicio,
                CONVERT(varchar(5), CAST(pm.HoraFin AS time), 108) AS HoraFin,
                CASE WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion ELSE 0 END AS CuposProgramados,
                COUNT(DISTINCT c.IdCita) AS CitasOtorgadas,
                COUNT(DISTINCT cb.IdCitaBloqueada) AS CitasBloqueadas,
                CASE WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                    THEN (DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion)
                         - COUNT(DISTINCT c.IdCita) - COUNT(DISTINCT cb.IdCitaBloqueada) ELSE 0 END AS CuposDisponibles
            FROM ProgramacionMedica pm
            INNER JOIN Especialidades e ON e.IdEspecialidad = pm.IdEspecialidad
            INNER JOIN Servicios s ON s.IdServicio = pm.IdServicio
            LEFT JOIN Turnos t ON t.IdTurno = pm.IdTurno
            LEFT JOIN Medicos m ON m.IdMedico = pm.IdMedico
            LEFT JOIN Empleados emp ON emp.IdEmpleado = m.IdEmpleado
            LEFT JOIN Citas c ON c.IdProgramacion = pm.IdProgramacion
            LEFT JOIN CitasBloqueadas cb
                ON cb.IdMedico = pm.IdMedico
                AND CAST(cb.Fecha AS date) = CAST(pm.Fecha AS date)
                AND cb.HoraInicio >= pm.HoraInicio
                AND cb.HoraFin <= pm.HoraFin
            WHERE CAST(pm.Fecha AS date) >= ?
              AND ISNULL(pm.TiempoPromedioAtencion, 0) > 0
            SQL;
        $bindings = [$date];

        if ($terms !== []) {
            $conditions = [];
            foreach ($terms as $term) {
                $conditions[] = "(e.Nombre COLLATE Latin1_General_CI_AI LIKE ? OR s.Nombre COLLATE Latin1_General_CI_AI LIKE ? OR (ISNULL(emp.ApellidoPaterno, '') + ' ' + ISNULL(emp.ApellidoMaterno, '') + ' ' + ISNULL(emp.Nombres, '')) COLLATE Latin1_General_CI_AI LIKE ?)";
                $like = '%'.$term.'%';
                array_push($bindings, $like, $like, $like);
            }
            $sql .= ' AND ('.implode(' OR ', $conditions).')';
        }

        $sql .= <<<'SQL'
            GROUP BY CAST(pm.Fecha AS date), e.Nombre, s.Nombre,
                emp.ApellidoPaterno, emp.ApellidoMaterno, emp.Nombres,
                t.Descripcion, pm.HoraInicio, pm.HoraFin, pm.TiempoPromedioAtencion
            ORDER BY CAST(pm.Fecha AS date), pm.HoraInicio
            SQL;

        return DB::connection('sigh')->select($sql, $bindings);
    }

    public function searchTerms(string $search): array
    {
        $stop = ['hola','buenos','dias','buenas','tardes','noches','quiero','saber','consultar','dime','decirme','por','favor','horario','horarios','hora','atiende','atienden','atencion','cita','citas','cupo','cupos','disponible','disponibles','especialidad','servicio','medico','doctor','doctora','para','del','de','la','el','los','las','un','una','y','o','en','me','hay','tiene','tienen','hoy','manana','lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
        $normalized = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($search)) ?: trim($search), 'UTF-8');
        $parts = preg_split('/\s+/', $normalized) ?: [];
        $terms = [];
        foreach ($parts as $part) {
            $part = preg_replace('/[^a-z0-9ñ]/u', '', $part) ?? '';
            if (strlen($part) >= 3 && ! in_array($part, $stop, true)) {
                $terms[] = $part;
            }
        }
        return array_slice(array_values(array_unique($terms)), 0, 5);
    }
}
