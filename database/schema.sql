
-- Aplicación de gestión de reservas de alojamiento
-- Compatible con MySQL / MariaDB

CREATE TABLE reserva (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped VARCHAR(120) NOT NULL,
    email_huesped VARCHAR(254) NOT NULL,
    nombre_alojamiento VARCHAR(150) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada')
        NOT NULL DEFAULT 'pendiente',
    importe DECIMAL(10, 2) NOT NULL,
    notas TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_reserva_fechas
        CHECK (fecha_salida > fecha_entrada),

    CONSTRAINT chk_reserva_importe
        CHECK (importe >= 0),

    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_fechas (fecha_entrada, fecha_salida),
    INDEX idx_reserva_huesped (nombre_huesped)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE reserva_evento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_evento_reserva
        FOREIGN KEY (reserva_id)
        REFERENCES reserva(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_evento_reserva_fecha (reserva_id, fecha_creacion)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- Datos de ejemplo
INSERT INTO reserva (
    nombre_huesped,
    email_huesped,
    nombre_alojamiento,
    fecha_entrada,
    fecha_salida,
    estado,
    importe,
    notas
) VALUES
(
    'Laura Sánchez',
    'laura.sanchez@example.com',
    'Casa Rural El Roble',
    '2026-11-05',
    '2026-11-08',
    'confirmada',
    240.00,
    'Prefiere una habitación tranquila'
),
(
    'Carlos Martín',
    'carlos.martin@example.com',
    'Apartamentos La Plaza',
    '2026-11-12',
    '2026-11-15',
    'pendiente',
    180.50,
    'Llegada prevista por la tarde'
),
(
    'Ana García',
    'ana.garcia@example.com',
    'Hotel Sierra Verde',
    '2026-12-01',
    '2026-12-04',
    'cancelada',
    315.00,
    NULL
),
(
    'David López',
    'david.lopez@example.com',
    'Casa Rural El Roble',
    '2026-12-10',
    '2026-12-12',
    'pendiente',
    150.00,
    'Viaja con un niño'
),
(
    'Marta Fernández',
    'marta.fernandez@example.com',
    'Apartamentos La Plaza',
    '2026-11-20',
    '2026-11-23',
    'confirmada',
    210.00,
    NULL
);


-- Historial inicial de cada reserva
INSERT INTO reserva_evento (
    reserva_id,
    tipo,
    descripcion
) VALUES
(1, 'creacion', 'Reserva creada'),
(2, 'creacion', 'Reserva creada'),
(2, 'estado', 'Reserva confirmada inicialmente como pendiente'),
(3, 'creacion', 'Reserva creada'),
(3, 'estado', 'Reserva cancelada'),
(4, 'creacion', 'Reserva creada'),
(5, 'creacion', 'Reserva creada'),
(5, 'estado', 'Reserva confirmada');
