# Decisiones de arquitectura

## ADR-001 — Laravel 13 sobre un repositorio vacío

- Estado: aceptada
- Contexto: `checaflow` solo tenía el commit inicial y `.gitignore`. No había `Makefile`, backend ni `mobile/`.
- Decisión: se instaló Laravel 13.11 (framework 13.43) dentro del repo, conservando `.git`. No se creó la app React Native.
- Consecuencia: las migraciones corren con Artisan. La Fase 3 necesita Node y un proyecto React Native nuevo.

## ADR-002 — Hexagonal por feature, solo Domain y Application al inicio

- Estado: aceptada
- Contexto: el producto debe crecer de asistencia hacia RH sin un `app/Services` plano.
- Decisión: `app/Features/{Feature}/Domain` y `Application`. Los casos se nombran verbo + sustantivo, sin sufijo `UseCase`. Domain no depende del framework. Los adaptadores Eloquent, controladores y FormRequests quedan para cuando se pidan.
- Consecuencia: Fase 1 no creó esas carpetas vacías.

## ADR-003 — `users.company_id` con restrict y CHECK de rol

- Estado: aceptada
- Contexto: `company_id` es nulo solo para `super_admin`. Un `ON DELETE SET NULL` dejaría empleados sin tenant y fuera de los filtros.
- Decisión: `restrictOnDelete`. Además, en MySQL, MariaDB y PostgreSQL, el CHECK `users_role_company_consistency` obliga: `super_admin` sin empresa, cualquier otro rol con empresa.
- Consecuencia: no se puede borrar la fila de una empresa que aún tiene usuarios. SQLite no recibe ese CHECK porque esta versión de Laravel no expone `check()` en el schema builder y `ALTER TABLE ... ADD CONSTRAINT` no es portable a SQLite.

## ADR-004 — Índice de marcas no único

- Estado: aceptada
- Contexto: el esquema pide índice `(employee_profile_id, work_date, punch_type)`, y único solo donde lo dice de forma explícita.
- Decisión: el índice no es único. El rechazo de una marca duplicada vive en `RegisterPunch`.
- Consecuencia: dos escrituras concurrentes del mismo `punch_type` pueden colar hasta que esa regla exista en aplicación o se promueva el índice a único.

## ADR-005 — Modelo `User` alineado a la migración

- Estado: aceptada
- Contexto: el esqueleto traía `email_verified_at`. La tabla pedida no lo incluye y sí incluye `company_id`, `role`, `is_active` y soft delete.
- Decisión: se quitó `email_verified_at` del modelo y del factory. El factory crea un `super_admin` sin empresa, que es el único estado válido sin un tenant.
- Consecuencia: no hay verificación de correo del framework hasta que se pida.

## ADR-006 — Docker local con Nginx, PHP-FPM, MySQL y Redis

- Estado: aceptada
- Contexto: el esqueleto quedó en SQLite del host, sin `Makefile` ni Compose.
- Decisión: el entorno de trabajo es `docker-compose.yml`: `nginx-checa`, `php-checa` (PHP 8.4-FPM), `horizon-checa`, `scheduler-checa`, `mysql-checa`, `redis-checa` y `pma-checa`. Staging (`docker-compose-staging.yaml`) no trae MySQL ni phpMyAdmin y usa la red externa `appnetwork`. Artisan se ejecuta con `docker exec php-checa`.
- Consecuencia: `.env` apunta a los hostnames `mysql` y `redis`. Los puertos del host son 8098 (HTTP), 8097 (phpMyAdmin), 4336 (MySQL) y 6386 (Redis). El 8096 ya lo usa otro proyecto. `make restart` borra y recrea la base `checaflow`, migra y ejecuta `initial-setup`. No borra el volumen de Docker. La red local queda fija en `172.16.60.0/24` porque los pools automáticos de Docker de esta máquina ya no tienen subred libre.

## ADR-007 — Reglas de la marca y del resumen diario

