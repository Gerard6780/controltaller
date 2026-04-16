# Proyecto: Control de Entradas Taller

Sistema ligero de gestión para registros de reparaciones y ensamblaje de equipos (creaciones), optimizado para terminales TPV (1024x768).

## Estructura del Proyecto

### Backend (PHP)
- **db.php**: Centraliza la conexión a la base de datos MySQL.
- **save.php / update.php**: Gestión de persistencia.
- **get_records.php**: Motor de búsqueda y filtrado.
- **get_next_id.php**: Generador de referencias correlativas (R-XXXX, C-XXXX).
- **print.php**: Motor de impresión de etiquetas (Zebra / Brother).
- **change_status.php / delete.php**: Acciones rápidas sobre registros.

### Frontend
- **index.html**: Interfaz principal del TPV.
- **app.js**: Lógica de navegación, carga dinámica y procesos de formulario.
- **style.css**: Diseño visual (Modo Oscuro / Terra Design).

## Base de Datos
El esquema completo se encuentra en `tpv_db.sql`.
- **Reparaciones**: Almacena clientes, técnicos, accesorios y descripción del fallo.
- **Creaciones**: Almacena fichas técnicas de equipos montados con sus respectivos P/N y S/N en formato JSON.

## Requisitos
- Servidor Web (Apache/Nginx).
- PHP 7.4 o superior (con extensión PDO MySQL).
- MySQL 5.7 o superior.

**Desarrollado y optimizado por Antigravity AI para Gerard Anta.**