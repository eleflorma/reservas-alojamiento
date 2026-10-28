<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Repositories\ReservaRepository;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$conexion = Connection::getConnection();
$repositorio = new ReservaRepository($conexion);

echo "1. Probando filtros combinados...\n";

$reservas = $repositorio->obtenerTodas(
    'pendiente',
    null,
    '2026-11-01',
    '2026-11-30'
);

echo "Reservas pendientes en noviembre: "
    . count($reservas) . "\n";

echo "\n2. Probando historial...\n";

$eventos = $repositorio->obtenerEventosPorReserva(1);

echo "Eventos de la reserva 1: "
    . count($eventos) . "\n";

echo "\n3. Probando cambio de estado...\n";

// Elegimos la reserva 2, que originalmente está pendiente.
$reserva = $repositorio->cambiarEstado(2, 'confirmada');

if ($reserva !== null) {
    echo "Estado actual: " . $reserva['estado'] . "\n";
}

echo "\n4. Comprobando el historial actualizado...\n";

$eventos = $repositorio->obtenerEventosPorReserva(2);

foreach ($eventos as $evento) {
    echo $evento['tipo'] . ': '
        . $evento['descripcion'] . "\n";
}

echo "\nPrueba terminada.\n";
