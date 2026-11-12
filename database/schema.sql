CREATE DATABASE IF NOT EXISTS medicitas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medicitas;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  rol ENUM('administrador','recepcionista','medico') NOT NULL,
  especialidad VARCHAR(100) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos TINYINT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL
);

CREATE TABLE log_accesos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL,
  exito TINYINT(1) NOT NULL,
  ip VARCHAR(45) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documento VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  telefono VARCHAR(25) NULL,
  email VARCHAR(100) NULL,
  fecha_nacimiento DATE NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE horarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medico_id INT NOT NULL,
  dia_semana TINYINT NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  FOREIGN KEY (medico_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE citas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  medico_id INT NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  estado ENUM('programada','atendida','cancelada','no_asistio') NOT NULL DEFAULT 'programada',
  creada_por INT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  slot_activo TINYINT AS (IF(estado = 'programada', 1, NULL)) STORED,
  UNIQUE KEY uq_slot (medico_id, fecha, hora, slot_activo),
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  FOREIGN KEY (medico_id) REFERENCES usuarios(id)
);

CREATE TABLE atenciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cita_id INT NOT NULL UNIQUE,
  paciente_id INT NOT NULL,
  medico_id INT NOT NULL,
  motivo TEXT NOT NULL,
  diagnostico TEXT NOT NULL,
  tratamiento TEXT NOT NULL,
  observaciones TEXT NULL,
  registrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cita_id) REFERENCES citas(id),
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  FOREIGN KEY (medico_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (usuario, clave_hash, nombre, rol) VALUES
('admin', '$6$w.y5Dqzn1vdR16Jq$0yV8eSuSPRQWv2kLHAWDiMfJkh1jESX4xNbkmGfTPy0CLTgWfs45p2KMAr9kj7JHm1QmbjFBQagXgUzLrzexr1', 'Administrador', 'administrador');
