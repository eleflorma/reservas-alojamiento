<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Repositories\ReservaRepository;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Cargar las variables de configuración.
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

try {
    // Conectar con la base de datos.
    $conexion = Connection::getConnection();

    // Obtener las reservas.
    $repositorio = new ReservaRepository($conexion);
    $reservas = $repositorio->obtenerTodas();

    // Configurar Twig.
    $rutaPlantillas = dirname(__DIR__) . '/templates';
    $loader = new FilesystemLoader($rutaPlantillas);

    $twig = new Environment($loader, [
        'autoescape' => 'html',
        'cache' => false,
    ]);

    // Renderizar la página HTML.
    echo $twig->render('reservas/index.html.twig', [
        'reservas' => $reservas,
    ]);

} catch (Throwable $e) {
    error_log((string) $e);

    http_response_code(500);
    echo 'No se ha podido cargar la página de reservas.';
}
