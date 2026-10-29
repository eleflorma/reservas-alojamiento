
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
        celda.colSpan = 9;
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

        // Creamos la columna de acciones.
        const celdaAcciones = document.createElement('td');
        celdaAcciones.classList.add('acciones');

        const botonDetalles = document.createElement('button');
        botonDetalles.type = 'button';
        botonDetalles.classList.add('secundario');
        botonDetalles.dataset.accion = 'detalles';
        botonDetalles.dataset.id = reserva.id;
        botonDetalles.textContent = 'Ver detalles';

        celdaAcciones.appendChild(botonDetalles);

        if (reserva.estado !== 'cancelada') {
            const botonCancelar = document.createElement('button');
            botonCancelar.type = 'button';
            botonCancelar.classList.add('peligro');
            botonCancelar.dataset.accion = 'cancelar';
            botonCancelar.dataset.id = reserva.id;
            botonCancelar.textContent = 'Cancelar';

            celdaAcciones.appendChild(botonCancelar);
        }

        fila.appendChild(celdaAcciones);
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

const dialogoDetalles = document.querySelector('#dialogo-detalles');
const contenidoDetalles = document.querySelector('#contenido-detalles');
const botonCerrarDetalles = document.querySelector('#cerrar-detalles');

// Cierra la ventana de detalles.
botonCerrarDetalles.addEventListener('click', () => {
    dialogoDetalles.close();
});

// Añade un dato a la ventana de detalles de forma segura.
function anadirDato(contenedor, etiqueta, valor) {
    const parrafo = document.createElement('p');
    const negrita = document.createElement('strong');

    negrita.textContent = `${etiqueta}: `;
    parrafo.appendChild(negrita);
    parrafo.appendChild(
        document.createTextNode(valor ?? 'No indicado')
    );

    contenedor.appendChild(parrafo);
}

// Carga y muestra los detalles de una reserva.
async function mostrarDetalles(id) {
    contenidoDetalles.replaceChildren();

    const cargando = document.createElement('p');
    cargando.textContent = 'Cargando detalles...';
    contenidoDetalles.appendChild(cargando);

    dialogoDetalles.showModal();

    try {
        const respuesta = await fetch(`${apiUrl}/${id}`);
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(
                resultado.mensaje || 'No se pudieron cargar los detalles.'
            );
        }

        const reserva = resultado.datos;
        contenidoDetalles.replaceChildren();

        const seccionDatos = document.createElement('section');
        const tituloDatos = document.createElement('h3');
        tituloDatos.textContent = 'Información de la reserva';
        seccionDatos.appendChild(tituloDatos);

        anadirDato(seccionDatos, 'Número', reserva.id);
        anadirDato(seccionDatos, 'Huésped', reserva.nombre_huesped);
        anadirDato(seccionDatos, 'Correo electrónico', reserva.email_huesped);
        anadirDato(seccionDatos, 'Alojamiento', reserva.nombre_alojamiento);
        anadirDato(seccionDatos, 'Entrada', reserva.fecha_entrada);
        anadirDato(seccionDatos, 'Salida', reserva.fecha_salida);
        anadirDato(seccionDatos, 'Estado', reserva.estado);
        anadirDato(
            seccionDatos,
            'Importe',
            formatoImporte.format(Number(reserva.importe))
        );
        anadirDato(seccionDatos, 'Notas', reserva.notas);
        anadirDato(seccionDatos, 'Fecha de creación', reserva.fecha_creacion);

        contenidoDetalles.appendChild(seccionDatos);

        const seccionHistorial = document.createElement('section');
        const tituloHistorial = document.createElement('h3');
        tituloHistorial.textContent = 'Historial de la reserva';
        seccionHistorial.appendChild(tituloHistorial);

        const historial = document.createElement('ul');

        if (reserva.historial && reserva.historial.length > 0) {
            reserva.historial.forEach(evento => {
                const elemento = document.createElement('li');
                elemento.textContent =
                    `${evento.fecha_creacion} — ${evento.descripcion}`;

                historial.appendChild(elemento);
            });
        } else {
            const elemento = document.createElement('li');
            elemento.textContent = 'No hay eventos registrados.';
            historial.appendChild(elemento);
        }

        seccionHistorial.appendChild(historial);
        contenidoDetalles.appendChild(seccionHistorial);

    } catch (error) {
        contenidoDetalles.replaceChildren();

        const mensajeError = document.createElement('p');
        mensajeError.classList.add('error');
        mensajeError.textContent = error.message;

        contenidoDetalles.appendChild(mensajeError);
    }
}

// Gestiona los botones de acciones de la tabla.
tabla.addEventListener('click', async evento => {
    const boton = evento.target.closest('button[data-accion]');

    if (!boton) {
        return;
    }

    const id = boton.dataset.id;
    const accion = boton.dataset.accion;

    if (accion === 'detalles') {
        await mostrarDetalles(id);
        return;
    }

    if (accion === 'cancelar') {
        const confirmar = window.confirm(
            `¿Seguro que quieres cancelar la reserva número ${id}?`
        );

        if (!confirmar) {
            return;
        }

        boton.disabled = true;
        mensaje.textContent = 'Cancelando reserva...';
        mensaje.classList.remove('error');

        try {
            const respuesta = await fetch(`${apiUrl}/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    estado: 'cancelada'
                })
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.exito) {
                throw new Error(
                    resultado.mensaje || 'No se pudo cancelar la reserva.'
                );
            }

            mensaje.textContent = `La reserva ${id} se ha cancelado correctamente.`;

            // Volvemos a consultar la API para actualizar la tabla.
            await cargarReservas();

        } catch (error) {
            mensaje.textContent = error.message;
            mensaje.classList.add('error');

        } finally {
            boton.disabled = false;
        }
    }
});
