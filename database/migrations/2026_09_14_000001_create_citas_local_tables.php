<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $connection = 'sqlsrv';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('SalaEsperaTickets')) {
            $schema->create('SalaEsperaTickets', function (Blueprint $table): void {
                $table->increments('IdTicket');
                $table->string('Ticket', 20)->unique();
                $table->string('Estado', 20)->default('ESPERA');
                $table->dateTime('FechaIngreso')->useCurrent();
                $table->dateTime('FechaInicioTurno')->nullable();
                $table->dateTime('FechaExpiracion')->nullable();
                $table->dateTime('FechaFinalizacion')->nullable();
                $table->index(['Estado', 'FechaIngreso', 'IdTicket'], 'IX_SalaEsperaTickets_Estado_Fecha');
            });
        }

        if ($schema->hasTable('SalaEsperaTickets') && ! $schema->hasColumn('SalaEsperaTickets', 'FechaExpiracion')) {
            $schema->table('SalaEsperaTickets', function (Blueprint $table): void {
                $table->dateTime('FechaExpiracion')->nullable();
            });

            if ($schema->hasColumn('SalaEsperaTickets', 'ExpiraEn')) {
                DB::connection($this->connection)->statement(
                    'UPDATE dbo.SalaEsperaTickets SET FechaExpiracion = ExpiraEn WHERE FechaExpiracion IS NULL'
                );
            }
        }

        if (! $schema->hasTable('SalaEsperaRegistros')) {
            $schema->create('SalaEsperaRegistros', function (Blueprint $table): void {
                $table->increments('IdRegistro');
                $table->string('Ticket', 20);
                $table->string('NumHC', 50);
                $table->string('Nombre', 100);
                $table->string('Apellido', 100);
                $table->string('Sexo', 20);
                $table->string('TipoDocumento', 20);
                $table->string('DocIden', 30);
                $table->string('Telefono', 15);
                $table->integer('IdEspecialidad');
                $table->string('Especialidad', 150);
                $table->integer('IdServicio')->default(0);
                $table->string('Servicio', 150)->default('NO APLICA');
                $table->integer('IdMedico');
                $table->string('Medico', 200);
                $table->string('TieneSIS', 2)->nullable();
                $table->text('MotivoConsulta')->nullable();
                $table->string('EsGinecologia', 2)->nullable();
                $table->string('EsGestante', 2)->nullable();
                $table->integer('SemanaGestacion')->nullable();
                $table->integer('EdadPaciente')->nullable();
                $table->string('EsAdolescente', 2)->nullable();
                $table->string('CesareaAnterior', 2)->nullable();
                $table->string('Diabetes', 2)->nullable();
                $table->string('Hipertension', 2)->nullable();
                $table->string('InfeccionesUrinariasRepetitivas', 2)->nullable();
                $table->string('Hemofilia', 2)->nullable();
                $table->integer('NumeroGestacion')->nullable();
                $table->text('Otros')->nullable();
                $table->dateTime('FechaRegistro')->useCurrent();
                $table->string('Estado', 20)->default('REGISTRADO');
                $table->index('FechaRegistro', 'IX_SalaEsperaRegistros_Fecha');
            });
        } else {
            $columns = [
                'TieneSIS' => fn (Blueprint $table) => $table->string('TieneSIS', 2)->nullable(),
                'MotivoConsulta' => fn (Blueprint $table) => $table->text('MotivoConsulta')->nullable(),
                'EsGinecologia' => fn (Blueprint $table) => $table->string('EsGinecologia', 2)->nullable(),
                'EsGestante' => fn (Blueprint $table) => $table->string('EsGestante', 2)->nullable(),
                'SemanaGestacion' => fn (Blueprint $table) => $table->integer('SemanaGestacion')->nullable(),
                'EdadPaciente' => fn (Blueprint $table) => $table->integer('EdadPaciente')->nullable(),
                'EsAdolescente' => fn (Blueprint $table) => $table->string('EsAdolescente', 2)->nullable(),
                'CesareaAnterior' => fn (Blueprint $table) => $table->string('CesareaAnterior', 2)->nullable(),
                'Diabetes' => fn (Blueprint $table) => $table->string('Diabetes', 2)->nullable(),
                'Hipertension' => fn (Blueprint $table) => $table->string('Hipertension', 2)->nullable(),
                'InfeccionesUrinariasRepetitivas' => fn (Blueprint $table) => $table->string('InfeccionesUrinariasRepetitivas', 2)->nullable(),
                'Hemofilia' => fn (Blueprint $table) => $table->string('Hemofilia', 2)->nullable(),
                'NumeroGestacion' => fn (Blueprint $table) => $table->integer('NumeroGestacion')->nullable(),
                'Otros' => fn (Blueprint $table) => $table->text('Otros')->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (! $schema->hasColumn('SalaEsperaRegistros', $name)) {
                    $schema->table('SalaEsperaRegistros', $definition);
                }
            }
        }

        if (! $schema->hasTable('ChatbotLogs')) {
            $schema->create('ChatbotLogs', function (Blueprint $table): void {
                $table->increments('IdLog');
                $table->text('MensajeUsuario');
                $table->text('RespuestaBot');
                $table->string('Intencion', 50);
                $table->dateTime('FechaRegistro')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // No se eliminan tablas automáticamente para proteger registros hospitalarios locales.
    }
};
