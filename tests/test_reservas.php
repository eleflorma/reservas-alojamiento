<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Repositories\ReservaRepository;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

try {
    $conexion = Connection::getConnection();

    $repositorio = new ReservaRepository($conexion);

    $reservas = $repositorio->obtenerTodas();

    echo 'Consulta realizada correctamente.' . PHP_EOL;
    echo 'Total de reservas: ' . count($reservas) . PHP_EOL;

    foreach ($reservas as $reserva) {
        echo $reserva['id'] . ' - '
            . $reserva['nombre_huesped'] . ' - '
            . $reserva['estado'] . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'Error al consultar las reservas.' . PHP_EOL;
    error_log((string) $e);
    exit(1);
}