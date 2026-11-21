# ChecaFlow — contexto del proyecto

SaaS B2B multi-tenant de control de asistencia. El ciclo diario tiene cuatro marcas: `clock_in`, `lunch_out`, `lunch_in`, `clock_out`. El producto debe poder crecer hacia Recursos Humanos sin mezclar ese dominio dentro de asistencia.

Fase actual: migraciones, dominio, adaptadores Eloquent, portal `/platform` (`OpenCompanyAccount` + tablero de planes), checador web móvil y panel de asistencia para `company_admin` / `hr`. La app móvil sigue en TypeScript, sin pantallas ni cliente HTTP.

## Stack real

- PHP 8.4-FPM en Docker (`php-checa`)
- Laravel 13.43 (`laravel/laravel` 13.11)
- MySQL 8.4 (`mysql-checa`), Redis 8, Nginx, Horizon (`laravel/horizon` ^5.52), scheduler y phpMyAdmin
- Auth de framework: sesión web sobre `App\Models\User`
- Tests: PHPUnit 12, dentro del contenedor (`make test`). `phpunit.xml` usa SQLite en memoria
- App móvil en `mobile/`: TypeScript, solo domain y application. Sin pantallas ni cliente HTTP

## Dominio

Una **company** es el tenant. Tiene **branches** (sucursales, con geocerca opcional) y **schedules** (horario esperado y días laborables).

Un **user** entra al sistema. Su `role` es `super_admin`, `company_admin`, `hr` o `employee`. Solo `super_admin` puede tener `company_id` nulo. El CHECK `users_role_company_consistency` lo exige en MySQL, MariaDB y PostgreSQL.

`super_admin` tiene `company_id` nulo y solo `companies.manage` y `company_admins.manage`. `company_admin` administra su empresa: sucursales, horarios, usuarios `hr` o `employee`, empleados y el reporte de asistencia. `hr` administra empleados, horarios y el reporte de su empresa. `employee` solo marca y ve su historial. El catálogo es `PermissionName`. El port es `PermissionChecker`, en `app/Features/Users/Domain`, implementado por `RolePermissions`. `AuthorizeActor` lo usa al inicio del caso. No hay adaptador Spatie.

Un **employee profile** une a un usuario con una sucursal y un horario dentro de una empresa. `employee_code` se repite entre empresas, no dentro de la misma. `user_id` es único: una persona, un perfil.

Un **attendance log** es una marca. Un **daily attendance summary** es el cálculo del día: `late_minutes`, `lunch_minutes`, `excess_lunch_minutes`, `worked_minutes` y `status` (`on_time`, `late`, `incomplete`, `absent`).

MySQL no puede comprobar, con un CHECK, que la sucursal y el horario del perfil sean de la misma empresa. `CreateEmployee` y `LoadEmployeeShift` lo exigen en aplicación: cargan sucursal y horario con el `CompanyId` y rechazan el cruce.

## Relaciones

```
companies
  ├── branches
  ├── schedules
  ├── users (company_id null solo en super_admin)
  ├── employee_profiles → user, branch, schedule
  ├── attendance_logs → employee_profile, branch
  └── daily_attendance_summaries → employee_profile, branch, schedule
```

`onDelete`:

- Hijo del tenant hacia `companies`: `cascade`
- `users.company_id`: `restrict` (un `null` por borrado convertiría empleados en cuentas sin tenant)
- `branch_id` y `schedule_id`: `restrict`

## Árbol real (sin vendor ni storage)

```
checaflow/
├── app/
│   ├── Features/{Users,Branches,Schedules,Employees,Attendance,Shared}/{Domain,Application}
│   ├── Features/{Branches,Schedules,Employees,Attendance}/Infrastructure
│   ├── Http/{Auth,Attendance,Controllers,Middleware,Requests,Search}
│   ├── Models/
│   └── Providers/AppServiceProvider.php
├── bootstrap/
├── config/          # employees|branches|schedules|attendance.php (sortable/filterable)
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/
│   └── seeders/DatabaseSeeder.php
├── docker/
│   ├── nginx/default.conf
│   └── php/{php.ini,xdebug.ini}
├── docs/
├── public/
├── resources/
├── routes/
├── tests/
├── AGENTS.md
├── composer.json
├── Dockerfile
├── docker-compose.yml
├── docker-compose-staging.yaml
└── Makefile
```

## Código de negocio

Domain y Application no cambian de forma. La persistencia está en `Infrastructure` (`EloquentBranchRepository`, `EloquentScheduleRepository`, `EloquentEmployeeRepository`, `EloquentAttendanceLogRepository`, `EloquentDailyAttendanceRepository`, `EloquentCompanyClock`). `AppServiceProvider` ata cada port.

`OpenEmployeeAccount` crea el usuario `employee` y después llama a `CreateEmployee`. El controller envuelve esa pareja en una transacción.

Pantallas:

- `/attendance` — reloj, estado, botón del siguiente evento, marcas del día e historial de 7 días. Origen `mobile_web`.
- `/admin/attendance` — métricas del día (presentes, tarde, en comida, incompletos) y la tabla por sucursal y fecha.
- `/admin/reports` — rango de fechas sobre `daily_attendance_summaries` (`GetCompanyAttendanceRange`).
- `/{employees,branches,schedules,attendance,reports}` — shells del panel; JSON paginado en `/admin/{seccion}`.
- `/punch` — checador del empleado.
- `super_admin` usa las mismas secciones de lectura (+ `/platform` para alta de empresas).

`Users` persiste empresas, planes y usuarios. El portal de `/platform` usa `OpenCompanyAccount` (empresa + administrador) y muestra el tablero de uso. Los planes son `micro`, `small_business` y `enterprise`. No hay carpetas `Auth`, `Companies` ni `Reports`.

El cliente web tipado vive en `resources/js/features`. No hay pantallas nuevas: la UI sigue en Blade.

La capa móvil ya está, sin proyecto nativo:

```
mobile/src/features/
├── auth/{domain,application}          SignIn, SignOut, GetCurrentSession, RequireSession
├── attendance/{domain,application}    RegisterPunch, GetTodayStatus
├── history/{domain,application}       GetAttendanceHistory
└── profile/{domain,application}       GetEmployeeProfile
```

## Glosario

| Término | Significado |
| --- | --- |
| company | Tenant. Aísla todos los datos. |
| branch | Sucursal. Puede exigir geocerca. |
| schedule | Horario esperado y días laborables. |
| employee profile | Ficha laboral. Une usuario, sucursal y horario. |
| punch | Una marca del ciclo de cuatro. |
| daily summary | Totales y estado de un empleado en una fecha. |
| super_admin | Operador de plataforma, sin `company_id`. Permisos `companies.manage` y `company_admins.manage`. |
| company_admin | Administra su empresa: sucursales, horarios, usuarios, empleados, reporte y ajuste de marcas. |
| hr | En su empresa: empleados, horarios y reporte. No administra usuarios ni sucursales. |
| employee | Marca (`attendance.punch`) y ve su historial (`attendance.view_own_history`). |

## Qué no está

- Carpetas de feature `Auth`, `Companies`, `Reports`
- Proyecto nativo de React Native (ios/android). La app móvil no tiene cliente HTTP
- Pantallas nuevas del cliente web tipado (`resources/js/features` solo tiene tipos y servicios)
- Edición o baja de sucursales, horarios y empleados. El panel crea y asigna horario.
