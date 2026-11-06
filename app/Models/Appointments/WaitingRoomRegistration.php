<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Model;

final class WaitingRoomRegistration extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SalaEsperaRegistros';
    protected $primaryKey = 'IdRegistro';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['FechaRegistro' => 'datetime'];
    }
}
