# ChecaFlow — reglas para agentes

ChecaFlow es un SaaS B2B multi-tenant de asistencia (ciclo de 4 marcas). El backend es Laravel 13 / PHP 8.4 en Docker. La app móvil React Native todavía no existe.

1. Este repositorio es la fuente de verdad. El código reciente que cumple estas reglas gana sobre un patrón genérico de Laravel.
2. No copies dominio ni módulos de otros repos.
3. No inventes arquitectura, columnas, tablas ni módulos.
4. No acomplejes. El camino más corto que cumple el requisito.
5. Prohibido `else` / `elseif`. Usa guard clauses, early return y polimorfismo.
6. SOLID sin capas extra: una clase, una responsabilidad. Extiende con una clase nueva. El controller no posee negocio.
7. Eloquent vive en `app/Features/{Feature}/Infrastructure` e implementa un port. Domain y Application no ven modelos.
8. Domain no importa Eloquent, Facades ni clases de `Illuminate`.
9. Application depende de Ports (interfaces), no de modelos Eloquent.
10. Las clases de Application se nombran verbo + sustantivo. Nunca uses el sufijo `UseCase`.
11. Código, tablas, columnas, enums, nombres de clase y comentarios técnicos van en inglés claro. Sin abreviaturas crípticas. La copia visible al usuario (vistas, validación, flashes, errores de dominio en pantalla y la salida de `initial-setup`) va en español.
12. Toda operación de negocio exige `company_id`. Un usuario de empresa no puede leer datos de otra.
13. `super_admin` es el único rol con `company_id` nulo. El resto de roles exigen empresa. Lo refuerza el CHECK `users_role_company_consistency`.
14. Búsqueda y orden usan DTOs/criterios tipados y una lista blanca de columnas. Nunca un string arbitrario del cliente.
15. Antes de implementar: entiende el pedido → identifica el feature → lee la regla de ese módulo → busca un equivalente en este repo → cambio mínimo → test → actualiza `AGENTS.md`, la regla y `docs/AI_*` solo si cambió arquitectura, dominio, colas, API o permisos.
16. Features con código: `Users`, `Branches`, `Schedules`, `Employees`, `Attendance`, más `Shared` (`CompanyId`, `GeoPoint`, `Sort`). Los permisos viven en `Users` (`PermissionName`, port `PermissionChecker`, mapa `RolePermissions`, `AuthorizeActor`). No hay feature `Access` ni carpetas `Auth`, `Companies` o `Reports`. `Users` persiste empresas, planes y usuarios con `EloquentCompanyRepository`, `EloquentPlanRepository` y `EloquentUserRepository`. El alta de plataforma es `OpenCompanyAccount` (empresa + `company_admin`). Los planes viven en `plans` (`micro`, `small_business`, `enterprise`). `companies.max_branches` (nullable, default 2) limita sucursales activas; `null` es ilimitado. `CreateBranch` y `UpdateCompanyLimits` lo aplican.
17. Cada feature vive en `app/Features/{Feature}/Domain`, `Application` e `Infrastructure`. Los ports siguen siendo interfaces.
18. HTTP de páginas está en `app/Http`. El `company_id` sale del usuario autenticado, nunca del formulario. El checador web vive en `/punch` (`PunchSource::MobileWeb`) y no manda `punchedAt`. Las pantallas de panel son `/{seccion}` (`/employees`, `/branches`, `/schedules`, `/attendance`, `/reports`); los datos JSON paginados y las mutaciones van en `/admin/{seccion}`.
19. Migraciones en `database/migrations`. No corras `migrate:fresh` ni borres datos sin petición explícita.
20. No uses `env()` dentro de `app/`. Lee configuración con `config()`.
21. No expongas secretos ni copies valores de `.env` a docs o reglas.
22. No dejes `// TODO` ni métodos vacíos “por si acaso”.
23. El ciclo de asistencia es `clock_in` → `lunch_out` → `lunch_in` → `clock_out`.
24. `RegisterPunch` valida empresa, secuencia y geocerca, guarda por el port y llama a `CalculateDailyAttendance`. App y web usan la hora del servidor. `GetCompanyAttendanceRange` alimenta `/admin/reports`. Sucursales y el checador usan `GOOGLE_MAPS_API_KEY` cuando está definida.
25. La app móvil vive en `mobile/src/features/{auth,attendance,history,profile}` con `domain/` y `application/`. Acciones con verbo, sin `UseCase`. No hay cliente HTTP ni pantallas.
26. No generes el proyecto nativo de React Native (ios/android) salvo que lo pidan. Los tests móviles corren con `npm test` dentro de `mobile/`. `mobile/` es un proyecto de paquetes aparte; sus comandos se corren dentro de `mobile/`, no como workspace del raíz.
27. Artisan, Composer y tests corren dentro de `php-checa` (`make up`, `make enter`, `make test`). No ejecutes `migrate:fresh`, `make restart` ni borres volúmenes de Docker sin petición explícita. `make restart` borra la base `checaflow`, la crea de nuevo, migra y corre `php artisan initial-setup`.
28. Laravel trajo `cache`, `jobs`, `sessions` y `password_reset_tokens`. No los reemplaces.
29. Índices y `onDelete` están definidos en las migraciones. No los cambies de paso.
30. Legacy que viole estas reglas no es plantilla. Los modelos Eloquent están en `app/Models` y solo los usan los adaptadores y el borde HTTP.
31. El catálogo es `PermissionName` y el port es `PermissionChecker` (`allows(UserRole, PermissionName)`), implementado por `RolePermissions` sin framework. `AuthorizeActor` corre al inicio del caso. `super_admin` (`company_id` nulo) tiene `companies.manage`, `company_admins.manage` y `attendance.view_company_report` (solo lectura de tableros de cualquier empresa). Las mutaciones de tenant siguen bloqueadas para `super_admin`. En `/platform` usa `OpenCompanyAccount`. `company_admin` / `hr` / `employee` conservan el mapa de siempre. `UpdateBranch`, `UpdateSchedule` y `UpdateEmployee` editan agregados; la foto vive en `users.photo_path`.
32. El cliente web tipado vive en `resources/js/features`. La UI es Blade + Vite + Tailwind. Las listas del panel cargan con `fetch` desde `/admin/{seccion}` (JSON + paginación). Las páginas shell están en `/{seccion}`. `super_admin` ve las mismas secciones con filtro de empresa; no envía `company_id` en mutaciones de tenant.
33. Pantallas del panel: `cf-page-head` + panel lista/tabla vía fetch + alta/edición en modal (`04-admin-ui`). Paleta `#1767E2` y blanco. `PageQuery` / `PagedResult` en `Shared`.
