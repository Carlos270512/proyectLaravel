<?php
// Data Access Object para la tabla soap_demo en la base de datos 'mercado'

class ProductoDAO
{
    private PDO $pdo;

    /**
     * Constructor acepta un PDO opcional o crea uno por defecto (XAMPP).
     *
     * @param PDO|null $pdo
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
            return;
        }

        // Ajusta estas credenciales si tu entorno es distinto
        $host = '127.0.0.1';
        $db   = 'mercado';
        $user = 'root';
        $pass = '';
        $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, nombre_producto, precio FROM soap_demo ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre_producto, precio FROM soap_demo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $nombre, float $precio): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO soap_demo (nombre_producto, precio) VALUES (:nombre, :precio)');
        $stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $nombre, float $precio): bool
    {
        $stmt = $this->pdo->prepare('UPDATE soap_demo SET nombre_producto = :nombre, precio = :precio WHERE id = :id');
        $stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM soap_demo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}