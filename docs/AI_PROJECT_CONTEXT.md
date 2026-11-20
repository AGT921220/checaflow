# ChecaFlow — contexto del proyecto

SaaS B2B multi-tenant de control de asistencia. El ciclo diario tiene cuatro marcas: `clock_in`, `lunch_out`, `lunch_in`, `clock_out`. El producto debe poder crecer hacia Recursos Humanos sin mezclar ese dominio dentro de asistencia.

Fase actual: esqueleto Laravel 13 y migraciones. Aún no hay clases de Domain ni Application, ni app móvil.

## Stack real

- PHP 8.4-FPM en Docker (`php-checa`)
- Laravel 13.43 (`laravel/laravel` 13.11)
- MySQL 8.4 (`mysql-checa`), Redis 8, Nginx, Horizon (`laravel/horizon` ^5.52), scheduler y phpMyAdmin
- Auth de framework: `App\Models\User`
- Tests: PHPUnit 12, dentro del contenedor (`make test`). `phpunit.xml` usa SQLite en memoria
- Node no está instalado. No hay carpeta `mobile/`

## Dominio

Una **company** es el tenant. Tiene **branches** (sucursales, con geocerca opcional) y **schedules** (horario esperado y días laborables).

Un **user** entra al sistema. Su `role` es `super_admin`, `company_admin`, `hr` o `employee`. Solo `super_admin` puede tener `company_id` nulo. El CHECK `users_role_company_consistency` lo exige en MySQL, MariaDB y PostgreSQL.

Un **employee profile** une a un usuario con una sucursal y un horario dentro de una empresa. `employee_code` se repite entre empresas, no dentro de la misma. `user_id` es único: una persona, un perfil.

Un **attendance log** es una marca. Un **daily attendance summary** es el cálculo del día: `late_minutes`, `lunch_minutes`, `excess_lunch_minutes`, `worked_minutes` y `status` (`on_time`, `late`, `incomplete`, `absent`).

MySQL no puede comprobar, con un CHECK, que la sucursal y el horario del perfil sean de la misma empresa. Esa igualdad la va a exigir la capa de aplicación.

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
│   ├── Http/Controllers/Controller.php
│   ├── Models/User.php
│   └── Providers/AppServiceProvider.php
├── bootstrap/
├── config/
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

## Estructura que seguirá el código de negocio

Todavía no está creada. Fase 2 la llena solo con Domain y Application.

```
app/Features/
├── Auth/
│   ├── Domain/
│   └── Application/
├── Companies/
│   ├── Domain/
│   └── Application/
├── Branches/
│   ├── Domain/
│   └── Application/
├── Schedules/
│   ├── Domain/
│   └── Application/
├── Employees/
│   ├── Domain/
│   └── Application/
├── Attendance/
│   ├── Domain/
│   └── Application/
└── Reports/
│   ├── Domain/
│   └── Application/
```

Fase 3, cuando exista la app React Native:

```
mobile/src/features/
├── auth/{domain,application}
├── attendance/{domain,application}
├── history/{domain,application}
└── profile/{domain,application}
```

Las acciones móviles se nombran con verbo, sin `UseCase`: `RegisterPunch`, `GetTodayStatus`, `GetAttendanceHistory`.

## Glosario

| Término | Significado |
| --- | --- |
| company | Tenant. Aísla todos los datos. |
| branch | Sucursal. Puede exigir geocerca. |
| schedule | Horario esperado y días laborables. |
| employee profile | Ficha laboral. Une usuario, sucursal y horario. |
| punch | Una marca del ciclo de cuatro. |
| daily summary | Totales y estado de un empleado en una fecha. |
| super_admin | Operador de plataforma, sin `company_id`. |

## Qué no está

- Controladores, FormRequests y adaptadores Eloquent de negocio
- Ports implementados
- `mobile/`
- Reglas por módulo: se agregan cuando el feature exista en código
