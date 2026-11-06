USE [CITAS];
GO

IF OBJECT_ID('dbo.SalaEsperaTickets', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.SalaEsperaTickets (
        IdTicket INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Ticket NVARCHAR(20) NOT NULL UNIQUE,
        Estado NVARCHAR(20) NOT NULL DEFAULT 'ESPERA',
        FechaIngreso DATETIME2(0) NOT NULL DEFAULT SYSDATETIME(),
        FechaInicioTurno DATETIME2(0) NULL,
        FechaExpiracion DATETIME2(0) NULL,
        FechaFinalizacion DATETIME2(0) NULL
    );

    CREATE INDEX IX_SalaEsperaTickets_Estado_Fecha
        ON dbo.SalaEsperaTickets (Estado, FechaIngreso, IdTicket);
END;
GO

IF OBJECT_ID('dbo.SalaEsperaRegistros', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.SalaEsperaRegistros (
        IdRegistro INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        Ticket NVARCHAR(20) NOT NULL,
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
        FechaRegistro DATETIME2(0) NOT NULL DEFAULT SYSDATETIME(),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'REGISTRADO'
    );

    CREATE INDEX IX_SalaEsperaRegistros_Fecha
        ON dbo.SalaEsperaRegistros (FechaRegistro DESC);
END;
GO

IF OBJECT_ID('dbo.ChatbotLogs', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.ChatbotLogs (
        IdLog INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        MensajeUsuario NVARCHAR(MAX) NOT NULL,
        RespuestaBot NVARCHAR(MAX) NOT NULL,
        Intencion NVARCHAR(50) NOT NULL,
        FechaRegistro DATETIME2(0) NOT NULL DEFAULT SYSDATETIME()
    );
END;
GO
