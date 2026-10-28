<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Repositories\ReservaRepository;
use App\Services\ReservaService;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/**
 * Devuelve una respuesta JSON y termina la ejecución.
 */
function responderJson(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    exit;
}

/**
 * Lee el cuerpo JSON de la petición.
 */
function leerJson(): array
{
    try {
        $contenido = file_get_contents('php://input');

        $datos = json_decode(
            $contenido === false ? '' : $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        throw new InvalidArgumentException(
            'El cuerpo de la petición no contiene un JSON válido.'
        );
    }

    if (!is_array($datos)) {
        throw new InvalidArgumentException(
            'El cuerpo de la petición debe ser un objeto JSON.'
        );
    }

    return $datos;
}

/**
 * Comprueba una fecha con formato YYYY-MM-DD.
 */
function fechaValida(string $fecha): bool
{
    $fechaObjeto = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $fecha
    );

    $errores = DateTimeImmutable::getLastErrors();

    return $fechaObjeto !== false
        && (
            $errores === false
            || (
                $errores['warning_count'] === 0
                && $errores['error_count'] === 0
            )
        )
        && $fechaObjeto->format('Y-m-d') === $fecha;
}

try {
    $conexion = Connection::getConnection();
    $repositorio = new ReservaRepository($conexion);
    $servicio = new ReservaService($repositorio);

    // ----------------------------------------------
    // 1. LISTAR Y FILTRAR RESERVAS
    // GET /api/reservas
    // ----------------------------------------------

    if (
        $metodo === 'GET'
        && preg_match('~/api/reservas/?$~', $ruta)
    ) {
        $estado = $_GET['estado'] ?? null;
        $busqueda = $_GET['busqueda'] ?? '';
        $fechaDesde = $_GET['fecha_desde'] ?? null;
        $fechaHasta = $_GET['fecha_hasta'] ?? null;

        if (
            $estado !== null
            && (
                !is_string($estado)
                || !in_array(
                    $estado,
                    ['pendiente', 'confirmada', 'cancelada'],
                    true
                )
            )
        ) {
            responderJson([
                'exito' => false,
                'mensaje' => 'El estado indicado no es válido.'
            ], 400);
        }

        if (!is_string($busqueda)) {
            responderJson([
                'exito' => false,
                'mensaje' => 'El término de búsqueda no es válido.'
            ], 400);
        }

        foreach ([
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ] as $nombre => $fecha) {
            if (
                $fecha !== null
                && (
                    !is_string($fecha)
                    || !fechaValida($fecha)
                )
            ) {
                responderJson([
                    'exito' => false,
                    'mensaje' => "La fecha {$nombre} no es válida."
                ], 400);
            }
        }

        if (
            $fechaDesde !== null
            && $fechaHasta !== null
            && $fechaDesde > $fechaHasta
        ) {
            responderJson([
                'exito' => false,
                'mensaje' => 'La fecha desde no puede ser posterior a la fecha hasta.'
            ], 400);
        }

        $reservas = $repositorio->obtenerTodas(
            $estado,
            trim($busqueda) !== '' ? trim($busqueda) : null,
            $fechaDesde,
            $fechaHasta
        );

        responderJson([
            'exito' => true,
            'total' => count($reservas),
            'datos' => $reservas
        ]);
    }

    // ----------------------------------------------
    // 2. CREAR UNA RESERVA
    // POST /api/reservas
    // ----------------------------------------------

    if (
        $metodo === 'POST'
        && preg_match('~/api/reservas/?$~', $ruta)
    ) {
        $datos = leerJson();

        $id = $servicio->crear($datos);

        responderJson([
            'exito' => true,
            'mensaje' => 'Reserva creada correctamente.',
            'id' => $id
        ], 201);
    }

    // ----------------------------------------------
    // 3. CONSULTAR EL DETALLE Y EL HISTORIAL
    // GET /api/reservas/1
    // ----------------------------------------------

    if (
        $metodo === 'GET'
        && preg_match(
            '~/api/reservas/([0-9]+)/?$~',
            $ruta,
            $coincidencias
        )
    ) {
        $id = (int) $coincidencias[1];

        $reserva = $repositorio->obtenerPorId($id);

        if ($reserva === null) {
            responderJson([
                'exito' => false,
                'mensaje' => 'Reserva no encontrada.'
            ], 404);
        }

        $reserva['historial'] =
            $repositorio->obtenerEventosPorReserva($id);

        responderJson([
            'exito' => true,
            'datos' => $reserva
        ]);
    }

    // ----------------------------------------------
    // 4. CANCELAR UNA RESERVA
    // PATCH /api/reservas/1
    // Cuerpo JSON: {"estado":"cancelada"}
    // ----------------------------------------------

    if (
        $metodo === 'PATCH'
        && preg_match(
            '~/api/reservas/([0-9]+)/?$~',
            $ruta,
            $coincidencias
        )
    ) {
        $datos = leerJson();

        if (($datos['estado'] ?? null) !== 'cancelada') {
            responderJson([
                'exito' => false,
                'mensaje' => 'Solo se permite cambiar el estado a cancelada.'
            ], 400);
        }

        $reserva = $servicio->cancelar(
            (int) $coincidencias[1]
        );

        if ($reserva === null) {
            responderJson([
                'exito' => false,
                'mensaje' => 'Reserva no encontrada.'
            ], 404);
        }

        responderJson([
            'exito' => true,
            'mensaje' => 'Reserva cancelada correctamente.',
            'datos' => $reserva
        ]);
    }

    // ----------------------------------------------
    // 5. RUTA NO ENCONTRADA
    // ----------------------------------------------

    responderJson([
        'exito' => false,
        'mensaje' => 'Ruta no encontrada.'
    ], 404);

} catch (InvalidArgumentException $e) {
    responderJson([
        'exito' => false,
        'mensaje' => $e->getMessage()
    ], 400);

} catch (Throwable $e) {
    error_log((string) $e);

    responderJson([
        'exito' => false,
        'mensaje' => 'Error interno del servidor.'
    ], 500);
}
