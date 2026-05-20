ALTER TABLE usuarios
    ADD COLUMN es_gerente TINYINT(1) NOT NULL DEFAULT 0 AFTER propietario_id;

CREATE INDEX idx_usuarios_propietario_gerente
    ON usuarios (propietario_id, es_gerente);

UPDATE usuarios
SET es_gerente = 0
WHERE es_gerente IS NULL;
