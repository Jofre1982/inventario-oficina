# Inventario Oficina

Control de elementos para una alcaldía u oficina: **consumibles** (papel, tóner, aseo) y **devolutivos** (portátiles, herramientas) con entradas, salidas, préstamos, devoluciones, actas de entrega en PDF y alerta de préstamos vencidos.

Basado en [Inventario General](https://github.com/Jofre1982/inventario-general) (misma plantilla Black Dashboard, mismo instalador para Windows), actualizado a **Laravel 12** (con soporte de seguridad hasta febrero de 2027) y **PHP 8.2+**, que es la versión que trae el XAMPP actual.

## Qué hace

- **Elementos** con foto, nombre, código de barras, categoría, unidad de medida, serial/placa y ubicación. Dos tipos:
  - **Consumible:** sale y no regresa.
  - **Devolutivo:** se presta y debe regresar.
- **Entradas:** suman al almacén (proveedor y documento soporte opcionales). Al crear un elemento se puede escribir la cantidad que ya existe.
- **Salidas y préstamos:** responsable que recibe, dependencia, fecha de salida y, si hay devolutivos, fecha en que se deben devolver. Al registrar se abre el **acta de entrega en PDF** con espacio para firmas (entrega, recibe y visto bueno del jefe de la dependencia).
- **Devoluciones** de devolutivos, parciales o totales, con el estado en que regresan: **bueno** (vuelve a estar disponible), **dañado** o **incompleto** (queda en el almacén como no disponible hasta marcarlo como reparado o darlo de baja). Genera **acta de devolución**.
- **Préstamos vencidos:** alerta en el inicio, en la barra superior y en el menú, y un listado propio.
- **Reportes** (en pantalla y PDF): movimientos por fechas, por responsable y por dependencia, y qué tiene prestado cada persona.
- **Historial (kárdex)** de cada elemento: todo lo que entró, salió, se prestó o se devolvió, con el saldo.
- **Lector de código de barras:** en entradas y salidas, y en el buscador de la barra superior (abre el elemento).
- **Usuarios y roles:** Administrador (todo), Almacenista (todo menos usuarios, roles y datos de la entidad) y Consulta (solo ver). Se pueden crear roles nuevos marcando permisos.
- **Inicio con datos reales:** vencidos, prestado ahora, salidas del mes, stock bajo, préstamos que vencen esta semana y gráfica de los últimos 14 días.

Se quitó del proyecto base todo lo de ventas, clientes, precios, impuestos y tickets.

## Instalación en Windows

Funciona sin internet una vez instalado. Solo necesita **XAMPP con PHP 8.2 o superior** en `C:\xampp`.

1. Doble clic en **`instalar.bat`** (pide permiso de administrador solo). Activa las extensiones de PHP, enciende MySQL, crea la base de datos `inventario_oficina`, pregunta los datos de la entidad, quién firma las actas y la contraseña del administrador, y crea el ícono **Inventario Oficina** en el escritorio.
2. Para usarlo cada día: doble clic en el ícono. Enciende MySQL y el sistema sin ventanas negras y lo abre como ventana de programa (Edge o Chrome en modo aplicación).
3. Cada día que se abre guarda una copia de la base de datos en `respaldos\` (se conservan 30). Con **`restaurar-respaldo.bat`** se vuelve a una copia anterior.

Usa el puerto **8010**, así que puede convivir con Inventario General (puerto 8000) en el mismo computador; ambos comparten el MySQL de XAMPP.

### Llevarlo a otra oficina

En un computador donde ya funcione, ejecute **`preparar-paquete.bat`**: deja en el escritorio `InventarioOficina-instalador.zip` con las librerías incluidas y **sin** sus datos, su `.env`, sus fotos ni sus respaldos. En el destino: instalar XAMPP, descomprimir el .zip en `C:\` y ejecutar `C:\InventarioOficina\instalar.bat`. Las instrucciones están en `LEEME-INSTALACION.txt`.

| Archivo | Para qué |
|---|---|
| `instalar.bat` | Instalación completa (se puede repetir sin perder datos) |
| `InventarioOficina.vbs` | Lo que abre el ícono del escritorio |
| `windows\arrancar.bat` | Enciende MySQL y el servidor ocultos, hace el respaldo del día |
| `windows\detener.bat` | Apaga todo (opcional) |
| `restaurar-respaldo.bat` | Vuelve a una copia de seguridad (antes guarda el estado actual) |
| `preparar-paquete.bat` | Arma el .zip para otro computador |

La instalación queda en modo producción (`APP_DEBUG=false`): los errores no se muestran en pantalla y quedan en `storage\logs\laravel.log`.

## Instalación manual (para programar)

```bash
git clone https://github.com/Jofre1982/inventario-oficina.git
cd inventario-oficina
php composer.phar install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
```

Cree una base de datos vacía `inventario_oficina` (utf8mb4) y revise los datos en `.env`. Luego:

```bash
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # opcional: datos de ejemplo
cd public && php -S 127.0.0.1:8010 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Entre a http://127.0.0.1:8010 con **admin@inventario.com** / **secret** y cambie la contraseña en *Mi perfil* (el instalador de Windows ya lo pide).

## Cómo funcionan las existencias

| Columna | Qué es |
|---|---|
| Disponible | Unidades en el almacén que se pueden entregar |
| Prestado | Devolutivos entregados que no han regresado (se calcula con las salidas) |
| No disponible | Devueltos dañados o incompletos; no se pueden prestar hasta marcarlos como reparados o darlos de baja |

Todo cambio pasa por `app/Services/InventoryService.php` dentro de una transacción con bloqueo de filas y queda en la tabla `movements`. El stock nunca queda negativo. Una entrada se puede anular si sus unidades siguen en el almacén; una salida se puede anular si todavía no tiene devoluciones.

## Pruebas automáticas

```bash
php artisan test
```

Cubren entradas, salidas, préstamos, devoluciones por estado, vencidos, anulaciones, bajas y reparados, fechas no válidas, permisos de cada rol, que todas las pantallas carguen y que se generen los PDF. También se pasaron contra MariaDB (`DB_CONNECTION=mysql DB_DATABASE=inventario_test php vendor/bin/phpunit`).

## Estructura relevante

| Archivo | Para qué |
|---|---|
| `app/Services/InventoryService.php` | Todas las operaciones que mueven el stock |
| `app/Http/Controllers/Controller.php` | `permissions()`: une cada acción con su permiso |
| `app/Providers/AppServiceProvider.php` | Roles y permisos (reemplaza a Shinobi, con las mismas tablas) |
| `database/seeders/PermissionsTableSeeder.php` | Lista de permisos y roles por defecto |
| `database/seeders/DemoSeeder.php` | Datos de ejemplo |
| `app/Support/Pdf.php` | Genera los PDF con dompdf |
| `resources/views/delivery/pdf.blade.php` | Acta de entrega |
| `public/js/inventario.js` | Pantallas de entrada y salida con lector de código de barras |

## Licencia

MIT
