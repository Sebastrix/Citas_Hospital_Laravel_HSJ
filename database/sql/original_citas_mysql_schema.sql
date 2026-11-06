CREATE DATABASE IF NOT EXISTS citas_hospitalarias
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE citas_hospitalarias;

CREATE TABLE IF NOT EXISTS SalaEsperaTickets (
    IdTicket INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Ticket VARCHAR(20) NOT NULL UNIQUE,
    Estado VARCHAR(20) NOT NULL DEFAULT 'ESPERA',
    FechaIngreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaInicioTurno DATETIME NULL,
    FechaExpiracion DATETIME NULL,
    FechaFinalizacion DATETIME NULL,
    INDEX IX_SalaEsperaTickets_Estado_Fecha (Estado, FechaIngreso, IdTicket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SalaEsperaRegistros (
    IdRegistro INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Ticket VARCHAR(20) NOT NULL,
    NumHC VARCHAR(50) NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Sexo VARCHAR(20) NOT NULL,
    TipoDocumento VARCHAR(20) NOT NULL,
    DocIden VARCHAR(30) NOT NULL,
    Telefono VARCHAR(15) NOT NULL,
    IdEspecialidad INT NOT NULL,
    Especialidad VARCHAR(150) NOT NULL,
    IdServicio INT NOT NULL DEFAULT 0,
    Servicio VARCHAR(150) NOT NULL DEFAULT 'NO APLICA',
    IdMedico INT NOT NULL,
    Medico VARCHAR(200) NOT NULL,
    TieneSIS VARCHAR(2) NULL,
    MotivoConsulta TEXT NULL,
    EsGinecologia VARCHAR(2) NULL,
    EsGestante VARCHAR(2) NULL,
    SemanaGestacion INT NULL,
    EdadPaciente INT NULL,
    EsAdolescente VARCHAR(2) NULL,
    CesareaAnterior VARCHAR(2) NULL,
    Diabetes VARCHAR(2) NULL,
    Hipertension VARCHAR(2) NULL,
    InfeccionesUrinariasRepetitivas VARCHAR(2) NULL,
    Hemofilia VARCHAR(2) NULL,
    NumeroGestacion INT NULL,
    Otros TEXT NULL,
    FechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Estado VARCHAR(20) NOT NULL DEFAULT 'REGISTRADO',
    INDEX IX_SalaEsperaRegistros_Fecha (FechaRegistro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;