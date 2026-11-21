# ChecaFlow — guía de implementación

Proceso de cada cambio:

Comprender → identificar el feature → leer su regla → buscar un equivalente en este repo → cambio mínimo → test → actualizar `AGENTS.md`, `.cursor/rules` y `docs/AI_*` solo si cambió arquitectura, dominio, colas, API o permisos.

El ejemplo a copiar es `app/Features/Attendance/Application/RegisterPunch.php`: un caso corto, ports en el constructor, guardias y una llamada a `CalculateDailyAttendance`. Los catálogos equivalentes son `CreateEmployee`, `SearchEmployees` y `AssignEmployeeSchedule`.

## Entorno Docker

`make up` levanta Nginx, PHP, Horizon, scheduler, MySQL, Redis y phpMyAdmin.

```bash
make up
make install
docker exec php-checa php artisan migrate
make test
```

Artisan y Composer van dentro de `php-checa`. `make enter` abre bash ahí. `make clear` limpia cachés y reinicia Horizon y la cola. `make down` apaga los contenedores. `make restart` los vuelve a crear, borra la base `checaflow`, la crea de nuevo, corre las migraciones y `php artisan initial-setup`. Ese comando (no un seeder) crea al administrador de plataforma.

Datos masivos de prueba (no borra nada):

```bash
docker exec php-checa php artisan fake:seed --companies=20 --employees=200 --branches=5 --schedules=3 --days=30
```

Si omites `--schedules`, el comando elige un número al azar entre 10 y 1000 horarios por empresa. Contraseña de las cuentas fake: `password`. Imprime nicknames `…-admin`, `…-hr`, `…-e1`.

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

## Feature de aplicación

Ruta: `app/Features/{Feature}/Application/{VerbNoun}.php`. El DTO vive en `Domain` y es `readonly`. El repositorio es una interfaz. Sin `else`.

| Clase | Responsabilidad |
| --- | --- |
| `RegisterPunch` | Pertenencia, secuencia, geocerca, guardar, recalcular |
| `CalculateDailyAttendance` | Minutos y `status`, persistir el resumen |
| `GetTodayEmployeeStatus` | Marcas del día, estado y siguiente `PunchType` |
| `GetCompanyDailyReport` | Resumen del día por `CompanyId`, fecha y sucursal opcional |
| `GetCompanyAttendanceRange` | Resúmenes entre dos fechas para `/admin/reports` |
| `OpenCompanyAccount` | `super_admin` crea empresa + `company_admin` con plan de `plans` |
| `CreateCompany` | `super_admin` crea una empresa con `companies.manage` (`maxBranches` default 2) |
| `UpdateCompanyLimits` | `super_admin` actualiza `maxEmployees` / `maxBranches` sin bajar del conteo activo |
| `CreateCompanyAdmin` | `super_admin` crea un `company_admin` en una empresa activa |
| `CreateCompanyUser` | `company_admin` con `users.manage` crea `hr` o `employee` en su empresa |
| `ActivateCompanyAdmin` | `super_admin` con `company_admins.manage` activa o desactiva un `company_admin` |
| `ToggleUserStatus` | `company_admin` con `users.manage` cambia `is_active` de un `hr` o `employee` propio |
| `CreateEmployee` | Alta dentro del cupo, con sucursal y horario de la misma empresa. Permiso `employees.manage` |
| `SearchEmployees` | Listado con criterio tipado y columnas en lista blanca |
| `AssignEmployeeSchedule` | Cambia el horario si pertenece a la empresa y está activo. Permiso `employees.manage` |
| `CreateBranch`, `UpdateBranch`, `SearchBranches` | Sucursal y geocerca. Exigen `branches.manage` |
| `CreateSchedule`, `UpdateSchedule`, `SearchSchedules` | Horario y días laborables. Exigen `schedules.manage` |

## Pantalla del panel (shell + fetch + modal)

- Página: `GET /{seccion}` (Blade shell).
- Datos: `GET /admin/{seccion}?page=` JSON (`PagedResult`).
- Mutaciones: `POST`/`PUT` `/admin/{seccion}` (tenant).
- Checador: `/punch`.
- Copia `admin/employees/index` + `resources/js/features/admin/*-list.js`. `super_admin` ve las mismas secciones (lectura; filtro empresa). Detalle en `.cursor/rules/04-admin-ui.mdc`.

## Filtros

El criterio de búsqueda es un DTO. `App\Features\Shared\Domain\Sort` recibe la lista blanca del feature (`employee_code`, `name`, `status`, …). Una columna fuera de esa lista lanza `InvalidSortColumn` antes de tocar un repositorio.

## Multi-tenant

Cada método público de Application recibe `companyId` y lo compara con el agregado antes de leer o escribir. Un `employee_profile` de otra empresa es un error de dominio, no un resultado vacío silencioso.

Las altas de sucursal, horario y empleado también reciben `actorRole` y `actorCompanyId`. `AuthorizeActor` consulta el port `PermissionChecker` antes de guardar. La geocerca, el cupo y el horario no se reimplementan ahí.

## Permisos

Catálogo en `App\Features\Users\Domain\PermissionName`: `companies.manage`, `company_admins.manage`, `branches.manage`, `schedules.manage`, `users.manage`, `employees.manage`, `attendance.view_company_report`, `attendance.adjust_logs`, `attendance.punch`, `attendance.view_own_history`.

El port es `App\Features\Users\Domain\PermissionChecker` (`allows(UserRole, PermissionName): bool`). El mapa sin framework es `RolePermissions`. `AuthorizeActor` lo aplica. No hay adaptador de Spatie.

## App móvil

Misma idea que el backend, en TypeScript: `mobile/src/features/{feature}/application/{VerbNoun}.ts`.

`RequireSession` compara `companyId` y `employeeProfileId` antes de leer. `RegisterPunch` no envía hora ni sucursal. El historial ordena solo con `Sort` y las columnas `work_date`, `status`, `late_minutes`, `worked_minutes`.

`mobile/` es un proyecto de paquetes aparte; sus comandos se corren dentro de `mobile/`, no como workspace del raíz.

```bash
cd mobile && npm test && npm run typecheck
```

## Pantalla web

El controller vive en `app/Http/Controllers`. La validación, en `app/Http/Requests`. El `company_id` lo resuelve `App\Http\Auth\CurrentCompanyId` desde el usuario. No lo tomes del request.

Roles: `employee` → `attendance.today`; `company_admin` y `hr` → `admin.attendance.index`; `super_admin` → `platform.home`. El middleware es `EnsureRole`.

El cliente tipado está en `resources/js/features` y se revisa con `npm run typecheck`. Los servicios de empresa no aceptan `companyId` ni lo envían en el body o el query. `resources/js/features/super-admin` arma `OpenCompanyAccount` contra `POST /platform/companies`. Sucursales y checador cargan Google Maps con `GOOGLE_MAPS_API_KEY`.

Para probar el navegador en local:

```bash
docker exec php-checa php artisan db:seed --class=DemoCompanySeeder
```

Eso crea la empresa Acme si todavía no existe. Las cuentas de demostración están en el seeder.

El CSS sale de Vite (`npm run build` en el host). Los tests de pantalla usan `withoutVite()` y no dependen de `public/build`.

## Qué no hacer en un cambio pequeño

- No agregar controladores ni FormRequests si el pedido es de dominio.
- No crear el otro feature “porque va a hacer falta”.
- No renombrar columnas ya migradas para corregir estilo.
