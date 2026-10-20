-- El municipio guardado tiene que pertenecer al departamento elegido.
-- El índice (id, departamento_id) permite la clave foránea compuesta.

ALTER TABLE municipios
    ADD UNIQUE KEY uq_municipios_id_departamento (id, departamento_id);

ALTER TABLE paciente
    ADD CONSTRAINT fk_paciente_municipio_departamento
        FOREIGN KEY (municipio_id, departamento_id)
        REFERENCES municipios (id, departamento_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;
