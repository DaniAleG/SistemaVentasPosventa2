-- Tabla necesaria para el manejo de sesiones en base de datos
-- (usada por Backend/includes/session_init.php)
CREATE TABLE IF NOT EXISTS sesiones (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    datos TEXT NOT NULL,
    expira_en DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
