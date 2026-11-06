<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Model;

final class WaitingRoomTicket extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SalaEsperaTickets';
    protected $primaryKey = 'IdTicket';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'FechaIngreso' => 'datetime',
            'FechaInicioTurno' => 'datetime',
            'FechaExpiracion' => 'datetime',
            'FechaFinalizacion' => 'datetime',
        ];
    }
}
