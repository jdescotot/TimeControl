-- Script SQL para crear tablas de seguridad del sistema de recuperación de contraseña
-- Ejecutar este script una sola vez en la base de datos

-- Tabla para rate limiting (prevenir spam de solicitudes de reset)
CREATE TABLE IF NOT EXISTS password_reset_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para auditoría de uso de tokens (logging de seguridad)
CREATE TABLE IF NOT EXISTS password_reset_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    username VARCHAR(255),
    token_generated_at DATETIME,
    token_used_at DATETIME,
    ip_address VARCHAR(45),
    user_agent TEXT,
    success BOOLEAN DEFAULT 0,
    action VARCHAR(50) DEFAULT 'password_reset',
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token_used (token_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar que la tabla usuarios tenga las columnas necesarias
-- (Descomentar si es necesario agregar estas columnas)
-- ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_token VARCHAR(128) NULL;
-- ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_token_expira DATETIME NULL;
-- ALTER TABLE usuarios ADD INDEX idx_reset_token (reset_token);
