<?php
/**
 * Manejo de sesiones basado en base de datos.
 *
 * En Vercel (entorno serverless) cada petición puede ejecutarse en un
 * contenedor distinto, así que las sesiones de archivo (comportamiento
 * por defecto de PHP) no persisten entre peticiones. Este handler guarda
 * las sesiones en la tabla `sesiones` de la misma base de datos MySQL,
 * así funciona igual en local que en producción.
 */

require_once __DIR__ . '/../conexion.php';

class MySQLSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT datos FROM sesiones WHERE id = ? AND expira_en > NOW()'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila ? $fila['datos'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO sesiones (id, datos, expira_en) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 4 HOUR))'
        );
        return $stmt->execute([$id, $data]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sesiones WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM sesiones WHERE expira_en < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}

session_set_save_handler(new MySQLSessionHandler($pdo), true);
session_start();
