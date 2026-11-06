<?php

namespace App\Services\Appointments;

use App\Models\Appointments\WaitingRoomRegistration;
use App\Models\Appointments\WaitingRoomTicket;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WaitingRoomService
{
    public const TURN_SECONDS = 300;

    public function enter(): array
    {
        $ticket = DB::connection('sqlsrv')->transaction(function (): string {
            $row = WaitingRoomTicket::query()->create([
                'Ticket' => 'TMP-'.Str::uuid()->toString(),
                'Estado' => 'ESPERA',
                'FechaIngreso' => now(),
            ]);

            $ticket = 'ESP-'.str_pad((string) $row->IdTicket, 3, '0', STR_PAD_LEFT);
            $row->update(['Ticket' => $ticket]);

            return $ticket;
        });

        $this->refreshQueue();

        return $this->status($ticket);
    }

    public function status(string $ticket): array
    {
        $this->refreshQueue();
        $row = WaitingRoomTicket::query()->where('Ticket', trim($ticket))->first();

        if (! $row) {
            return ['ok' => false, 'error' => 'Ticket no encontrado en sala de espera'];
        }

        $response = ['ok' => true, 'ticket' => $row->Ticket, 'estado' => $row->Estado];

        if ($row->Estado === 'ACTIVO') {
            $response['segundosRestantes'] = $row->FechaExpiracion
                ? max(0, now()->diffInSeconds($row->FechaExpiracion, false))
                : 0;
            return $response;
        }

        if ($row->Estado === 'ESPERA') {
            $position = WaitingRoomTicket::query()
                ->where('Estado', 'ESPERA')
                ->where(function ($query) use ($row): void {
                    $query->where('FechaIngreso', '<', $row->FechaIngreso)
                        ->orWhere(function ($same) use ($row): void {
                            $same->where('FechaIngreso', '=', $row->FechaIngreso)
                                ->where('IdTicket', '<', $row->IdTicket);
                        });
                })
                ->count() + 1;

            $active = WaitingRoomTicket::query()
                ->where('Estado', 'ACTIVO')
                ->orderBy('FechaInicioTurno')
                ->first();
            $activeRemaining = $active?->FechaExpiracion
                ? max(0, now()->diffInSeconds($active->FechaExpiracion, false))
                : 0;

            $response['posicion'] = $position;
            $response['segundosEstimados'] = $activeRemaining + max(0, $position - 1) * self::TURN_SECONDS;
        }

        return $response;
    }

    public function submit(array $data): array
    {
        $this->refreshQueue();

        $registration = DB::connection('sqlsrv')->transaction(function () use ($data): WaitingRoomRegistration {
            $ticket = WaitingRoomTicket::query()
                ->where('Ticket', trim($data['ticket']))
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new DomainException('Ticket no encontrado.');
            }
            if ($ticket->Estado === 'EXPIRADO' || ($ticket->Estado === 'ACTIVO' && $ticket->FechaExpiracion?->isPast())) {
                throw new DomainException('Tu turno expiró. Debes volver a ingresar a la sala de espera.');
            }
            if ($ticket->Estado !== 'ACTIVO') {
                throw new DomainException('Aún no es tu turno para enviar el registro.');
            }

            $row = WaitingRoomRegistration::query()->create([
                'Ticket' => trim($data['ticket']),
                'NumHC' => trim($data['historiaClinica']),
                'Nombre' => mb_strtoupper(trim($data['nombre']), 'UTF-8'),
                'Apellido' => mb_strtoupper(trim($data['apellido']), 'UTF-8'),
                'Sexo' => mb_strtoupper(trim($data['sexo']), 'UTF-8'),
                'TipoDocumento' => $data['tipoDocumento'],
                'DocIden' => $data['docIden'],
                'Telefono' => trim($data['telefono']),
                'IdEspecialidad' => (int) $data['idEspecialidad'],
                'Especialidad' => trim($data['especialidad']),
                'IdServicio' => 0,
                'Servicio' => 'NO APLICA',
                'IdMedico' => (int) $data['idMedico'],
                'Medico' => trim($data['medico']),
                'TieneSIS' => $data['tieneSis'],
                'MotivoConsulta' => $data['motivoConsulta'],
                'EsGinecologia' => $data['esGinecologia'],
                'EsGestante' => $data['esGestante'],
                'SemanaGestacion' => $data['semanaGestacion'],
                'EdadPaciente' => $data['edadPaciente'],
                'EsAdolescente' => $data['esAdolescente'],
                'CesareaAnterior' => $data['cesareaAnterior'],
                'Diabetes' => $data['diabetes'],
                'Hipertension' => $data['hipertension'],
                'InfeccionesUrinariasRepetitivas' => $data['infeccionesUrinariasRepetitivas'],
                'Hemofilia' => $data['hemofilia'],
                'NumeroGestacion' => $data['numeroGestacion'],
                'Otros' => $data['otros'],
                'FechaRegistro' => now(),
                'Estado' => 'REGISTRADO',
            ]);

            $ticket->update(['Estado' => 'FINALIZADO', 'FechaFinalizacion' => now()]);
            return $row->fresh();
        }, 3);

        $this->refreshQueue();
        return $this->mapRegistration($registration);
    }

    public function registrations(int $limit = 50): array
    {
        $this->refreshQueue();
        $limit = max(1, min(500, $limit));

        return WaitingRoomRegistration::query()
            ->orderByDesc('FechaRegistro')
            ->limit($limit)
            ->get()
            ->map(fn (WaitingRoomRegistration $row): array => $this->mapRegistration($row))
            ->all();
    }

    public function refreshQueue(): void
    {
        DB::connection('sqlsrv')->transaction(function (): void {
            WaitingRoomTicket::query()
                ->where('Estado', 'ACTIVO')
                ->whereNotNull('FechaExpiracion')
                ->where('FechaExpiracion', '<=', now())
                ->update(['Estado' => 'EXPIRADO', 'FechaFinalizacion' => now()]);

            $active = WaitingRoomTicket::query()
                ->where('Estado', 'ACTIVO')
                ->lockForUpdate()
                ->first();
            if ($active) {
                return;
            }

            $next = WaitingRoomTicket::query()
                ->where('Estado', 'ESPERA')
                ->orderBy('FechaIngreso')
                ->orderBy('IdTicket')
                ->lockForUpdate()
                ->first();
            if (! $next) {
                return;
            }

            $start = now();
            $next->update([
                'Estado' => 'ACTIVO',
                'FechaInicioTurno' => $start,
                'FechaExpiracion' => $start->copy()->addSeconds(self::TURN_SECONDS),
            ]);
        }, 3);
    }

    public function debug(): array
    {
        $connection = DB::connection('sqlsrv')->selectOne('SELECT DB_NAME() AS baseActual, @@SERVERNAME AS servidorActual, GETDATE() AS fechaServidor');
        $tickets = DB::connection('sqlsrv')->select('SELECT Estado, COUNT(*) AS total FROM SalaEsperaTickets GROUP BY Estado ORDER BY Estado');

        return [
            'conexion' => $connection,
            'tickets' => $tickets,
            'registros' => WaitingRoomRegistration::query()->count(),
        ];
    }

    private function mapRegistration(WaitingRoomRegistration $row): array
    {
        return [
            'idRegistro' => (int) $row->IdRegistro,
            'ticket' => $row->Ticket,
            'historiaClinica' => $row->NumHC,
            'nombre' => $row->Nombre,
            'apellido' => $row->Apellido,
            'sexo' => $row->Sexo,
            'tipoDocumento' => $row->TipoDocumento,
            'docIden' => $row->DocIden,
            'fechaRegistro' => optional($row->FechaRegistro)->toDateTimeString(),
            'estado' => $row->Estado,
            'telefono' => $row->Telefono,
            'idEspecialidad' => $row->IdEspecialidad,
            'especialidad' => $row->Especialidad,
            'idServicio' => $row->IdServicio,
            'servicio' => $row->Servicio,
            'idMedico' => $row->IdMedico,
            'medico' => $row->Medico,
            'tieneSis' => $row->TieneSIS,
            'motivoConsulta' => $row->MotivoConsulta,
            'esGinecologia' => $row->EsGinecologia,
            'esGestante' => $row->EsGestante,
            'semanaGestacion' => $row->SemanaGestacion,
            'edadPaciente' => $row->EdadPaciente,
            'esAdolescente' => $row->EsAdolescente,
            'cesareaAnterior' => $row->CesareaAnterior,
            'diabetes' => $row->Diabetes,
            'hipertension' => $row->Hipertension,
            'infeccionesUrinariasRepetitivas' => $row->InfeccionesUrinariasRepetitivas,
            'hemofilia' => $row->Hemofilia,
            'numeroGestacion' => $row->NumeroGestacion,
            'otros' => $row->Otros,
        ];
    }
}
