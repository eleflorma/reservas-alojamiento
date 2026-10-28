<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Database\Connection;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

try {
    $pdo = Connection::getConnection();

    $stmt = $pdo->query(
        'SELECT COUNT(*) AS total FROM reserva'
    );

    $resultado = $stmt->fetch();

    echo 'Conexión correcta.' . PHP_EOL;
    echo 'Reservas encontradas: ' . $resultado['total'] . PHP_EOL;
} catch (Throwable $e) {
    echo 'No se pudo completar la prueba de conexión.' . PHP_EOL;

    // El detalle técnico se consulta en los registros del servidor.
    error_log((string) $e);
    exit(1);
}
