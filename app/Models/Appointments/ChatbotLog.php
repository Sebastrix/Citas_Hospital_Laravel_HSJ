<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Model;

final class ChatbotLog extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'ChatbotLogs';
    protected $primaryKey = 'IdLog';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['FechaRegistro' => 'datetime'];
    }
}
