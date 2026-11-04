# Sistema de reservas de alojamiento

Aplicación web desarrollada en PHP nativo para gestionar reservas de alojamientos. Permite consultar, filtrar, crear, confirmar y cancelar reservas, así como consultar sus detalles e historial de cambios.

## Tecnologías utilizadas

- PHP 8.1
- MySQL / MariaDB
- Twig 3
- HTML5 y CSS3
- JavaScript
- Apache
- Composer

## Requisitos previos

- PHP 8.1 o superior.
- Apache con el módulo `mod_rewrite` habilitado.
- MySQL o MariaDB.
- Composer.
- Extensiones PHP PDO y PDO MySQL.

## Instalación

1. Clonar el repositorio:

   ```bash
   git clone https://github.com/eleflorma/reservas-alojamiento.git
   ```

2. Acceder a la carpeta del proyecto:

   ```bash
   cd reservas-alojamiento
   ```

3. Instalar las dependencias:

   ```bash
   composer install
   ```

4. Crear una base de datos MySQL llamada `reservas_alojamiento`.

5. Importar el archivo `database/schema.sql` en la base de datos. Por ejemplo, desde phpMyAdmin, seleccionando la base de datos y utilizando la pestaña **Importar**.

6. Crear el archivo de configuración `.env` a partir del archivo de ejemplo:

   ```bash
   cp .env.example .env
   ```

   En Windows PowerShell también se puede utilizar:

   ```powershell
   Copy-Item .env.example .env
   ```

   Configurar en `.env` los datos de conexión a la base de datos:

   - `DB_HOST`: dirección del servidor.
   - `DB_PORT`: puerto de MySQL.
   - `DB_NAME`: nombre de la base de datos.
   - `DB_USER`: usuario de MySQL.
   - `DB_PASSWORD`: contraseña de MySQL.

   Los valores de `.env.example` corresponden a una configuración local de desarrollo. Deben adaptarse al entorno donde se instale la aplicación.

7. Iniciar Apache y MySQL desde XAMPP o desde el entorno correspondiente.

## Ejecución

Abrir en el navegador:

```text
http://localhost/reservas-alojamiento/public/reservas.php
```

## Funcionalidades

- Listado de reservas.
- Búsqueda por nombre y filtros por estado y fechas.
- Creación de reservas.
- Consulta de detalles de cada reserva.
- Historial de eventos.
- Confirmación y cancelación de reservas sin recargar manualmente la página.
- Validación de datos.
- Registro de cambios de estado.

## API

La aplicación dispone de una API REST desarrollada con PHP nativo. Sus endpoints principales son:

| Método | Endpoint |
|---|---|
| GET | `/reservas-alojamiento/public/api/reservas` |
| GET | `/reservas-alojamiento/public/api/reservas/{id}` |
| POST | `/reservas-alojamiento/public/api/reservas` |
| PATCH | `/reservas-alojamiento/public/api/reservas/{id}` |

Las respuestas de la API se devuelven en formato JSON.

## Estructura del proyecto

- `public/`: punto de entrada, interfaz y recursos públicos.
- `src/`: lógica de conexión, repositorios y servicios.
- `templates/`: plantillas Twig.
- `database/`: estructura SQL y datos iniciales de la base de datos.
- `vendor/`: dependencias instaladas mediante Composer.

## Autoría

Proyecto desarrollado como práctica de preparación para una prueba técnica de desarrollo PHP.