-- MediCitas: esquema de base de datos (MySQL 5.7+ / MariaDB 10.2+)

-- ==========================================
-- 1. CREAR BASE DE DATOS
-- ==========================================

IF DB_ID('medicitas') IS NULL
BEGIN
    CREATE DATABASE medicitas;
END;
GO

USE medicitas;
GO

-- ==========================================
-- 2. TABLA DE USUARIOS
-- ==========================================

CREATE TABLE usuarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,

    rol VARCHAR(20) NOT NULL
        CHECK (rol IN ('administrador', 'recepcionista', 'medico')),

    especialidad VARCHAR(100) NULL,
    activo BIT NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL
);
GO

-- ==========================================
-- 3. TABLA DE REGISTRO DE ACCESOS
-- ==========================================

CREATE TABLE log_accesos (
    id INT IDENTITY(1,1) PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    exito BIT NOT NULL,
    ip VARCHAR(45) NULL,
    fecha DATETIME NOT NULL DEFAULT GETDATE()
);
GO

-- ==========================================
-- 4. TABLA DE PACIENTES
-- ==========================================

CREATE TABLE pacientes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    documento VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    telefono VARCHAR(25) NULL,
    email VARCHAR(100) NULL,
    fecha_nacimiento DATE NULL,
    creado DATETIME NOT NULL DEFAULT GETDATE()
);
GO

-- ==========================================
-- 5. TABLA DE HORARIOS
-- ==========================================

-- 1 = Lunes ... 7 = Domingo

CREATE TABLE horarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    medico_id INT NOT NULL,

    dia_semana TINYINT NOT NULL
        CHECK (dia_semana BETWEEN 1 AND 7),

    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,

    CONSTRAINT FK_horarios_usuarios
        FOREIGN KEY (medico_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT CK_horarios_horas
        CHECK (hora_fin > hora_inicio)
);
GO

-- ==========================================
-- 6. TABLA DE CITAS
-- ==========================================

CREATE TABLE citas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,

    estado VARCHAR(20) NOT NULL DEFAULT 'programada'
        CHECK (estado IN (
            'programada',
            'atendida',
            'cancelada',
            'no_asistio'
        )),

    creada_por INT NULL,
    creado DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_citas_pacientes
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id),

    CONSTRAINT FK_citas_medicos
        FOREIGN KEY (medico_id)
        REFERENCES usuarios(id),

    CONSTRAINT FK_citas_creada_por
        FOREIGN KEY (creada_por)
        REFERENCES usuarios(id)
);
GO

-- Índice único filtrado:
-- Evita que un médico tenga dos citas programadas
-- en la misma fecha y hora.

CREATE UNIQUE INDEX uq_slot
ON citas (medico_id, fecha, hora)
WHERE estado = 'programada';
GO

-- ==========================================
-- 7. TABLA DE ATENCIONES MÉDICAS
-- ==========================================

CREATE TABLE atenciones (
    id INT IDENTITY(1,1) PRIMARY KEY,

    cita_id INT NOT NULL UNIQUE,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,

    motivo VARCHAR(MAX) NOT NULL,
    diagnostico VARCHAR(MAX) NOT NULL,
    tratamiento VARCHAR(MAX) NOT NULL,
    observaciones VARCHAR(MAX) NULL,

    registrada DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_atenciones_citas
        FOREIGN KEY (cita_id)
        REFERENCES citas(id),

    CONSTRAINT FK_atenciones_pacientes
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id),

    CONSTRAINT FK_atenciones_medicos
        FOREIGN KEY (medico_id)
        REFERENCES usuarios(id)
);
GO

-- ==========================================
-- 8. INSERTAR USUARIO ADMINISTRADOR
-- ==========================================

INSERT INTO usuarios (
    usuario,
    clave_hash,
    nombre,
    rol
)
VALUES (
    'admin',
    '$6$w.y5Dqzn1vdR16Jq$0yV8eSuSPRQWv2kLHAWDiMfJkh1jESX4xNbkmGfTPy0CLTgWfs45p2KMAr9kj7JHm1QmbjFBQagXgUzLrzexr1',
    'Administrador',
    'administrador'
);
GO

-- ==========================================
-- 9. VERIFICAR LAS TABLAS Y EL ADMIN
-- ==========================================

SELECT * FROM usuarios;
SELECT * FROM pacientes;
SELECT * FROM horarios;
SELECT * FROM citas;
SELECT * FROM atenciones;
SELECT * FROM log_accesos;
GO
