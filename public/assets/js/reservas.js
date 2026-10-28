
const formulario = document.querySelector('#form-filtros');
const tabla = document.querySelector('#tabla-reservas');
const mensaje = document.querySelector('#mensaje');

// Construimos la ruta a partir de la ubicación de reservas.php.
const rutaBase = window.location.pathname.replace(/reservas\.php$/, '');
const apiUrl = `${rutaBase}api/reservas`;

const formatoImporte = new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR'
});

function crearCelda(valor) {
    const celda = document.createElement('td');

    // textContent evita interpretar los datos como HTML.
    celda.textContent = valor ?? '';

    return celda;
}

function mostrarReservas(reservas) {
    tabla.replaceChildren();

    if (reservas.length === 0) {
        const fila = document.createElement('tr');
        const celda = crearCelda('No se encontraron reservas.');
        celda.colSpan = 8;
        fila.appendChild(celda);
        tabla.appendChild(fila);
        return;
    }

    reservas.forEach(reserva => {
        const fila = document.createElement('tr');

        fila.appendChild(crearCelda(reserva.id));
        fila.appendChild(crearCelda(reserva.nombre_huesped));
        fila.appendChild(crearCelda(reserva.email_huesped));
        fila.appendChild(crearCelda(reserva.nombre_alojamiento));
        fila.appendChild(crearCelda(reserva.fecha_entrada));
        fila.appendChild(crearCelda(reserva.fecha_salida));

        const celdaEstado = document.createElement('td');
        const etiqueta = document.createElement('span');

        etiqueta.classList.add(
            'estado',
            `estado--${reserva.estado}`
        );
        etiqueta.textContent = reserva.estado;

        celdaEstado.appendChild(etiqueta);
        fila.appendChild(celdaEstado);

        const importe = Number(reserva.importe);
        fila.appendChild(
            crearCelda(
                Number.isFinite(importe)
                    ? formatoImporte.format(importe)
                    : reserva.importe
            )
        );

        tabla.appendChild(fila);
    });
}

async function cargarReservas() {
    mensaje.textContent = 'Cargando reservas...';
    mensaje.classList.remove('error');

    try {
        const parametros = new URLSearchParams();

        const busqueda = document.querySelector('#busqueda').value.trim();
        const estado = document.querySelector('#estado').value;
        const fechaDesde = document.querySelector('#fecha_desde').value;
        const fechaHasta = document.querySelector('#fecha_hasta').value;

        if (busqueda) parametros.set('busqueda', busqueda);
        if (estado) parametros.set('estado', estado);
        if (fechaDesde) parametros.set('fecha_desde', fechaDesde);
        if (fechaHasta) parametros.set('fecha_hasta', fechaHasta);

        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            throw new Error(
                'La fecha inicial no puede ser posterior a la fecha final.'
            );
        }

        const url = parametros.size
            ? `${apiUrl}?${parametros.toString()}`
            : apiUrl;

        const respuesta = await fetch(url);
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(
                resultado.mensaje || 'No se pudieron obtener las reservas.'
            );
        }

        mostrarReservas(resultado.datos);

        mensaje.textContent =
            `Reservas encontradas: ${resultado.total}`;

    } catch (error) {
        mensaje.textContent = error.message;
        mensaje.classList.add('error');
    }
}

formulario.addEventListener('submit', evento => {
    evento.preventDefault();
    cargarReservas();
});

formulario.addEventListener('reset', () => {
    // Esperamos a que el formulario restablezca sus valores.
    setTimeout(cargarReservas, 0);
});

// Carga inicial al abrir la página.
cargarReservas();

const formularioReserva = document.querySelector('#form-reserva');
const mensajeCreacion = document.querySelector('#mensaje-creacion');
const botonCrearReserva = document.querySelector('#btn-crear-reserva');

formularioReserva.addEventListener('submit', async evento => {
    evento.preventDefault();

    mensajeCreacion.textContent = '';
    mensajeCreacion.classList.remove('exito', 'error');
    botonCrearReserva.disabled = true;

    try {
        const datosFormulario = new FormData(formularioReserva);

        const datosReserva = {
            nombre_huesped: datosFormulario.get('nombre_huesped').trim(),
            email_huesped: datosFormulario.get('email_huesped').trim(),
            nombre_alojamiento: datosFormulario.get('nombre_alojamiento').trim(),
            fecha_entrada: datosFormulario.get('fecha_entrada'),
            fecha_salida: datosFormulario.get('fecha_salida'),
            importe: Number(datosFormulario.get('importe')),
            notas: datosFormulario.get('notas').trim()
        };

        const respuesta = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datosReserva)
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(
                resultado.mensaje || 'No se pudo crear la reserva.'
            );
        }

        mensajeCreacion.textContent =
            `Reserva creada correctamente. ID: ${resultado.id}`;

        mensajeCreacion.classList.add('exito');

        formularioReserva.reset();

        // Recargamos los datos de la tabla sin actualizar la página.
        await cargarReservas();

    } catch (error) {
        mensajeCreacion.textContent = error.message;
        mensajeCreacion.classList.add('error');
    } finally {
        botonCrearReserva.disabled = false;
    }
});
