# ChecaFlow — guía de implementación

Proceso de cada cambio:

Comprender → identificar el feature → leer su regla → buscar un equivalente en este repo → cambio mínimo → test → actualizar `AGENTS.md`, `.cursor/rules` y `docs/AI_*` solo si cambió arquitectura, dominio, colas, API o permisos.

Hoy el único código de negocio persistido son las migraciones. No hay un CRUD de referencia todavía. La primera feature que se escriba (Fase 2, asistencia y catálogos) pasa a ser el ejemplo a copiar.

## Entorno Docker

`make up` levanta Nginx, PHP, Horizon, scheduler, MySQL, Redis y phpMyAdmin.

```bash
make up
make install
docker exec php-checa php artisan migrate
make test
```

Artisan y Composer van dentro de `php-checa`. `make enter` abre bash ahí. `make clear` limpia cachés y reinicia Horizon y la cola. `make down` apaga los contenedores. `make restart` los vuelve a crear sin borrar volúmenes.

Puertos del host: HTTP `8098`, phpMyAdmin `8097`, MySQL `4336`, Redis `6386`. Staging usa `docker-compose-staging.yaml` y no incluye MySQL ni phpMyAdmin.

## Migración nueva

Archivo en `database/migrations`, en inglés, con `company_id` y el `onDelete` ya usado en las tablas hermanas.

Orden actual:

1. `companies`
2. `users` (y tablas de sesión que trajo Laravel)
3. `cache`, `jobs`
4. `branches`, `schedules`
5. `employee_profiles`
6. `attendance_logs`
7. `daily_attendance_summaries`

No edites una migración ya aplicada en un entorno compartido. Agrega otra.

## Feature de aplicación (Fase 2)

Ruta: `app/Features/{Feature}/Application/{VerbNoun}.php`.

```php
final class RegisterPunch
{
    public function __construct(private AttendanceLogRepository $logs) {}

    public function handle(RegisterPunchData $command): void
    {
        if ($employee->companyId() !== $command->companyId) {
            throw new EmployeeDoesNotBelongToCompany();
        }
    }
}
```

- El DTO de entrada es `readonly`.
- El repositorio es una interfaz en `Domain`. No pongas Eloquent en esa carpeta.
- Sin `else`. Una guarda y retorno, o una excepción de dominio con nombre claro.

Acciones ya nombradas para asistencia:

| Clase | Responsabilidad |
| --- | --- |
| `RegisterPunch` | Pertenencia, secuencia, geocerca, guardar, recalcular |
| `CalculateDailyAttendance` | Minutos y `status`, persistir el resumen |
| `GetTodayEmployeeStatus` | Marcas del día, estado y siguiente `PunchType` |
| `GetCompanyDailyReport` | Resumen del día por `company_id`, fecha y sucursal opcional |

Catálogos previstos: `CreateEmployee`, `SearchEmployees`, `AssignEmployeeSchedule`.

## Filtros

El criterio de búsqueda es un DTO. Las columnas ordenables salen de una constante blanca del propio feature. Si la columna pedida no está en la lista, se rechaza. No se concatena a SQL.

## Multi-tenant

Cada método público de Application recibe `companyId` y lo compara con el agregado antes de leer o escribir. Un `employee_profile` de otra empresa es un error de dominio, no un resultado vacío silencioso.

## Qué no hacer en un cambio pequeño

- No agregar controladores ni FormRequests si el pedido es de dominio.
- No crear el otro feature “porque va a hacer falta”.
- No renombrar columnas ya migradas para corregir estilo.