- Estado: aceptada
- Contexto: el índice de `punch_type` no es único y el cliente móvil puede mandar una hora o una sucursal falsas.
- Decisión: `RegisterPunch` exige el `PunchType` pedido. `AttendanceDay` rechaza duplicado, salto y día ya cerrado. La sucursal sale del empleado, no del comando. App y web ignoran la hora del cliente y usan `CompanyClock`; `manual_adjustment` sí exige `punchedAt`. La geocerca, si es obligatoria, lanza `PunchOutsideGeofence` y no guarda la fila. El estado prioriza `absent`, luego `late` (aunque el ciclo siga abierto), luego `incomplete`, luego `on_time`.
- Consecuencia: dos marcas concurrentes iguales todavía pueden colar por el índice no único. El cupo `max_employees` se valida en `CreateEmployee` antes de persistir.

## ADR-008 — La app móvil nace sin proyecto nativo

- Estado: aceptada
- Contexto: la Fase 3 pide `domain/` y `application/` en `mobile/`, y deja la red y el almacenamiento para después. No había carpeta `mobile/`.
- Decisión: TypeScript en `mobile/src/features` para `auth`, `attendance`, `history` y `profile`. Los ports son interfaces. No se generó el proyecto de React Native ni un cliente HTTP. `RegisterPunch` en el teléfono no manda `punchedAt` ni `branch_id`.
- Consecuencia: `npm test` y `npm run typecheck` dentro de `mobile/` cubren esta capa. Las pantallas y el GPS del dispositivo entran detrás de los ports.

## ADR-009 — Origen de la marca

- Estado: aceptada
- Contexto: `attendance_logs.source` ya estaba migrado como `mobile_app`, `web`, `manual_adjustment`. El checado del empleado irá primero en el navegador y el panel de HR es otra vía.
- Decisión: los valores quedan `mobile_web`, `mobile_app`, `web_admin` y `manual_adjustment`. La migración `2026_11_20_160000_expand_attendance_log_sources` convierte un `web` ya guardado en `mobile_web`. No se reescribió la migración ya aplicada.
- Consecuencia: app y web del empleado, y el panel, siguen usando la hora del servidor. Solo `manual_adjustment` trae `punchedAt`.

## ADR-010 — Checador web y panel sobre los ports existentes

- Estado: aceptada
- Contexto: el empleado marca primero en el navegador. HR y el administrador de la empresa ven el día. Ya existían los casos de Application, sin adaptador ni rutas.
- Decisión: Blade, Tailwind y Vite, que es el frontend que trajo Laravel. Los adaptadores Eloquent implementan los ports y se registran en `AppServiceProvider`. La sesión usa el guard web. El checador envía `mobile_web` y la hora la pone `CompanyClock`.
- Consecuencia: no hay proyecto React en el panel. `super_admin` no recibe un selector de empresa. La alta de un empleado crea el `users` y el perfil en una transacción del controller.

## ADR-011 — El actor viaja en el comando

- Estado: aceptada
- Contexto: un usuario de una empresa no puede crear datos de otra, y `super_admin` no opera sucursales, horarios ni empleados. Su alta es el administrador de una empresa activa.
- Decisión: `app/Features/Users` expone `OpenCompanyAccount`, `CreateCompany`, `CreateCompanyAdmin`, `CreateCompanyUser`, `ActivateCompanyAdmin` y `ToggleUserStatus`. Los DTOs de alta llevan `actorRole` y `actorCompanyId`. `AuthorizeActor` consulta `PermissionChecker` antes de guardar. `OpenEmployeeAccount` conserva `EmployeeAccountRegistrar`. El password viaja en claro hasta `EloquentUserRepository`, que lo hashea.
- Consecuencia: `/platform` abre la cuenta (empresa + `company_admin`) en una transacción. `EloquentCompanyRepository`, `EloquentPlanRepository` y `EloquentUserRepository` implementan los ports. El panel de tenant pasa el rol de la sesión.

## ADR-014 — Planes comerciales y cupo de empleados

- Estado: aceptada
- Contexto: el alta de un tenant necesita un plan con rango de empleados y un tablero de uso para `super_admin`.
- Decisión: tabla `plans` con `micro`, `small_business` y `enterprise`. `companies.plan_id` es FK. `CreateCompany` valida el cupo contra el plan. `PlatformHomeController` agrega empresas, empleados activos y marcas.
- Consecuencia: el formulario de `/platform` pide plan, cupo, datos del administrador (`nickname` obligatorio, `email` y `phone` opcionales) y muestra el tablero.

