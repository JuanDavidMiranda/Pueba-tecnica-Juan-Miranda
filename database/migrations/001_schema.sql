CREATE TABLE IF NOT EXISTS departamentos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departamentos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS municipios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    departamento_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_municipio_departamento (departamento_id, nombre),
    KEY idx_municipios_departamento (departamento_id),
    CONSTRAINT fk_municipios_departamento
        FOREIGN KEY (departamento_id) REFERENCES departamentos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_documento (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tipos_documento_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS genero (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(40) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_genero_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS paciente (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_documento_id INT UNSIGNED NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    nombre1 VARCHAR(60) NOT NULL,
    nombre2 VARCHAR(60) NULL,
    apellido1 VARCHAR(60) NOT NULL,
    apellido2 VARCHAR(60) NULL,
    genero_id INT UNSIGNED NOT NULL,
    departamento_id INT UNSIGNED NOT NULL,
    municipio_id INT UNSIGNED NOT NULL,
    correo VARCHAR(180) NOT NULL,
    foto VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_paciente_documento (tipo_documento_id, numero_documento),
    UNIQUE KEY uq_paciente_correo (correo),
    KEY idx_paciente_nombre (apellido1, apellido2, nombre1),
    KEY idx_paciente_genero (genero_id),
    KEY idx_paciente_departamento (departamento_id),
    KEY idx_paciente_municipio (municipio_id),
    CONSTRAINT fk_paciente_tipo_documento
        FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documento (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_paciente_genero
        FOREIGN KEY (genero_id) REFERENCES genero (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_paciente_departamento
        FOREIGN KEY (departamento_id) REFERENCES departamentos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_paciente_municipio
        FOREIGN KEY (municipio_id) REFERENCES municipios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
