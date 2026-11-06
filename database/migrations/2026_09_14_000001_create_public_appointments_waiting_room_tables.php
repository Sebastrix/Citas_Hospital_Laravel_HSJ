<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('sqlsrv')->unprepared(<<<'SQL'
            IF OBJECT_ID('dbo.SalaEsperaTickets', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.SalaEsperaTickets (
                    IdTicket BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                    Ticket NVARCHAR(50) NOT NULL,
                    Estado NVARCHAR(20) NOT NULL CONSTRAINT DF_SalaEsperaTickets_Estado DEFAULT('ESPERA'),
                    FechaIngreso DATETIME2(0) NOT NULL CONSTRAINT DF_SalaEsperaTickets_FechaIngreso DEFAULT(SYSDATETIME()),
                    FechaInicioTurno DATETIME2(0) NULL,
                    ExpiraEn DATETIME2(0) NULL,
                    FechaFinalizacion DATETIME2(0) NULL,
                    CONSTRAINT UQ_SalaEsperaTickets_Ticket UNIQUE (Ticket),
                    CONSTRAINT CK_SalaEsperaTickets_Estado CHECK (Estado IN ('ESPERA','ACTIVO','FINALIZADO','EXPIRADO'))
                );
                CREATE INDEX IX_SalaEsperaTickets_Cola ON dbo.SalaEsperaTickets (Estado, IdTicket);
                CREATE INDEX IX_SalaEsperaTickets_ExpiraEn ON dbo.SalaEsperaTickets (Estado, ExpiraEn);
            END;

            IF OBJECT_ID('dbo.SalaEsperaRegistros', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.SalaEsperaRegistros (
                    IdRegistro BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                    Ticket NVARCHAR(30) NOT NULL,
                    NumHC NVARCHAR(50) NOT NULL,
                    Nombre NVARCHAR(100) NOT NULL,
                    Apellido NVARCHAR(100) NOT NULL,
                    Sexo NVARCHAR(20) NOT NULL,
                    TipoDocumento NVARCHAR(20) NOT NULL,
                    DocIden NVARCHAR(30) NOT NULL,
                    Telefono NVARCHAR(15) NOT NULL,
                    IdEspecialidad INT NOT NULL,
                    Especialidad NVARCHAR(150) NOT NULL,
                    IdServicio INT NOT NULL,
                    Servicio NVARCHAR(150) NOT NULL,
                    IdMedico INT NOT NULL,
                    Medico NVARCHAR(200) NOT NULL,
                    FechaRegistro DATETIME2(0) NOT NULL CONSTRAINT DF_SalaEsperaRegistros_FechaRegistro DEFAULT(SYSDATETIME()),
                    Estado NVARCHAR(30) NOT NULL CONSTRAINT DF_SalaEsperaRegistros_Estado DEFAULT('PENDIENTE')
                );
                CREATE INDEX IX_SalaEsperaRegistros_FechaRegistro ON dbo.SalaEsperaRegistros (FechaRegistro DESC);
                CREATE INDEX IX_SalaEsperaRegistros_Ticket ON dbo.SalaEsperaRegistros (Ticket);
            END;
            SQL);
    }

    public function down(): void
    {
        DB::connection('sqlsrv')->unprepared(<<<'SQL'
            IF OBJECT_ID('dbo.SalaEsperaTickets', 'U') IS NOT NULL
                DROP TABLE dbo.SalaEsperaTickets;
            SQL);
    }
};