## ADR-015 — Maps, reportes por rango y alta de empleado con nickname

- Estado: aceptada
- Contexto: la geocerca se capturaba a mano; el reporte solo cubría un día; el alta de empleado exigía correo.
- Decisión: Google Maps JS con `GOOGLE_MAPS_API_KEY` (fallback a lat/lng manual). `GetCompanyAttendanceRange` sobre `daily_attendance_summaries` en `/admin/reports`. `OpenEmployeeAccount` pide `nickname`, teléfono opcional y correo opcional.
- Consecuencia: no hay feature `Reports`. El checador pide GPS al cargar y bloquea el submit si la sucursal exige geocerca sin punto.

## ADR-012 — Cliente web tipado sin pantallas nuevas

- Estado: aceptada
- Contexto: el navegador ya es Blade + Vite + Tailwind. Hacía falta un cliente tipado por rol antes de nuevas pantallas.
- Decisión: los tipos y los servicios viven en `resources/js/features`. Llaman rutas que ya existen. Si la ruta no existe, arman el request sin URL. No envían `company_id`. El destino del rol es un mapa: plataforma, panel o checador.
- Consecuencia: no hay SPA ni React. `resources/js/app.js` sigue siendo el reloj y el checador.

## ADR-013 — Catálogo de permisos sin Spatie

- Estado: aceptada
- Contexto: el MVP necesita saber qué rol puede operar cada caso, sin tablas ni paquetes todavía.
- Decisión: el catálogo es `PermissionName` en `Users`. El port es `PermissionChecker`. `RolePermissions` es el mapa. `AuthorizeActor` corre al inicio del caso. `super_admin` solo tiene `companies.manage` y `company_admins.manage`. `company_admin`, `hr` y `employee` usan el resto según ese mapa, siempre en su empresa.
- Consecuencia: no hay `spatie/laravel-permission`, `HasRoles` ni seeders de roles. El adaptador de ese paquete queda para un bloque posterior.

## ADR-016 — Admin UI lista + modal

- Estado: aceptada
- Contexto: Sucursales y Horarios usaban panel + formulario lateral; Empleados ya tenía lista + modal.
- Decisión: `admin/employees` es el canon. Branches, schedules, attendance y reports usan `cf-page-head` + un panel. Alta/edición en `cf-modal` con `_form` / `openModal`. Paleta `#1767E2` y blanco. `UpdateSchedule` completa la edición de horarios.
- Consecuencia: no hay formularios laterales en admin. Regla `.cursor/rules/04-admin-ui.mdc`.

## ADR-017 — Shells sin /admin, JSON en /admin, super_admin lectora

- Estado: aceptada
- Contexto: las listas debían paginarse y cargarse con fetch; `super_admin` necesitaba las mismas secciones sin mutar tenants.
- Decisión: páginas en `/{seccion}`; JSON y mutaciones en `/admin/{seccion}`; checador en `/punch`. `SearchCriteria`/`PagedResult`. `super_admin` gana `attendance.view_company_report` y ve listados (filtro empresa); mutaciones tenant siguen denegadas.
- Consecuencia: Home de `super_admin`/`company_admin`/`hr` es `/attendance`. `/platform` queda para alta de empresas.

## ADR-018 — SearchCriteria compartido con paginación

- Estado: aceptada
- Contexto: cada listado necesitaba sort tipado, filtros por agregados y paginación (`page`/`per_page`) ante volúmenes grandes.
- Decisión: base `App\Features\Shared\Domain\SearchCriteria` (sort whitelist + page + perPage). Cada feature extiende con sus filtros (`SearchEmployeesCriteria`, `SearchBranchesCriteria`, `SearchSchedulesCriteria`, `CompanyDailyReportCriteria`, `CompanyAttendanceRangeCriteria`). Campos permitidos viven en `config/{entity}.php` (`sortable`, `filterable`, `default_*`, `max_per_page`). HTTP arma el criteria con `App\Http\Search\SearchListInput`. Los `Search*` / `GetCompany*` devuelven `PagedResult`. Se elimina `PageQuery` / `RequestPageQuery`.
- Consecuencia: Domain no lee `config()`; la whitelist llega por el criteria. Lookups de selects usan `max_per_page`.
