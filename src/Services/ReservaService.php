<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReservaRepository;
use DateTimeImmutable;
use InvalidArgumentException;

class ReservaService
{
    public function __construct(
        private ReservaRepository $repositorio
    ) {
    }

    /**
     * Valida los datos y crea una reserva.
     *
     * Devuelve el ID de la reserva creada.
     */
    public function crear(array $datos): int
    {
        $camposObligatorios = [
            'nombre_huesped',
            'email_huesped',
            'nombre_alojamiento',
            'fecha_entrada',
            'fecha_salida',
            'importe',
        ];

        foreach ($camposObligatorios as $campo) {
            if (
                !isset($datos[$campo])
                || (
                    is_string($datos[$campo])
                    && trim($datos[$campo]) === ''
                )
            ) {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        // Comprobar los tipos de los campos de texto.
        $camposTexto = [
            'nombre_huesped',
            'email_huesped',
            'nombre_alojamiento',
            'fecha_entrada',
            'fecha_salida',
        ];

        foreach ($camposTexto as $campo) {
            if (!is_string($datos[$campo])) {
                throw new InvalidArgumentException(
                    "El campo {$campo} debe ser texto."
                );
            }

            $datos[$campo] = trim($datos[$campo]);
        }

        if (mb_strlen($datos['nombre_huesped']) > 120) {
            throw new InvalidArgumentException(
                'El nombre del huésped no puede superar 120 caracteres.'
            );
        }

        if (
            mb_strlen($datos['email_huesped']) > 254
            || !filter_var(
                $datos['email_huesped'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'El email no es válido.'
            );
        }

        if (mb_strlen($datos['nombre_alojamiento']) > 150) {
            throw new InvalidArgumentException(
                'El nombre del alojamiento no puede superar 150 caracteres.'
            );
        }

        // Comprobar que las fechas existen y tienen formato YYYY-MM-DD.
        $entrada = $this->validarFecha(
            $datos['fecha_entrada'],
            'fecha_entrada'
        );

        $salida = $this->validarFecha(
            $datos['fecha_salida'],
            'fecha_salida'
        );

        if ($salida <= $entrada) {
            throw new InvalidArgumentException(
                'La fecha de salida debe ser posterior a la de entrada.'
            );
        }

        // Validar el importe sin aceptar valores negativos.
        if (
            !is_int($datos['importe'])
            && !is_float($datos['importe'])
            && !(
                is_string($datos['importe'])
                && is_numeric($datos['importe'])
            )
        ) {
            throw new InvalidArgumentException(
                'El importe debe ser un número válido.'
            );
        }

        $importe = (float) $datos['importe'];

        if (!is_finite($importe) || $importe < 0) {
            throw new InvalidArgumentException(
                'El importe no puede ser negativo.'
            );
        }

        if (round($importe, 2) !== $importe) {
            throw new InvalidArgumentException(
                'El importe solo puede tener dos decimales.'
            );
        }

        // Las notas son opcionales.
        $notas = $datos['notas'] ?? null;

        if ($notas !== null && !is_string($notas)) {
            throw new InvalidArgumentException(
                'Las notas deben ser texto.'
            );
        }

        if ($notas !== null && mb_strlen($notas) > 65535) {
            throw new InvalidArgumentException(
                'Las notas son demasiado largas.'
            );
        }

        return $this->repositorio->crear([
            'nombre_huesped' => $datos['nombre_huesped'],
            'email_huesped' => $datos['email_huesped'],
            'nombre_alojamiento' => $datos['nombre_alojamiento'],
            'fecha_entrada' => $datos['fecha_entrada'],
            'fecha_salida' => $datos['fecha_salida'],
            'importe' => number_format($importe, 2, '.', ''),
            'notas' => $notas,
        ]);
    }

    /**
     * Cancela una reserva existente.
     *
     * Devuelve null si la reserva no existe.
     */
    public function cancelar(int $id): ?array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la reserva no es válido.'
            );
        }

        $reserva = $this->repositorio->obtenerPorId($id);

        if ($reserva === null) {
            return null;
        }

        if ($reserva['estado'] === 'cancelada') {
            throw new InvalidArgumentException(
                'La reserva ya está cancelada.'
            );
        }

        return $this->repositorio->cambiarEstado(
            $id,
            'cancelada'
        );
    }

    /**
     * Comprueba que una fecha sea válida y tenga formato YYYY-MM-DD.
     */
    private function validarFecha(
        string $fecha,
        string $campo
    ): DateTimeImmutable {
        $fechaValidada = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fecha
        );

        $errores = DateTimeImmutable::getLastErrors();

        if (
            $fechaValidada === false
            || (
                $errores !== false
                && (
                    $errores['warning_count'] > 0
                    || $errores['error_count'] > 0
                )
            )
            || $fechaValidada->format('Y-m-d') !== $fecha
        ) {
            throw new InvalidArgumentException(
                "El campo {$campo} no contiene una fecha válida (YYYY-MM-DD)."
            );
        }

        return $fechaValidada;
    }
}
